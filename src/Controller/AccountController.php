<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Order\OrderWorkflow;
use App\Repository\OrderRepository;
use App\Security\OrderVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/compte')]
final class AccountController extends AbstractController
{
    #[Route('', name: 'app_account')]
    public function index(OrderRepository $orders, #[CurrentUser] User $user): Response
    {
        return $this->render('account/index.html.twig', ['orders' => $orders->forCustomer($user)]);
    }

    #[Route('/commandes/{reference}', name: 'app_account_order')]
    public function order(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order): Response
    {
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);

        return $this->render('account/order.html.twig', ['order' => $order]);
    }

    /** A customer can cancel an order that is not paid yet; the stock is given back. */
    #[Route('/commandes/{reference}/annuler', name: 'app_account_order_cancel', methods: ['POST'])]
    public function cancel(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, Request $request, OrderWorkflow $workflow): Response
    {
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);
        if (!$this->isCsrfTokenValid('cancel' . $order->getReference(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ($order->getStatus() === OrderStatus::Pending) {
            $workflow->changeStatus($order, OrderStatus::Cancelled);
            $this->addFlash('success', 'Commande annulée.');
        }

        return $this->redirectToRoute('app_account_order', ['reference' => $order->getReference()]);
    }
}
