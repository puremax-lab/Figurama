<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Entity\Review;
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
            ['fantasy', 'Licorne de porcelaine', '1574607407517-cd664b1504f5', 2990, 11, false, 13, '1/12', 'Porcelaine émaillée, filets d\'or', 'Licorne couchée en porcelaine blanche, crinière et sabots rehaussés à la feuille d\'or. Une pièce douce et lumineuse pour une étagère ou une chambre.'],
            ['fantasy', 'Mage de bois sculpté', '1672884311128-0b25cb10be1d', 5890, 3, false, 26, '1/7', 'Tilleul sculpté à la main', 'Buste de vieux mage au chapeau pointu, taillé dans un seul bloc de tilleul. Chaque pli de la barbe est sculpté au ciseau, puis ciré.'],
            ['fantasy', 'Fée des sous-bois', '1668687897308-4ab4523b91a0', 2490, 15, false, 12, '1/14', 'Résine peinte à la main', 'Petite fée bleue assise sur sa branche, ailes translucides peintes en dégradé. Elle se pose aussi bien dans une vitrine que dans une jardinière.'],
            ['fantasy', 'Griffon de pierre', '1643324759759-d765bed49091', 4290, 5, false, 22, '1/20', 'Pierre reconstituée', 'Griffon aux ailes déployées, moulé en pierre reconstituée comme les gargouilles d\'autrefois. Lourd et stable, il résiste aussi à l\'extérieur.'],
            ['fantasy', 'Sirène de bronze', '1751315874123-f12fdc12130c', 3890, 0, false, 9, '1/18', 'Bronze patiné', 'Sirène assise, la queue enroulée, coulée en bronze puis patinée à la main. Rupture de stock : la prochaine fonte est prévue cet hiver.'],
            ['fantasy', 'Citadelle des neiges', '1613038170440-eaf774d1cfbe', 7990, 2, true, 28, '1/200', 'Résine, socle en bois', 'Maquette d\'une forteresse de montagne prise dans la neige, avec ses remparts, ses tours et ses toits d\'ardoise. Chaque fenêtre est peinte à la main.'],
            ['fantasy', 'Fée aux fleurs', '1612014862634-bdd7b1a7a8e6', 2190, 20, false, 15, '1/12', 'Céramique peinte', 'Fée debout tenant un bouquet, en céramique aux tons pastel. Une figurine délicate, à poser à l\'abri des chocs.'],
            ['retro-futur', 'Manège-fusée en tôle', '1681767097410-b04711e01b77', 5990, 4, true, 25, '1/12', 'Tôle lithographiée, mécanisme à clé', 'Manège de fête foraine à remontoir : les nacelles tournent autour de la fusée centrale quand on tourne la clé. Reproduction fidèle d\'un jouet des années 50.'],
            ['retro-futur', 'Soucoupe volante S-1', '1781543423332-a2c6e84d1530', 3690, 8, false, 14, '1/10', 'Plastique et métal chromé', 'Soucoupe rouge et blanche sur trois pieds, hublot central en verre. Le dôme pivote pour regarder dans toutes les directions.'],
            ['retro-futur', 'Robot à antenne rouge', '1680714794307-f74f962391d9', 2790, 0, false, 15, '1/6', 'Tôle lithographiée', 'Robot rouge vif à la tête crénelée et à l\'antenne en ressort. Victime de son succès : rupture de stock, retour prévu dans trois semaines.'],
            ['retro-futur', 'Robot rouge à cadran', '1681767097415-9210f6910a2d', 2490, 13, false, 14, '1/6', 'Tôle lithographiée', 'Petit robot trapu au cadran peint sur la poitrine. Les bras pivotent et les pieds sont lestés pour qu\'il tienne bien debout.'],
            ['retro-futur', 'Automobile 1910 en tôle', '1764350769219-6b5814dc6be8', 4890, 6, false, 17, '1/18', 'Tôle peinte et laiton', 'Voiture de course des années 1910, carrosserie rouge et phares en laiton. Les roues à rayons tournent pour de vrai.'],
            ['retro-futur', 'Astronaute au croissant de lune', '1620428268482-cf1851a36764', 2290, 22, false, 10, '1/20', 'Résine laquée', 'Petit astronaute assis sur un croissant de lune doré. Parfait sur une table de nuit ou en cadeau de naissance.'],
            ['retro-futur', 'Escouade de robots', '1582571352032-448f7928eca3', 6490, 3, false, 18, '1/8', 'Tôle lithographiée, coffret de cinq', 'Coffret de cinq robots en tôle, chacun dans sa couleur. Vendus ensemble, dans une boîte illustrée comme celles des années 60.'],
            ['curiosites', 'Chat en mosaïque', '1745231939879-0adf4d236695', 3290, 7, false, 20, '1/5', 'Céramique et éclats de faïence', 'Chat en marche recouvert d\'une mosaïque de faïence colorée posée à la main. Aucun chat n\'a exactement les mêmes motifs.'],
            ['curiosites', 'Cavalier de bronze', '1785516330744-d6cf3f4742b3', 8990, 1, false, 24, '1/16', 'Bronze, fonte à la cire perdue', 'Statuette d\'un chevalier en armure sur son cheval, d\'après une pièce de musée. Fonte à la cire perdue, patine brune. Dernier exemplaire.'],
            ['curiosites', 'Éléphant de bois verni', '1739281468485-dfb4231a004d', 2690, 16, false, 15, '1/40', 'Teck verni', 'Éléphant trompe levée, sculpté dans le teck puis verni. Le veinage du bois change d\'une pièce à l\'autre.'],
            ['curiosites', 'Éléphant d\'apparat', '1774463932181-63ce7b524303', 3490, 9, false, 16, '1/40', 'Bois peint et incrusté', 'Éléphant de cérémonie au caparaçon peint de motifs blancs. Un objet d\'artisanat à la fois coloré et très précis.'],
            ['curiosites', 'Grenouille couronnée', '1778869150560-344bf45ae747', 1990, 24, false, 11, '1/3', 'Céramique émaillée', 'Grenouille verte coiffée d\'une couronne d\'argent : il ne manque que le baiser. Émail brillant, à l\'intérieur comme au jardin.'],
            ['curiosites', 'Oiseau de verre soufflé', '1764774089002-34f3b15ff3a8', 3990, 5, false, 18, '1/2', 'Verre soufflé', 'Oiseau aux plumes ambrées, soufflé et étiré à la flamme par un verrier. Il accroche la lumière près d\'une fenêtre.'],
            ['curiosites', 'Petits soldats d\'antan', '1635875560469-2b94b774c187', 990, 60, false, 5, '1/72', 'Plastique souple', 'Sachet de vingt petits soldats en deux couleurs, comme ceux des cours de récréation. Pour jouer ou pour monter un diorama.'],
            ['curiosites', 'Maison de poupée victorienne', '1739133887954-ac18f17d1d5a', 12990, 2, true, 60, '1/12', 'Bois et carton, mobilier inclus', 'Grande maison de poupée à deux étages, façade crème et balcons ouvragés. Livrée avec son mobilier, à monter en une soirée.'],
            ['curiosites', 'Baleine de bois flotté', '1517505873137-e57bdf986679', 2490, 12, false, 6, '1/300', 'Bois sculpté et peint', 'Baleine bleu nuit taillée dans du bois flotté, ventre blanc et nageoires fines. Elle se pose ou s\'accroche au mur.'],
            ['curiosites', 'Tortue de laiton', '1781579326791-7c3a38efc847', 1590, 30, false, 4, '1/6', 'Laiton massif', 'Petite tortue en laiton massif, carapace ciselée. Lourde pour sa taille, elle sert aussi de presse-papier.'],
            ['curiosites', 'Poupées gigognes', '1672092590672-3feb81f3123a', 2890, 11, false, 14, '1/1', 'Tilleul tourné et peint', 'Trois poupées gigognes en bois tourné, peintes à la main de fleurs rouges et jaunes. Elles s\'emboîtent les unes dans les autres.'],
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

        // Demo customers who received their order and reviewed it: [first name, last name, [product index, rating, comment]...]
        $reviewers = [
            ['Léa', 'Moreau', [0, 5, 'Les écailles sont encore plus belles en vrai. Il trône sur mon bureau.'], [6, 4, 'Les deux robots sont superbes, la tôle fait vraiment vintage. Un peu plus petits que prévu.']],
            ['Samir', 'Haddad', [1, 5, 'Le dragonnet est adorable, ma fille l\'a adopté tout de suite.'], [11, 5, 'Une vraie pièce d\'exposition. Le laiton du casque est magnifique.'], [3, 4, 'Peinture très fine pour une si petite figurine. Livraison rapide.']],
            ['Jonas', 'Peeters', [0, 4, 'Très beau dragon, l\'orbe accroche bien la lumière. Emballage soigné.'], [9, 5, 'Parfait pour mon étagère spatiale, le socle lunaire est très réussi.'], [14, 4, 'Pratique pour dessiner, les articulations tiennent bien les poses.']],
        ];
        foreach ($reviewers as $i => [$firstName, $lastName]) {
            $customer = $this->user(strtolower($firstName) . '@figurama.test', $firstName, $lastName);
            $manager->persist($customer);
            $order = (new Order($customer))
                ->setShippingName("$firstName $lastName")
                ->setShippingAddress('Rue de la Station 8')
                ->setShippingPostalCode('4000')
                ->setShippingCity('Liège')
                ->setShippingCountry('BE');
            foreach (array_slice($reviewers[$i], 2) as [$index]) {
                $order->addItem($products[$index], 1);
            }
            foreach ([OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered] as $step) {
                $order->changeStatus($step);
            }
            $manager->persist($order);
            foreach (array_slice($reviewers[$i], 2) as $n => [$index, $rating, $comment]) {
                $review = (new Review($products[$index], $customer))
                    ->setRating($rating)
                    ->setComment($comment)
                    ->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', 3 + $i * 9 + $n * 2)));
                $manager->persist($review);
            }
        }
        $manager->persist((new Review($products[13], $client))->setRating(4)->setComment('Solide et bien peint, mon neveu ne le lâche plus.'));

        $manager->flush();
    }

    private function user(string $email, string $firstName, string $lastName): User
    {
        $user = (new User())->setEmail($email)->setFirstName($firstName)->setLastName($lastName);

        return $user->setPassword($this->hasher->hashPassword($user, 'password'));
    }
}
