<?php

namespace App\Tests;

use App\Entity\Review;

final class ReviewTest extends AppWebTestCase
{
    public function testShowsTheAverageRatingAndTheReviews(): void
    {
        $this->client->request('GET', '/produit/dragon-de-rubis');

        $this->assertSelectorTextContains('.rating-summary', '4,5 / 5 · 2 avis vérifiés');
        $this->assertSelectorTextContains('#avis', 'Les écailles sont encore plus belles en vrai.');
        $this->assertSelectorTextContains('#avis', 'achat vérifié');
    }

    public function testShowsTheRatingOnTheCards(): void
    {
        $crawler = $this->client->request('GET', '/boutique?q=dragon%20de%20rubis');

        $this->assertStringContainsString('(2)', $crawler->filter('.plate-rating')->text());
    }

    public function testOnlyCustomersWhoReceivedThePieceCanReviewIt(): void
    {
        // Visitor, then customer who never bought it: no form, and posting is refused.
        $this->client->request('GET', '/produit/robot-coiffe-r-7');
        $this->assertSelectorNotExists('form[name="review_form"]');

        $this->loginAs('client@figurama.test');
        $this->client->request('GET', '/produit/robot-coiffe-r-7');
        $this->assertSelectorNotExists('form[name="review_form"]');
        $this->client->request('POST', '/produit/robot-coiffe-r-7', ['review_form' => ['rating' => 5, 'comment' => 'Je ne l\'ai jamais reçu.']]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testACustomerReviewsAPieceTheyReceivedOnce(): void
    {
        $this->loginAs('client@figurama.test'); // received the Dragon de rubis in a delivered order
        $crawler = $this->client->request('GET', '/produit/dragon-de-rubis');
        $this->client->submit($crawler->selectButton('Publier mon avis')->form([
            'review_form[rating]' => '5',
            'review_form[comment]' => 'Magnifique, exactement comme sur la photo.',
        ]));

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.rating-summary', '3 avis vérifiés');
        $this->assertSelectorNotExists('form[name="review_form"]', 'one review per piece');
        $this->assertSame(1, $this->em->getRepository(Review::class)->count(['comment' => 'Magnifique, exactement comme sur la photo.']));
    }

    public function testRefusesAnEmptyReview(): void
    {
        $this->loginAs('client@figurama.test');
        $crawler = $this->client->request('GET', '/produit/dragon-de-rubis');
        $this->client->submit($crawler->selectButton('Publier mon avis')->form(['review_form[comment]' => 'Bof']));

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('#avis', 'Choisissez une note.');
        $this->assertSelectorTextContains('#avis', '10 caractères minimum.');
    }
}
