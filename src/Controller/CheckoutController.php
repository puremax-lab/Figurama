<?php

namespace App\Controller;

use App\Cart\Cart;
use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Form\ShippingForm;
use App\Order\CheckoutException;
use App\Order\OrderWorkflow;
use App\Payment\StripeClient;
use App\Security\OrderVoter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/commande')]
final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly OrderWorkflow $workflow,
        private readonly StripeClient $stripe,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** Step 1: shipping address, then the order is created and the stock reserved. */
    #[Route('', name: 'app_checkout')]
    public function checkout(Request $request, Cart $cart, #[CurrentUser] User $user): Response
    {
        $lines = $cart->lines();
        if ($lines === []) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_shop');
        }

        $order = $this->workflow->startCheckoutFor($user);
        $form = $this->createForm(ShippingForm::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->workflow->place($order);
            } catch (CheckoutException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->redirectToRoute('app_cart');
            }

            return $this->redirectToRoute('app_payment', ['reference' => $order->getReference()]);
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form,
            'lines' => $lines,
            'total' => $cart->totalCents(),
        ]);
    }

    /** Step 2: payment with Stripe Checkout, or the demo page when Stripe is not configured. */
    #[Route('/{reference}/paiement', name: 'app_payment')]
    public function payment(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);
        if ($order->getStatus() !== OrderStatus::Pending) {
            return $this->redirectToRoute('app_account_order', ['reference' => $order->getReference()]);
        }

        if ($this->stripe->isEnabled()) {
            try {
                $session = $this->stripe->createCheckoutSession(
                    $order,
                    $this->generateUrl('app_payment_success', ['reference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                    $this->generateUrl('app_account_order', ['reference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
                );
                $order->setStripeSessionId($session['id']);
                $this->em->flush();

                return $this->redirect($session['url']);
            } catch (\Throwable $e) {
                $logger->error('Stripe checkout failed: {message}', ['message' => $e->getMessage()]);
                $this->addFlash('danger', 'Le paiement est momentanément indisponible. Réessayez dans un instant.');

                return $this->redirectToRoute('app_account_order', ['reference' => $order->getReference()]);
            }
        }

        return $this->render('checkout/demo_payment.html.twig', ['order' => $order]);
    }

    /** Demo payment, used when no Stripe key is configured. */
    #[Route('/{reference}/paiement-demo', name: 'app_payment_demo', methods: ['POST'])]
    public function demoPayment(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, Request $request): Response
    {
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);
        if ($this->stripe->isEnabled() || !$this->isCsrfTokenValid('pay' . $order->getReference(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $this->workflow->markPaid($order);

        return $this->redirectToRoute('app_payment_success', ['reference' => $order->getReference()]);
    }

    /**
     * Page Stripe sends the customer back to. The payment is checked with the Stripe API,
     * so the order is confirmed even if the webhook arrives later.
     */
    #[Route('/{reference}/confirmation', name: 'app_payment_success')]
    public function success(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, Request $request): Response
    {
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);

        $sessionId = $request->query->getString('session_id');
        if ($this->stripe->isEnabled() && $order->getStatus() === OrderStatus::Pending && $sessionId === $order->getStripeSessionId()) {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);
            if (($session['payment_status'] ?? null) === 'paid') {
                $this->workflow->markPaid($order, $sessionId);
            }
        }

        return $this->render('checkout/success.html.twig', ['order' => $order]);
    }
}
