<?php

namespace App\Tests;

final class CartTest extends AppWebTestCase
{
    public function testAddsProductsToTheCart(): void
    {
        $this->addToCart('Dragon de rubis', 2);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'ajouté au panier');
        $crawler = $this->client->request('GET', '/panier');
        $this->assertSelectorTextContains('main', 'Dragon de rubis');
        $this->assertSelectorTextContains('main', '99,80');
        $this->assertStringContainsString('[2]', $crawler->filter('nav')->text());
    }

    public function testNeverPutsMoreThanTheStockInTheCart(): void
    {
        $this->addToCart('Patrouille nocturne', 2); // stock: 2
        $this->addToCart('Patrouille nocturne', 1);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-warning', 'plus disponible en plus grande quantité');
        $crawler = $this->client->request('GET', '/panier');
        $this->assertSame('2', $crawler->filter('input[name="quantity"]')->attr('value'));
    }

    public function testCannotBuyAnOutOfStockProduct(): void
    {
        $this->client->request('GET', '/produit/robot-a-remontoir');

        $this->assertSelectorTextContains('main', 'reviendra bientôt en stock');
        $this->assertSelectorNotExists('form[action*="/panier/ajouter"]');
    }

    public function testUpdatesAndRemovesLines(): void
    {
        $this->addToCart('Mannequin d\'atelier', 1);
        $crawler = $this->client->request('GET', '/panier');
        $this->client->submit($crawler->selectButton('Mettre à jour')->form(['quantity' => 3]));
        $crawler = $this->client->followRedirect();
        $this->assertSame('3', $crawler->filter('input[name="quantity"]')->attr('value'));

        $this->client->submit($crawler->selectButton('Mettre à jour')->form(['quantity' => 0]));
        $this->client->followRedirect();
        $this->assertSelectorTextContains('main', 'Aucune pièce n\'a encore été ajoutée.');
    }

    public function testRefusesCartChangesWithoutCsrfToken(): void
    {
        $product = $this->product('Mannequin d\'atelier');
        $this->client->request('POST', '/panier/ajouter/' . $product->getId(), ['quantity' => 1]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDropsProductsThatAreNoLongerSold(): void
    {
        $this->addToCart('Mannequin d\'atelier', 1);
        $product = $this->product('Mannequin d\'atelier');
        $product->setActive(false);
        $this->em->flush();

        $this->client->request('GET', '/panier');
        $this->assertSelectorTextContains('main', 'Aucune pièce n\'a encore été ajoutée.');
    }
}
