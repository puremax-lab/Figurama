<?php

namespace App\Tests;

use App\Entity\Order;
use App\Entity\OrderStatus;

final class AdminTest extends AppWebTestCase
{
    public function testOnlyAdminsCanOpenTheBackOffice(): void
    {
        $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('/connexion');

        $this->loginAs('client@figurama.test');
        $this->client->request('GET', '/admin');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDashboardShowsRevenueOrdersToShipAndLowStock(): void
    {
        $this->loginAs('admin@figurama.test');
        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        // Demo orders: 87,70 + 44,90 + 114,60, plus three reviewers' orders 94,80 + 104,70 + 84,70
        $this->assertSelectorTextContains('.counter', '531,40');
        $this->assertSelectorTextContains('main', 'Hibou de bronze');
        $this->assertSelectorTextContains('main', 'Robot à remontoir');
    }

    public function testShipsThenDeliversAnOrder(): void
    {
        $this->loginAs('admin@figurama.test');
        $order = $this->orderWithStatus(OrderStatus::Paid);

        $crawler = $this->client->request('GET', '/admin/commandes/' . $order->getReference());
        $this->client->submit($crawler->selectButton('Marquer comme expédiée')->form());
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('main .status', 'Expédiée');

        $this->client->submit($crawler->selectButton('Marquer comme livrée')->form());
        $this->assertSame(OrderStatus::Delivered, $this->orderByReference($order->getReference())->getStatus());
    }

    public function testCancellingAPaidOrderPutsTheItemsBackInStock(): void
    {
        $this->loginAs('admin@figurama.test');
        $order = $this->orderWithStatus(OrderStatus::Paid); // 1 winged diver + 3 lunar astronauts
        $before = $this->product('Astronaute lunaire')->getStock();

        $crawler = $this->client->request('GET', '/admin/commandes/' . $order->getReference());
        $this->client->submit($crawler->selectButton('Annuler et remettre en stock')->form());

        $this->assertSame(OrderStatus::Cancelled, $this->orderByReference($order->getReference())->getStatus());
        $this->assertSame($before + 3, $this->product('Astronaute lunaire')->getStock());
    }

    public function testRefusesAnImpossibleStatusChange(): void
    {
        $this->loginAs('admin@figurama.test');
        $delivered = $this->orderWithStatus(OrderStatus::Delivered);
        $crawler = $this->client->request('GET', '/admin/commandes/' . $delivered->getReference());
        $this->assertCount(0, $crawler->filter('main form'), 'no action on a delivered order');

        // A shipped order only offers "delivered": a forged "cancelled" is refused.
        $shipped = $this->orderWithStatus(OrderStatus::Shipped);
        $crawler = $this->client->request('GET', '/admin/commandes/' . $shipped->getReference());
        $form = $crawler->selectButton('Marquer comme livrée')->form();
        $form['status']->setValue('cancelled');
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-danger', 'ne peut pas passer');
        $this->assertSame(OrderStatus::Shipped, $this->orderByReference($shipped->getReference())->getStatus());
    }

    public function testCreatesAProduct(): void
    {
        $this->loginAs('admin@figurama.test');
        $crawler = $this->client->request('GET', '/admin/produits/nouveau');
        $form = $crawler->selectButton('Enregistrer')->form([
            'product_form[name]' => 'Golem de pierre',
            'product_form[description]' => 'Golem massif taillé dans une pierre grise, yeux de cristal bleu.',
            'product_form[priceCents]' => '44,90',
            'product_form[stock]' => '5',
            'product_form[imageUrl]' => 'https://images.unsplash.com/photo-1592628457982-c4181aeecbd7',
            'product_form[heightCm]' => '16',
            'product_form[scale]' => '1/10',
            'product_form[material]' => 'Pierre reconstituée',
        ]);
        $form['product_form[category]']->select((string) $this->product('Dragon de rubis')->getCategory()->getId());
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/produits');
        $golem = $this->product('Golem de pierre');
        $this->assertSame(4490, $golem->getPriceCents());
        $this->assertSame('golem-de-pierre', $golem->getSlug());
    }

    private function orderWithStatus(OrderStatus $status): Order
    {
        $this->em->clear();

        return $this->em->getRepository(Order::class)->findOneBy(['status' => $status]);
    }

    private function orderByReference(string $reference): Order
    {
        $this->em->clear();

        return $this->em->getRepository(Order::class)->findOneBy(['reference' => $reference]);
    }
}
