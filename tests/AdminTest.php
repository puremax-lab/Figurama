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
        // Delivered 95,70 + shipped 39,90 + paid 79,60 (demo orders)
        $this->assertSelectorTextContains('.stat-card', '215,20');
        $this->assertSelectorTextContains('main', 'Hibou érudit');
        $this->assertSelectorTextContains('main', 'Astronaute en apesanteur');
    }

    public function testShipsThenDeliversAnOrder(): void
    {
        $this->loginAs('admin@figurama.test');
        $order = $this->orderWithStatus(OrderStatus::Paid);

        $crawler = $this->client->request('GET', '/admin/commandes/' . $order->getReference());
        $this->client->submit($crawler->selectButton('Marquer comme expédiée')->form());
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.badge', 'Expédiée');

        $this->client->submit($crawler->selectButton('Marquer comme livrée')->form());
        $this->assertSame(OrderStatus::Delivered, $this->orderByReference($order->getReference())->getStatus());
    }

    public function testCancellingAPaidOrderPutsTheItemsBackInStock(): void
    {
        $this->loginAs('admin@figurama.test');
        $order = $this->orderWithStatus(OrderStatus::Paid); // 1 super-heroine + 3 pixel ghosts
        $before = $this->product('Fantôme pixel')->getStock();

        $crawler = $this->client->request('GET', '/admin/commandes/' . $order->getReference());
        $this->client->submit($crawler->selectButton('Annuler et remettre en stock')->form());

        $this->assertSame(OrderStatus::Cancelled, $this->orderByReference($order->getReference())->getStatus());
        $this->assertSame($before + 3, $this->product('Fantôme pixel')->getStock());
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
            'product_form[icon]' => '🗿',
            'product_form[color]' => '#e5e5e5',
        ]);
        $form['product_form[category]']->select((string) $this->product('Dragon des cendres')->getCategory()->getId());
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
