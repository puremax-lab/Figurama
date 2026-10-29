<?php

namespace App\Review;

use App\Entity\Product;
use App\Repository\ReviewRepository;

/**
 * Average rating of the products, loaded in one query the first time a page asks
 * for it, then reused for every card of the page.
 */
final class Ratings
{
    /** @var array<int, array{average: float, count: int}>|null */
    private ?array $stats = null;

    public function __construct(private readonly ReviewRepository $reviews)
    {
    }

    /** @return array{average: float, count: int}|null */
    public function for(Product $product): ?array
    {
        $this->stats ??= $this->reviews->statsByProduct();

        return $this->stats[$product->getId()] ?? null;
    }
}
