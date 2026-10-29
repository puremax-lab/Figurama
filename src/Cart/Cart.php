<?php

namespace App\Cart;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Shopping cart kept in the session as [product id => quantity].
 * Quantities are always kept between 1 and the stock available.
 */
final class Cart
{
    private const KEY = 'cart';
    public const MAX_PER_PRODUCT = 10;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
    ) {
    }

    /** @return int the quantity actually in the cart, which can be lower than asked when stock is short */
    public function add(Product $product, int $quantity = 1): int
    {
        $items = $this->items();

        return $this->set($product, ($items[$product->getId()] ?? 0) + $quantity);
    }

    public function set(Product $product, int $quantity): int
    {
        $items = $this->items();
        $quantity = min($quantity, self::MAX_PER_PRODUCT, $product->isActive() ? $product->getStock() : 0);
        if ($quantity < 1) {
            unset($items[$product->getId()]);
        } else {
            $items[$product->getId()] = $quantity;
        }
        $this->save($items);

        return max(0, $quantity);
    }

    public function remove(Product $product): void
    {
        $items = $this->items();
        unset($items[$product->getId()]);
        $this->save($items);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * Lines with up-to-date products. Products that no longer exist or are no longer
     * sold are dropped, and quantities above the current stock are lowered.
     *
     * @return list<CartLine>
     */
    public function lines(): array
    {
        $items = $this->items();
        $products = $this->products->findByIds(array_keys($items));

        $lines = [];
        $cleaned = [];
        foreach ($items as $id => $quantity) {
            $product = $products[$id] ?? null;
            $quantity = $product?->isActive() ? min($quantity, $product->getStock()) : 0;
            if ($quantity > 0) {
                $lines[] = new CartLine($product, $quantity);
                $cleaned[$id] = $quantity;
            }
        }
        if ($cleaned !== $items) {
            $this->save($cleaned);
        }

        return $lines;
    }

    public function totalCents(): int
    {
        return array_sum(array_map(fn (CartLine $line) => $line->getTotalCents(), $this->lines()));
    }

    /** Number of articles, for the badge in the header (no database query). */
    public function count(): int
    {
        return array_sum($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    /** @return array<int, int> */
    private function items(): array
    {
        return $this->requestStack->getSession()->get(self::KEY, []);
    }

    private function save(array $items): void
    {
        $this->requestStack->getSession()->set(self::KEY, $items);
    }
}
