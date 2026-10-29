<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function index(Request $request, ProductRepository $products, CategoryRepository $categories): Response
    {
        $category = $categories->findOneBy(['slug' => $request->query->getString('categorie')]);
        $query = mb_substr(trim($request->query->getString('q')), 0, 60);
        $sort = array_key_exists($request->query->getString('tri'), ProductRepository::SORTS) ? $request->query->getString('tri') : 'nouveautes';
        $inStock = $request->query->getBoolean('dispo');
        $page = max(1, $request->query->getInt('page', 1));

        $results = $products->search($category, $query, $sort, $inStock, $page, self::PER_PAGE);
        $pages = max(1, (int) ceil(count($results) / self::PER_PAGE));

        return $this->render('shop/index.html.twig', [
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

    #[Route('/produit/{slug}', name: 'app_product')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, ProductRepository $products): Response
    {
        if (!$product->isActive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('shop/show.html.twig', [
            'product' => $product,
            'similar' => $products->similar($product, 4),
        ]);
    }
}
