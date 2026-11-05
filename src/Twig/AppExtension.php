<?php

namespace App\Twig;

use App\Cart\Cart;
use App\Entity\Product;
use App\Payment\StripeClient;
use App\Review\Ratings;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{

    public function __construct(
        private readonly Cart $cart,
        private readonly StripeClient $stripe,
        private readonly Ratings $ratings,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** @return array{average: float, count: int}|null */
    #[AsTwigFunction('rating')]
    public function rating(Product $product): ?array
    {
        return $this->ratings->for($product);
    }

    /** 4.4 → "★★★★☆" (rounded to the nearest star) */
    #[AsTwigFilter('stars')]
    public function stars(float $average): string
    {
        $full = (int) round($average);

        return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    }

    /** 2490 → "24,90 €" in French, "€24.90" in English */
    #[AsTwigFilter('price')]
    public function price(int $cents): string
    {
        $locale = $this->translator->getLocale() === 'en' ? 'en_IE' : 'fr_BE';

        return (new \NumberFormatter($locale, \NumberFormatter::CURRENCY))->formatCurrency($cents / 100, 'EUR');
    }

    /** 4.5 → "4,5" in French, "4.5" in English */
    #[AsTwigFilter('decimal')]
    public function decimal(float $number): string
    {
        return number_format($number, 1, $this->translator->getLocale() === 'en' ? '.' : ',', '');
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
        return $this->translator->trans(['BE' => 'Belgique', 'FR' => 'France', 'LU' => 'Luxembourg', 'NL' => 'Pays-Bas'][$code] ?? $code);
    }
}
