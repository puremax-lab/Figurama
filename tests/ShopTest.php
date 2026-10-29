<?php

namespace App\Tests;

final class ShopTest extends AppWebTestCase
{
    public function testHomeShowsCategoriesAndFeaturedProducts(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Des héros, des dragons');
        $this->assertCount(5, $crawler->filter('.category-chip'));
        $this->assertCount(4, $crawler->filter('.product-card'));
    }

    public function testFiltersByCategoryAndSortsByPrice(): void
    {
        $crawler = $this->client->request('GET', '/boutique?categorie=fantasy&tri=prix-croissant');

        $names = $crawler->filter('.product-card h3')->each(fn ($node) => trim($node->text()));
        $this->assertSame(['Licorne des brumes', 'Mage de la tour d\'argent', 'Chevalier de l\'aube', 'Dragon des cendres'], $names);
    }

    public function testSearchesProductsByText(): void
    {
        $crawler = $this->client->request('GET', '/boutique?q=ROBOT');

        $this->assertSame(['Robot explorateur R-7'], $crawler->filter('.product-card h3')->each(fn ($n) => trim($n->text())));
    }

    public function testCanHideOutOfStockProducts(): void
    {
        $all = $this->client->request('GET', '/boutique?categorie=science-fiction');
        $this->assertStringContainsString('Astronaute en apesanteur', $all->text());

        $inStock = $this->client->request('GET', '/boutique?categorie=science-fiction&dispo=1');
        $this->assertStringNotContainsString('Astronaute en apesanteur', $inStock->text());
    }

    public function testPaginatesTheCatalogue(): void
    {
        $this->assertCount(12, $this->client->request('GET', '/boutique')->filter('.product-card'));
        $this->assertCount(4, $this->client->request('GET', '/boutique?page=2')->filter('.product-card'));
    }

    public function testProductPageShowsPriceAndStock(): void
    {
        $this->client->request('GET', '/produit/licorne-des-brumes');

        $this->assertSelectorTextContains('h1', 'Licorne des brumes');
        $this->assertSelectorTextContains('.fs-3', '29,90');
        $this->assertSelectorTextContains('main', 'Plus que 2 en stock');
    }

    public function testHiddenOrUnknownProductsAnswer404(): void
    {
        $product = $this->product('Panda gourmand');
        $product->setActive(false);
        $this->em->flush();

        $this->client->request('GET', '/produit/panda-gourmand');
        $this->assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/produit/nexiste-pas');
        $this->assertResponseStatusCodeSame(404);
    }
}
