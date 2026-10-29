<?php

namespace App\Tests;

final class CartTest extends AppWebTestCase
{
    public function testAddsProductsToTheCart(): void
    {
        $this->addToCart('Dragon des cendres', 2);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'ajouté au panier');
        $crawler = $this->client->request('GET', '/panier');
        $this->assertSelectorTextContains('main', 'Dragon des cendres');
        $this->assertSelectorTextContains('main', '99,80');
        $this->assertSame('2', $crawler->filter('nav .badge')->first()->text());
    }

    public function testNeverPutsMoreThanTheStockInTheCart(): void
    {
        $this->addToCart('Licorne des brumes', 2); // stock: 2
        $this->addToCart('Licorne des brumes', 1);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-warning', 'plus disponible en plus grande quantité');
        $crawler = $this->client->request('GET', '/panier');
        $this->assertSame('2', $crawler->filter('input[name="quantity"]')->attr('value'));
    }

    public function testCannotBuyAnOutOfStockProduct(): void
    {
        $this->client->request('GET', '/produit/astronaute-en-apesanteur');

        $this->assertSelectorTextContains('main', 'Épuisé pour le moment');
        $this->assertSelectorNotExists('button:contains("Ajouter au panier")');
    }

    public function testUpdatesAndRemovesLines(): void
    {
        $this->addToCart('Panda gourmand', 1);
        $crawler = $this->client->request('GET', '/panier');
        $this->client->submit($crawler->selectButton('Mettre à jour')->form(['quantity' => 3]));
        $crawler = $this->client->followRedirect();
        $this->assertSame('3', $crawler->filter('input[name="quantity"]')->attr('value'));

        $this->client->submit($crawler->selectButton('Mettre à jour')->form(['quantity' => 0]));
        $this->client->followRedirect();
        $this->assertSelectorTextContains('main', 'Votre panier est vide.');
    }

    public function testRefusesCartChangesWithoutCsrfToken(): void
    {
        $product = $this->product('Panda gourmand');
        $this->client->request('POST', '/panier/ajouter/' . $product->getId(), ['quantity' => 1]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDropsProductsThatAreNoLongerSold(): void
    {
        $this->addToCart('Panda gourmand', 1);
        $product = $this->product('Panda gourmand');
        $product->setActive(false);
        $this->em->flush();

        $this->client->request('GET', '/panier');
        $this->assertSelectorTextContains('main', 'Votre panier est vide.');
    }
}
