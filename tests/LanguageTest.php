<?php

namespace App\Tests;

use Symfony\Component\Finder\Finder;

class LanguageTest extends AppWebTestCase
{
    private function switchToEnglish(): void
    {
        $this->client->request('GET', '/');
        $this->client->request('GET', '/langue/en', [], [], ['HTTP_REFERER' => 'http://localhost/boutique']);
    }

    public function testFrenchIsTheDefaultAndTheHeaderOffersBothLanguages(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertSame('fr', $crawler->filter('html')->attr('lang'));
        $this->assertSelectorTextContains('main', 'Sélection du moment');
        $this->assertSame(['/langue/fr', '/langue/en'], $crawler->filter('.lang-switch a')->each(fn ($link) => $link->attr('href')));
    }

    public function testSwitchingReturnsToThePageAndIsRemembered(): void
    {
        $this->switchToEnglish();

        $this->assertResponseRedirects('http://localhost/boutique');
        $crawler = $this->client->followRedirect();
        $this->assertSame('en', $crawler->filter('html')->attr('lang'));
        $this->assertSelectorTextContains('h1', 'All pieces');

        $this->client->request('GET', '/');
        $this->assertSelectorTextContains('main', 'Current selection');

        $this->client->request('GET', '/langue/fr');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('main', 'Sélection du moment');
    }

    public function testRedirectsToTheHomePageWhenTheVisitorComesFromAnotherSite(): void
    {
        $this->client->request('GET', '/langue/en', [], [], ['HTTP_REFERER' => 'https://evil.example/']);

        $this->assertResponseRedirects('/');
    }

    public function testAnUnknownLanguageDoesNotExist(): void
    {
        $this->client->request('GET', '/langue/de');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheProductAndItsPriceAreInEnglish(): void
    {
        $this->switchToEnglish();

        $crawler = $this->client->request('GET', '/produit/' . $this->product('Dragon de rubis')->getSlug());

        $this->assertSelectorTextContains('h1', 'Ruby dragon');
        $this->assertSelectorTextContains('main', 'A dragon coiled around its crystal orb.');
        $this->assertSelectorTextContains('main', 'Hand-painted resin');
        $this->assertSelectorTextContains('main', '€49.90');
        $this->assertSelectorTextContains('.rating-summary', '4.5 / 5 · 2 verified reviews');
        $this->assertStringContainsString('A dragon coiled', $crawler->filter('meta[name=description]')->attr('content'));
    }

    public function testEveryDemoProductHasAnEnglishVersion(): void
    {
        $products = $this->em->getRepository(\App\Entity\Product::class)->findAll();
        $catalogue = require dirname(__DIR__) . '/translations/catalogue.en.php';

        $this->assertCount(40, $products);
        foreach ($products as $product) {
            foreach ([$product->getName(), $product->getDescription(), $product->getMaterial()] as $text) {
                $this->assertArrayHasKey($text, $catalogue);
            }
        }
    }

    public function testSearchesTheEnglishTexts(): void
    {
        $this->switchToEnglish();

        $crawler = $this->client->request('GET', '/boutique?q=mermaid');

        $this->assertSame(1, $crawler->filter('#catalogue .plate')->count());
        $this->assertSelectorTextContains('#catalogue', 'Bronze mermaid');
    }

    public function testFormErrorsAreInEnglish(): void
    {
        $this->switchToEnglish();
        $this->client->followRedirect();

        $crawler = $this->client->request('GET', '/inscription');
        $this->client->submit($crawler->selectButton('Create my account')->form());

        $this->assertSelectorTextContains('main', 'Choose a password.');
    }

    public function testFlashMessagesAreInEnglish(): void
    {
        $this->switchToEnglish();
        $this->client->followRedirect();

        $product = $this->product('Dragon de rubis');
        $crawler = $this->client->request('GET', '/produit/' . $product->getSlug());
        $this->client->submit($crawler->selectButton('Add to the order form')->form());
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', '"Ruby dragon" has been added to the order form.');
    }

    public function testTheLegalNoticeAndTheOrderStatusesAreInEnglish(): void
    {
        $this->switchToEnglish();

        $this->client->request('GET', '/mentions-legales');
        $this->assertSelectorTextContains('h1', 'Legal notice');

        $this->loginAs('client@figurama.test');
        $this->client->request('GET', '/compte');
        $this->assertSelectorTextContains('main', 'Delivered');
    }

    public function testEveryTextOfTheTemplatesHasAnEnglishVersion(): void
    {
        $root = dirname(__DIR__);
        $messages = require $root . '/translations/messages.en.php';
        $files = (new Finder())->files()->in($root . '/templates')->name('*.twig');

        $missing = [];
        foreach ($files as $file) {
            preg_match_all('/(\'(?:[^\'\\\\]|\\\\.)*\'|"[^"]*")\|trans(?!\(\{\}, \'catalogue\'\))/u', $file->getContents(), $calls);
            foreach ($calls[1] as $literal) {
                $text = str_replace("\\'", "'", substr($literal, 1, -1));
                if (!isset($messages[$text])) {
                    $missing[] = $text;
                }
            }
        }

        $this->assertSame([], $missing);
    }
}
