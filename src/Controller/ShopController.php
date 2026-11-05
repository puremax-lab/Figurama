<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewForm;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatorBagInterface;

use function Symfony\Component\Translation\t;

final class ShopController extends AbstractController
{
    private const PER_PAGE = 12;

    #[Route('/', name: 'app_home')]
    public function home(ProductRepository $products, CategoryRepository $categories): Response
    {
        return $this->render('shop/home.html.twig', [
            'featured' => $products->featured(4),
            'categories' => $categories->withProductCount(),
        ]);
    }

    #[Route('/boutique', name: 'app_shop')]
    public function index(Request $request, ProductRepository $products, CategoryRepository $categories, #[Autowire(service: 'translator')] TranslatorBagInterface $translations): Response
    {
        $category = $categories->findOneBy(['slug' => $request->query->getString('categorie')]);
        $query = mb_substr(trim($request->query->getString('q')), 0, 60);
        $sort = array_key_exists($request->query->getString('tri'), ProductRepository::SORTS) ? $request->query->getString('tri') : 'nouveautes';
        $inStock = $request->query->getBoolean('dispo');
        $page = max(1, $request->query->getInt('page', 1));

        $results = $products->search($category, $query, $sort, $inStock, $page, self::PER_PAGE, $this->frenchTextsMatching($query, $request, $translations));
        $pages = max(1, (int) ceil(count($results) / self::PER_PAGE));
        $nextUrl = $page < $pages
            ? $this->generateUrl('app_shop', ['page' => $page + 1] + $request->query->all())
            : null;

        // Infinite scroll: assets/app.js asks for the next page and only needs the new cards.
        if ($request->headers->get('X-Requested-With') === 'fetch') {
            return $this->render('shop/_page.html.twig', ['products' => $results, 'next_url' => $nextUrl]);
        }

        return $this->render('shop/index.html.twig', [
            'next_url' => $nextUrl,
            'products' => $results,
            'total' => count($results),
            'categories' => $categories->withProductCount(),
            'current_category' => $category,
            'query' => $query,
            'sort' => $sort,
            'in_stock' => $inStock,
            'page' => min($page, $pages),
            'pages' => $pages,
        ]);
    }

    /**
     * The catalogue is stored in French: in English, a search also looks in its translations.
     * @return list<string> the French texts whose English version contains the query
     */
    private function frenchTextsMatching(string $query, Request $request, TranslatorBagInterface $translations): array
    {
        if ($query === '' || $request->getLocale() !== 'en') {
            return [];
        }
        $texts = array_filter(
            $translations->getCatalogue('en')->all('catalogue'),
            static fn (string $english) => mb_stripos($english, $query) !== false,
        );

        return array_map('strval', array_keys($texts));
    }

    /** Product page. Customers who bought the piece can also post their review here. */
    #[Route('/produit/{slug}', name: 'app_product', methods: ['GET', 'POST'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
        Request $request,
        ProductRepository $products,
        ReviewRepository $reviews,
        EntityManagerInterface $em,
    ): Response {
        if (!$product->isActive()) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        $form = null;
        if ($user instanceof User && $reviews->canReview($user, $product)) {
            $review = new Review($product, $user);
            $form = $this->createForm(ReviewForm::class, $review, ['action' => $this->generateUrl('app_product', ['slug' => $product->getSlug()]) . '#avis']);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($review);
                $em->flush();
                $this->addFlash('success', t('Merci, votre avis est publié.'));

                return $this->redirect($this->generateUrl('app_product', ['slug' => $product->getSlug()]) . '#avis');
            }
        } elseif ($request->isMethod('POST')) {
            throw $this->createAccessDeniedException('Seuls les clients qui ont reçu cette pièce peuvent la noter.');
        }

        return $this->render('shop/show.html.twig', [
            'product' => $product,
            'similar' => $products->similar($product, 4),
            'reviews' => $reviews->forProduct($product),
            'review_form' => $form,
        ], new Response(status: $form?->isSubmitted() ? 422 : 200));
    }

    #[Route('/mentions-legales', name: 'app_legal', methods: ['GET'])]
    public function legal(Request $request): Response
    {
        return $this->render($request->getLocale() === 'en' ? 'shop/legal.en.html.twig' : 'shop/legal.html.twig');
    }
}
