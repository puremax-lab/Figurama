<?php

namespace App\Payment;

use App\Entity\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Minimal Stripe client written with Symfony HttpClient: creates Checkout
 * sessions, reads them back and checks webhook signatures. Without a secret
 * key, the shop falls back to the demo payment page.
 */
final class StripeClient
{
    private const API = 'https://api.stripe.com/v1/';
    private const SIGNATURE_TOLERANCE = 300;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] private readonly string $secretKey,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')] private readonly string $webhookSecret,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->secretKey !== '';
    }

    /** @return array{id: string, url: string} */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $body = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $order->getReference(),
            'customer_email' => $order->getCustomer()->getEmail(),
            'metadata' => ['reference' => $order->getReference()],
            'line_items' => [],
        ];
        foreach ($order->getItems() as $item) {
            $body['line_items'][] = [
                'quantity' => $item->getQuantity(),
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $item->getUnitPriceCents(),
                    'product_data' => ['name' => $item->getProductName()],
                ],
            ];
        }

        $session = $this->request('POST', 'checkout/sessions', $body);

        return ['id' => $session['id'], 'url' => $session['url']];
    }

    public function retrieveCheckoutSession(string $id): array
    {
        return $this->request('GET', 'checkout/sessions/' . rawurlencode($id));
    }

    /**
     * Checks the Stripe-Signature header (HMAC-SHA256 of "timestamp.payload")
     * and returns the decoded event.
     *
     * @throws \UnexpectedValueException when the signature is missing, wrong or too old
     */
    public function verifyWebhook(string $payload, string $signatureHeader, ?int $now = null): array
    {
        if ($this->webhookSecret === '') {
            throw new \UnexpectedValueException('Webhook secret not configured.');
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }
        if ($timestamp === null || $signatures === []) {
            throw new \UnexpectedValueException('Malformed signature header.');
        }
        if (abs(($now ?? time()) - $timestamp) > self::SIGNATURE_TOLERANCE) {
            throw new \UnexpectedValueException('Signature too old.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhookSecret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
            }
        }

        throw new \UnexpectedValueException('Invalid signature.');
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $options = ['auth_bearer' => $this->secretKey];
        if ($body !== []) {
            // Stripe expects form-encoded bodies with nested keys: line_items[0][quantity]=2
            $options['body'] = http_build_query($body);
            $options['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
        }

        return $this->httpClient->request($method, self::API . $path, $options)->toArray();
    }
}
