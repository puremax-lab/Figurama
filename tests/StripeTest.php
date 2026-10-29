<?php

namespace App\Tests;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Payment\StripeClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class StripeTest extends AppWebTestCase
{
    private const SECRET = 'whsec_test_secret';

    private function sign(string $payload, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return sprintf('t=%d,v1=%s', $timestamp, hash_hmac('sha256', $timestamp . '.' . $payload, self::SECRET));
    }

    public function testAcceptsAValidSignatureOnly(): void
    {
        $stripe = new StripeClient(new MockHttpClient(), '', self::SECRET);
        $payload = '{"type":"checkout.session.completed"}';

        $this->assertSame('checkout.session.completed', $stripe->verifyWebhook($payload, $this->sign($payload))['type']);

        foreach ([
            'wrong secret' => sprintf('t=%d,v1=%s', time(), hash_hmac('sha256', time() . '.' . $payload, 'other')),
            'too old' => $this->sign($payload, time() - 600),
            'malformed' => 'nonsense',
        ] as $case => $header) {
            try {
                $stripe->verifyWebhook($payload, $header);
                $this->fail("Accepted a $case signature");
            } catch (\UnexpectedValueException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testCreatesACheckoutSessionWithTheOrderLines(): void
    {
        $sent = null;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$sent) {
            $sent = ['method' => $method, 'url' => $url, 'body' => $options['body']];

            return new MockResponse('{"id":"cs_test_123","url":"https://checkout.stripe.com/c/pay/cs_test_123"}');
        });
        $stripe = new StripeClient($http, 'sk_test_x', self::SECRET);
        $order = $this->em->getRepository(Order::class)->findOneBy(['status' => OrderStatus::Paid]);

        $session = $stripe->createCheckoutSession($order, 'https://shop.test/ok', 'https://shop.test/ko');

        $this->assertSame('cs_test_123', $session['id']);
        $this->assertSame('POST', $sent['method']);
        $this->assertSame('https://api.stripe.com/v1/checkout/sessions', $sent['url']);
        parse_str($sent['body'], $body);
        $this->assertSame('payment', $body['mode']);
        $this->assertSame($order->getReference(), $body['client_reference_id']);
        $this->assertSame('5490', $body['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('eur', $body['line_items'][0]['price_data']['currency']);
    }

    public function testWebhookMarksTheOrderAsPaid(): void
    {
        $this->loginAs('client@figurama.test');
        $this->addToCart('Mannequin d\'atelier');
        $this->checkout();
        $order = $this->latestOrder();

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_test_456',
            'client_reference_id' => $order->getReference(),
            'payment_status' => 'paid',
        ]]]);
        $this->client->request('POST', '/stripe/webhook', server: ['HTTP_STRIPE_SIGNATURE' => $this->sign($payload)], content: $payload);

        $this->assertResponseIsSuccessful();
        $paid = $this->latestOrder();
        $this->assertSame(OrderStatus::Paid, $paid->getStatus());
        $this->assertSame('cs_test_456', $paid->getStripeSessionId());
    }

    public function testWebhookRefusesUnsignedRequests(): void
    {
        $this->client->request('POST', '/stripe/webhook', server: ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=abc'], content: '{}');

        $this->assertResponseStatusCodeSame(400);
    }
}
