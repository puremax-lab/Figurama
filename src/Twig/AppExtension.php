<?php

namespace App\Twig;

use App\Cart\Cart;
use App\Payment\StripeClient;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{
    private \NumberFormatter $formatter;

    public function __construct(
        private readonly Cart $cart,
        private readonly StripeClient $stripe,
    ) {
        $this->formatter = new \NumberFormatter('fr_BE', \NumberFormatter::CURRENCY);
    }

    /** 2490 → "24,90 €" */
    #[AsTwigFilter('price')]
    public function price(int $cents): string
    {
        return $this->formatter->formatCurrency($cents / 100, 'EUR');
    }

    #[AsTwigFunction('cart_count')]
    public function cartCount(): int
    {
        return $this->cart->count();
    }

    #[AsTwigFunction('stripe_enabled')]
    public function stripeEnabled(): bool
    {
        return $this->stripe->isEnabled();
    }

    #[AsTwigFunction('country_name')]
    public function countryName(string $code): string
    {
        return ['BE' => 'Belgique', 'FR' => 'France', 'LU' => 'Luxembourg', 'NL' => 'Pays-Bas'][$code] ?? $code;
    }
}
