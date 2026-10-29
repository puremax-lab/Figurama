<?php

namespace App\Tests;

use App\Entity\OrderStatus;

final class CheckoutTest extends AppWebTestCase
{
    public function testCheckoutRequiresAnAccount(): void
    {
        $this->addToCart('Dragon de rubis');
        $this->client->request('GET', '/commande');

        $this->assertResponseRedirects('/connexion');
    }

    public function testRedirectsWhenTheCartIsEmpty(): void
    {
        $this->loginAs('client@figurama.test');
        $this->client->request('GET', '/commande');

        $this->assertResponseRedirects('/boutique');
    }

    public function testPlacingAnOrderReservesTheStockAndEmptiesTheCart(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Dragon de rubis', 2); // stock: 6
        $this->checkout();

        $order = $this->latestOrder();
        $this->assertResponseRedirects('/commande/' . $order->getReference() . '/paiement');
        $this->assertSame(OrderStatus::Pending, $order->getStatus());
        $this->assertSame(9980, $order->getTotalCents());
        $this->assertSame('Bruxelles', $order->getShippingCity());
        $this->assertSame(4, $this->product('Dragon de rubis')->getStock());

        $this->client->request('GET', '/panier');
        $this->assertSelectorTextContains('main', 'Votre panier est vide.');
    }

    public function testShowsErrorsForAnInvalidAddress(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Dragon de rubis');
        $crawler = $this->client->request('GET', '/commande');
        $this->client->submit($crawler->selectButton('Continuer vers le paiement')->form([
            'shipping_form[shippingAddress]' => '',
            'shipping_form[shippingPostalCode]' => '!!',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('main', 'Code postal invalide.');
        $this->assertSame(6, $this->product('Dragon de rubis')->getStock());
    }

    public function testOnlyOrdersWhatIsStillInStock(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Patrouille nocturne', 2);
        // Someone else buys one while the customer is still shopping.
        $unicorn = $this->product('Patrouille nocturne');
        $unicorn->setStock(1);
        $this->em->flush();

        $this->checkout();

        $order = $this->latestOrder();
        $this->assertSame(1, $order->getItems()->first()->getQuantity());
        $this->assertSame(0, $this->product('Patrouille nocturne')->getStock());
    }

    public function testDemoPaymentMarksTheOrderAsPaid(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Duo de robots en tôle');
        $this->checkout();
        $crawler = $this->client->followRedirect();

        $this->assertSelectorTextContains('main', 'Mode démo');
        $this->client->submit($crawler->filter('form[action$="paiement-demo"]')->form());
        $this->client->followRedirect();

        $this->assertSelectorTextContains('h1', 'Merci pour votre commande');
        $order = $this->latestOrder();
        $this->assertSame(OrderStatus::Paid, $order->getStatus());
        $this->assertNotNull($order->getPaidAt());
    }

    public function testCustomerCanCancelAnUnpaidOrderAndTheStockComesBack(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Nain à la pipe', 3); // stock: 3
        $this->checkout();
        $this->assertSame(0, $this->product('Nain à la pipe')->getStock());

        $crawler = $this->client->request('GET', '/compte/commandes/' . $this->latestOrder()->getReference());
        $this->client->submit($crawler->selectButton('Annuler la commande')->form());

        $this->assertSame(OrderStatus::Cancelled, $this->latestOrder()->getStatus());
        $this->assertSame(3, $this->product('Nain à la pipe')->getStock());
    }

    public function testCustomersCannotSeeOtherCustomersOrders(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Mannequin d\'atelier');
        $this->checkout();
        $reference = $this->latestOrder()->getReference();

        $this->client->loginUser($this->createStranger());

        $this->client->request('GET', '/compte/commandes/' . $reference);
        $this->assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/commande/' . $reference . '/paiement');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccountListsTheCustomerOrders(): void
    {
        $this->loginAs('client@figurama.test');
        $crawler = $this->client->request('GET', '/compte');

        $this->assertCount(3, $crawler->filter('tbody tr'));
        $this->assertSelectorTextContains('tbody', 'Livrée');
    }

    private function createStranger(): \App\Entity\User
    {
        $user = (new \App\Entity\User())->setEmail('autre@figurama.test')->setFirstName('Autre')->setLastName('Client')->setPassword('x');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
