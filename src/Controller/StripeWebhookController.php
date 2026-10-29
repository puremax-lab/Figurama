<?php

namespace App\Controller;

use App\Order\OrderWorkflow;
use App\Payment\StripeClient;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Receives Stripe events. Only signed requests are accepted. */
final class StripeWebhookController
{
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request, StripeClient $stripe, OrderRepository $orders, OrderWorkflow $workflow): Response
    {
        try {
            $event = $stripe->verifyWebhook($request->getContent(), (string) $request->headers->get('Stripe-Signature'));
        } catch (\UnexpectedValueException|\JsonException) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $session = $event['data']['object'];
            $order = $orders->findOneBy(['reference' => $session['client_reference_id'] ?? '']);
            if ($order && ($session['payment_status'] ?? '') === 'paid') {
                $workflow->markPaid($order, $session['id']);
            }
        }

        return new Response('ok');
    }
}
