<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Demo catalogue, accounts and orders. Every account uses the password "password". */
final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $categories = [];
        foreach ([
            'fantasy' => ['Fantasy', 'FAN'],
            'retro-futur' => ['Rétro-futur', 'RTF'],
            'curiosites' => ['Curiosités', 'CUR'],
        ] as $slug => [$name, $code]) {
            $categories[$slug] = new Category($name, $slug, $code);
            $manager->persist($categories[$slug]);
        }

        // Photos from Unsplash (free licence): https://unsplash.com/license
        // [category, name, Unsplash photo id, price in cents, stock, featured, height cm, scale, material, description]
        $catalogue = [
            ['fantasy', 'Dragon de rubis', '1761501156501-d6e4fd7cde5f', 4990, 6, true, 24, '1/10', 'Résine peinte à la main', 'Dragon enroulé autour de son orbe de cristal. Les écailles sont peintes une à une en trois tons de rouge, puis vernies. Livré sur son socle laqué.'],
            ['fantasy', 'Dragonnet de mousse', '1792571394194-3fca5f360104', 2290, 18, true, 9, '1/6', 'Impression 3D, PLA mat', 'Petit dragon assis, imprimé en 3D puis poncé et peint à l\'aérographe. Le ventre et les cornes sont laissés en beige pour garder l\'aspect brut.'],
            ['fantasy', 'Chevalier du croissant', '1784720528906-76d551456e23', 3990, 9, false, 18, '1/32', 'Métal et résine', 'Chevalier épée levée au pied d\'un croissant de lune sculpté. Le personnage est en métal, le décor en résine teintée dans la masse.'],
            ['fantasy', 'Nain à la pipe', '1783171766117-225c1f70fa86', 2690, 3, false, 6, '1/28', 'Métal blanc peint', 'Miniature de jeu de plateau peinte avec soin : barbe en dégradé, cuir patiné, fumée de pipe sculptée. Socle de 32 mm.'],
            ['fantasy', 'Hibou de bronze', '1769839271985-87e087c2bbb5', 3490, 1, false, 11, '1/10', 'Bronze patiné', 'Hibou coulé en bronze puis patiné à la main. Lourd, froid au toucher, parfait comme serre-livres. Dernière pièce de la fonte.'],
            ['fantasy', 'Patrouille nocturne', '1779286661453-0fff222bccee', 6490, 2, false, 20, '1/72', 'Diorama résine, LED', 'Diorama d\'une rue gothique la nuit, avec ses lanternes allumées par de vraies LED (pile CR2032 fournie). Une scène entière à poser sur une étagère.'],
            ['retro-futur', 'Duo de robots en tôle', '1759322394509-87db4271279a', 4490, 7, true, 21, '1/6', 'Tôle lithographiée', 'Reproduction de deux robots jouets des années 60, en tôle imprimée comme à l\'époque. Les bras pivotent, les cadrans sont peints à la main.'],
            ['retro-futur', 'Robot coiffé R-7', '1527430253228-e93688616381', 3290, 12, false, 19, '1/6', 'Tôle lithographiée', 'Robot rétro à la tête crantée et aux yeux ronds. Un modèle de collection qui ne se démonte pas : à exposer, pas à jouer.'],
            ['retro-futur', 'Robot à remontoir', '1546776230-bb86256870ce', 2990, 0, false, 16, '1/6', 'Tôle et mécanisme à clé', 'Robot qui marche quand on tourne sa clé dans le dos. Mécanisme d\'origine restauré. Rupture de stock, retour prévu le mois prochain.'],
            ['retro-futur', 'Astronaute lunaire', '1694619100998-3b5ae167399c', 1990, 25, false, 8, '1/24', 'Résine blanche', 'Astronaute qui salue, debout sur un morceau de sol lunaire texturé. Résine blanche mate, sans peinture, pour un rendu de maquette.'],
            ['retro-futur', 'Astronautes en apesanteur', '1602212096437-d0af1ce0553e', 2490, 14, false, 7, '1/24', 'Résine, fils invisibles', 'Deux astronautes suspendus à des fils transparents, à accrocher au plafond ou dans une vitrine. Effet d\'apesanteur garanti.'],
            ['retro-futur', 'Scaphandrier ailé', '1765118460712-036937551d15', 5490, 4, true, 30, '1/8', 'Résine et laiton', 'Scaphandrier steampunk perché sur son piédestal, casque en laiton véritable et ailes mécaniques. La pièce la plus travaillée du catalogue.'],
            ['curiosites', 'Chien de porcelaine', '1773425349996-89b5413b0ae8', 2790, 10, false, 14, '1/4', 'Porcelaine émaillée', 'Chien assis en porcelaine blanche, émaillage brillant et museau peint. Un objet de brocante remis au goût du jour.'],
            ['curiosites', 'Stégosaure des falaises', '1627874458566-20503b954fde', 1890, 30, false, 12, '1/40', 'PVC peint', 'Stégosaure à l\'échelle, plaques dorsales détaillées. Solide et sans pièce fragile, il convient aussi aux enfants dès 6 ans.'],
            ['curiosites', 'Mannequin d\'atelier', '1774227738455-99f7a75f8257', 1490, 40, false, 30, '1/6', 'Hêtre massif articulé', 'Mannequin articulé en bois, utilisé par les dessinateurs pour étudier les poses. Il tient debout seul et se plie dans toutes les positions.'],
        ];

        $products = [];
        foreach ($catalogue as [$category, $name, $photo, $price, $stock, $featured, $height, $scale, $material, $description]) {
            $product = (new Product())
                ->setCategory($categories[$category])
                ->setName($name)
                ->setImageUrl('https://images.unsplash.com/photo-' . $photo . '?w=900&q=80&auto=format&fit=crop')
                ->setPriceCents($price)
                ->setStock($stock)
                ->setFeatured($featured)
                ->setHeightCm($height)
                ->setScale($scale)
                ->setMaterial($material)
                ->setDescription($description);
            $manager->persist($product);
            $products[] = $product;
        }

        $client = $this->user('client@figurama.test', 'Camille', 'Dubois');
        $admin = $this->user('admin@figurama.test', 'Alex', 'Martin')->setRoles(['ROLE_ADMIN']);
        $manager->persist($client);
        $manager->persist($admin);

        // A few past orders, so the account page and the admin dashboard are not empty.
        foreach ([
            [[0 => 1, 13 => 2], OrderStatus::Delivered],
            [[6 => 1], OrderStatus::Shipped],
            [[11 => 1, 9 => 3], OrderStatus::Paid],
        ] as [$lines, $status]) {
            $order = (new Order($client))
                ->setShippingName('Camille Dubois')
                ->setShippingAddress('Rue des Bouchers 12')
                ->setShippingPostalCode('1000')
                ->setShippingCity('Bruxelles')
                ->setShippingCountry('BE');
            foreach ($lines as $index => $quantity) {
                $order->addItem($products[$index], $quantity);
            }
            foreach ([OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered] as $step) {
                $order->changeStatus($step);
                if ($step === $status) {
                    break;
                }
            }
            $manager->persist($order);
        }

        $manager->flush();
    }

    private function user(string $email, string $firstName, string $lastName): User
    {
        $user = (new User())->setEmail($email)->setFirstName($firstName)->setLastName($lastName);

        return $user->setPassword($this->hasher->hashPassword($user, 'password'));
    }
}
