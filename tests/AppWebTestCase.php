<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Each test starts with a fresh SQLite database filled with the demo data. */
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $schema = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
        (new AppFixtures($container->get(UserPasswordHasherInterface::class)))->load($this->em);
        $this->em->clear();
    }

    protected function loginAs(string $email): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->client->loginUser($user);

        return $user;
    }

    protected function product(string $name): Product
    {
        $this->em->clear();

        return $this->em->getRepository(Product::class)->findOneBy(['name' => $name]);
    }

    protected function latestOrder(): ?Order
    {
        $this->em->clear();

        return $this->em->getRepository(Order::class)->findOneBy([], ['id' => 'DESC']);
    }

    /** Adds a product with the form of its page, like a visitor would. */
    protected function addToCart(string $name, int $quantity = 1): void
    {
        $product = $this->product($name);
        $crawler = $this->client->request('GET', '/produit/' . $product->getSlug());
        $form = $crawler->selectButton('Ajouter au panier')->form(['quantity' => $quantity]);
        $this->client->submit($form);
    }

    protected function checkout(): void
    {
        $crawler = $this->client->request('GET', '/commande');
        $this->client->submit($crawler->selectButton('Continuer vers le paiement')->form([
            'shipping_form[shippingName]' => 'Camille Dubois',
            'shipping_form[shippingAddress]' => 'Rue Neuve 1',
            'shipping_form[shippingPostalCode]' => '1000',
            'shipping_form[shippingCity]' => 'Bruxelles',
            'shipping_form[shippingCountry]' => 'BE',
        ]));
    }
}
