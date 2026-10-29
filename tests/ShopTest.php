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
        $this->assertSelectorTextContains('h1', 'choisies avec soin');
        $this->assertCount(3, $crawler->filter('.category-tile'));
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

    public function testLoadsTheCatalogueByChunksWhileScrolling(): void
    {
        $crawler = $this->client->request('GET', '/boutique?categorie=&tri=prix-croissant');
        $this->assertCount(12, $crawler->filter('#catalogue .plate'));
        $next = $crawler->filter('[data-load-more]')->attr('href');
        $this->assertStringContainsString('page=2', $next);
        $this->assertStringContainsString('tri=prix-croissant', $next, 'the filters are kept');

        // What assets/app.js asks for: only the next cards, and no link since it is the last page.
        $more = $this->client->request('GET', $next, server: ['HTTP_X_REQUESTED_WITH' => 'fetch']);
        $this->assertCount(3, $more->filter('[data-items] .plate'));
        $this->assertCount(0, $more->filter('[data-load-more]'));
        $this->assertCount(0, $more->filter('nav'), 'no layout around the cards');
    }

    public function testProductPageShowsTheTechnicalSheet(): void
    {
        $this->client->request('GET', '/produit/patrouille-nocturne');

        $this->assertSelectorTextContains('h1', 'Patrouille nocturne');
        $this->assertSelectorTextContains('.price-big', '64,90');
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
