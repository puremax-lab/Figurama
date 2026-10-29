<?php

namespace App\Cart;

use App\Entity\Product;

final class CartLine
{
    public function __construct(
        public readonly Product $product,
        public readonly int $quantity,
    ) {
    }

    public function getTotalCents(): int
    {
        return (int) $this->product->getPriceCents() * $this->quantity;
    }
}
