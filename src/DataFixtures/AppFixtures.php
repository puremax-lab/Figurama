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
            'fantasy' => ['Fantasy', '🐉'],
            'science-fiction' => ['Science-fiction', '🚀'],
            'heros' => ['Héros', '🦸'],
            'animaux' => ['Animaux', '🦊'],
            'retro-gaming' => ['Rétro gaming', '👾'],
        ] as $slug => [$name, $icon]) {
            $categories[$slug] = new Category($name, $slug, $icon);
            $manager->persist($categories[$slug]);
        }

        // [category, name, icon, background, price in cents, stock, featured, description]
        $catalogue = [
            ['fantasy', 'Dragon des cendres', '🐉', '#fde2d2', 4990, 6, true, 'Dragon aux ailes déployées, sculpté à la main et peint en dégradés de rouge et de gris. Socle en résine imitation roche volcanique. Hauteur 22 cm.'],
            ['fantasy', 'Mage de la tour d\'argent', '🧙', '#e3e0fb', 3490, 12, false, 'Magicien en longue robe étoilée, bâton levé. Peinture détaillée avec effets de lumière sur le cristal. Hauteur 15 cm.'],
            ['fantasy', 'Licorne des brumes', '🦄', '#fbe3f1', 2990, 2, false, 'Licorne nacrée sur un nuage de résine translucide. Finition irisée. Hauteur 14 cm. Dernières pièces de la série.'],
            ['fantasy', 'Chevalier de l\'aube', '⚔️', '#e6ecf3', 3990, 9, false, 'Chevalier en armure complète, épée et bouclier gravé. Métal patiné et cape en tissu véritable. Hauteur 18 cm.'],
            ['science-fiction', 'Robot explorateur R-7', '🤖', '#dff3f6', 3990, 15, true, 'Robot articulé aux bras et à la tête mobiles, yeux lumineux à LED (piles fournies). Hauteur 20 cm.'],
            ['science-fiction', 'Fusée Odyssée', '🚀', '#e3e9fb', 2490, 20, false, 'Maquette de fusée rétro-futuriste sur socle lunaire. Peinture métallisée. Hauteur 25 cm.'],
            ['science-fiction', 'Astronaute en apesanteur', '🧑‍🚀', '#e9e7f5', 2790, 0, false, 'Astronaute suspendu à son câble au-dessus d\'un mini-satellite. Hauteur 16 cm. Bientôt de retour en stock.'],
            ['science-fiction', 'Visiteur de Zeta', '👽', '#e2f6e4', 1990, 25, false, 'Petit extraterrestre sympathique avec sa soucoupe. Résine phosphorescente qui brille dans le noir. Hauteur 10 cm.'],
            ['heros', 'Super-héroïne Éclair', '🦸', '#fff0d6', 3490, 7, true, 'Héroïne en plein envol, cape au vent et poing levé. Socle façon toit d\'immeuble. Hauteur 19 cm.'],
            ['heros', 'Ninja de minuit', '🥷', '#e4e4ea', 2990, 3, false, 'Ninja en position de garde avec ses deux sabres. Tenue noire mate et détails rouges. Hauteur 15 cm.'],
            ['heros', 'Détective masqué', '🕵️', '#ece6dd', 2690, 11, false, 'Détective en imperméable et chapeau, loupe à la main. Socle pavé de ruelle. Hauteur 16 cm.'],
            ['animaux', 'Renard des neiges', '🦊', '#fde9dc', 2290, 18, true, 'Renard roux enroulé dans la neige, fourrure peinte poil par poil. Hauteur 9 cm.'],
            ['animaux', 'Panda gourmand', '🐼', '#eef1ec', 1890, 30, false, 'Panda assis avec sa tige de bambou. Résine douce au toucher. Hauteur 11 cm.'],
            ['animaux', 'Hibou érudit', '🦉', '#efe6d8', 2190, 1, false, 'Hibou à lunettes perché sur une pile de livres anciens. Hauteur 12 cm. Dernière pièce.'],
            ['retro-gaming', 'Fantôme pixel', '👾', '#e7e2fb', 1490, 40, false, 'Personnage pixel art en volume, couleurs vives et finition brillante. Hauteur 8 cm.'],
            ['retro-gaming', 'Manette arcade géante', '🕹️', '#fde3e3', 3290, 8, false, 'Réplique d\'un joystick d\'arcade avec boutons qui s\'enfoncent. Hauteur 14 cm.'],
        ];

        $products = [];
        foreach ($catalogue as [$category, $name, $icon, $color, $price, $stock, $featured, $description]) {
            $product = (new Product())
                ->setCategory($categories[$category])
                ->setName($name)
                ->setIcon($icon)
                ->setColor($color)
                ->setPriceCents($price)
                ->setStock($stock)
                ->setFeatured($featured)
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
            [[0 => 1, 11 => 2], OrderStatus::Delivered],
            [[4 => 1], OrderStatus::Shipped],
            [[8 => 1, 14 => 3], OrderStatus::Paid],
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
