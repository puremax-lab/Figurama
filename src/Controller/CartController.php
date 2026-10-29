<?php

namespace App\Controller;

use App\Cart\Cart;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class CartController extends AbstractController
{
    public function __construct(private readonly Cart $cart)
    {
    }

    #[Route('', name: 'app_cart')]
    public function index(): Response
    {
        return $this->render('cart/index.html.twig', [
            'lines' => $this->cart->lines(),
            'total' => $this->cart->totalCents(),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request): RedirectResponse
    {
        $this->checkToken($request, 'cart');
        $wanted = max(1, $request->request->getInt('quantity', 1));
        $before = $this->cart->count();
        $this->cart->add($product, $wanted);

        if ($this->cart->count() === $before) {
            $this->addFlash('warning', sprintf('« %s » n\'est plus disponible en plus grande quantité.', $product->getName()));
        } else {
            $this->addFlash('success', sprintf('« %s » a été ajouté au panier.', $product->getName()));
        }

        return $this->redirectToRoute($request->request->get('back') === 'cart' ? 'app_cart' : 'app_product', ['slug' => $product->getSlug()]);
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(Product $product, Request $request): RedirectResponse
    {
        $this->checkToken($request, 'cart');
        $wanted = $request->request->getInt('quantity');
        $kept = $this->cart->set($product, $wanted);
        if ($wanted > 0 && $kept < $wanted) {
            $this->addFlash('warning', sprintf('Quantité limitée à %d pour « %s ».', $kept, $product->getName()));
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/retirer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Product $product, Request $request): RedirectResponse
    {
        $this->checkToken($request, 'cart');
        $this->cart->remove($product);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request): RedirectResponse
    {
        $this->checkToken($request, 'cart');
        $this->cart->clear();

        return $this->redirectToRoute('app_cart');
    }

    private function checkToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Jeton CSRF invalide.');
        }
    }
}
