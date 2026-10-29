<?php

namespace App\Tests;

final class ShopTest extends AppWebTestCase
{
    private function names($crawler): array
    {
        return $crawler->filter('.plate-title')->each(fn ($node) => trim($node->text()));
    }

    public function testHomeShowsSeriesAndFeaturedProducts(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Figurines');
        $this->assertCount(3, $crawler->filter('.index-link'));
        $this->assertCount(4, $crawler->filter('.plate'));
    }

    public function testFiltersByCategoryAndSortsByPrice(): void
    {
        $crawler = $this->client->request('GET', '/boutique?categorie=fantasy&tri=prix-croissant');

        $this->assertSame(
            ['Dragonnet de mousse', 'Nain à la pipe', 'Hibou de bronze', 'Chevalier du croissant', 'Dragon de rubis', 'Patrouille nocturne'],
            $this->names($crawler),
        );
    }

    public function testSearchesProductsByText(): void
    {
        $names = $this->names($this->client->request('GET', '/boutique?q=ROBOT'));
        sort($names);

        $this->assertSame(['Duo de robots en tôle', 'Robot coiffé R-7', 'Robot à remontoir'], $names);
    }

    public function testCanHideOutOfStockProducts(): void
    {
        $all = $this->client->request('GET', '/boutique?categorie=retro-futur');
        $this->assertContains('Robot à remontoir', $this->names($all));

        $inStock = $this->client->request('GET', '/boutique?categorie=retro-futur&dispo=1');
        $this->assertNotContains('Robot à remontoir', $this->names($inStock));
    }

    public function testPaginatesTheCatalogue(): void
    {
        $this->assertCount(12, $this->client->request('GET', '/boutique')->filter('.plate'));
        $this->assertCount(3, $this->client->request('GET', '/boutique?page=2')->filter('.plate'));
    }

    public function testProductPageShowsTheTechnicalSheet(): void
    {
        $this->client->request('GET', '/produit/patrouille-nocturne');

        $this->assertSelectorTextContains('h1', 'Patrouille nocturne');
        $this->assertSelectorTextContains('.sticker-big', '64,90');
        $this->assertSelectorTextContains('.cartouche', '1/72');
        $this->assertSelectorTextContains('.cartouche', '2 pièces');
    }

    public function testHiddenOrUnknownProductsAnswer404(): void
    {
        $product = $this->product('Mannequin d\'atelier');
        $slug = $product->getSlug();
        $product->setActive(false);
        $this->em->flush();

        $this->client->request('GET', '/produit/' . $slug);
        $this->assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/produit/nexiste-pas');
        $this->assertResponseStatusCodeSame(404);
    }
}
