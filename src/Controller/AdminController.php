<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Form\ProductForm;
use App\Order\OrderWorkflow;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(OrderRepository $orders, ProductRepository $products): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'revenue' => $orders->revenueCents(),
            'counts' => $orders->countByStatus(),
            'to_ship' => $orders->latest(OrderStatus::Paid, 10),
            'low_stock' => $products->lowStock(),
        ]);
    }

    #[Route('/produits', name: 'admin_products')]
    public function products(ProductRepository $products): Response
    {
        return $this->render('admin/products.html.twig', [
            'products' => $products->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/produits/nouveau', name: 'admin_product_new')]
    #[Route('/produits/{id}/modifier', name: 'admin_product_edit')]
    public function productForm(Request $request, EntityManagerInterface $em, ?Product $product = null): Response
    {
        $isNew = $product === null;
        $product ??= new Product();
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', $isNew ? 'Produit créé.' : 'Produit enregistré.');

            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/product_form.html.twig', ['form' => $form, 'product' => $product, 'is_new' => $isNew]);
    }

    #[Route('/commandes', name: 'admin_orders')]
    public function orders(Request $request, OrderRepository $orders): Response
    {
        $status = OrderStatus::tryFrom($request->query->getString('statut'));

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders->latest($status, 100),
            'current_status' => $status,
            'counts' => $orders->countByStatus(),
        ]);
    }

    #[Route('/commandes/{reference}', name: 'admin_order')]
    public function order(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order): Response
    {
        return $this->render('admin/order.html.twig', ['order' => $order]);
    }

    #[Route('/commandes/{reference}/statut', name: 'admin_order_status', methods: ['POST'])]
    public function changeStatus(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, Request $request, OrderWorkflow $workflow): Response
    {
        if (!$this->isCsrfTokenValid('status' . $order->getReference(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $next = OrderStatus::tryFrom($request->request->getString('status'));
        try {
            if ($next === null) {
                throw new \DomainException('Statut inconnu.');
            }
            $workflow->changeStatus($order, $next);
            $this->addFlash('success', sprintf('Commande %s : %s.', $order->getReference(), mb_strtolower($next->label())));
        } catch (\DomainException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_order', ['reference' => $order->getReference()]);
    }
}
