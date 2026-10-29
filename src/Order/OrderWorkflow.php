<?php

namespace App\Order;

use App\Cart\Cart;
use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates orders and moves them through their statuses.
 *
 * Stock is reserved when the order is placed, so two customers cannot buy the
 * last item at the same time, and it is given back if the order is cancelled.
 */
final class OrderWorkflow
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Cart $cart,
    ) {
    }

    /**
     * Turns the cart into an order for this customer, with the shipping details
     * already filled in $order.
     *
     * @throws CheckoutException when the cart is empty or a product is out of stock
     */
    public function place(Order $order): Order
    {
        $lines = $this->cart->lines();
        if ($lines === []) {
            throw new CheckoutException('Votre panier est vide.');
        }

        $this->em->wrapInTransaction(function () use ($order, $lines): void {
            foreach ($lines as $line) {
                // Re-read the stock inside the transaction, with a lock where the database supports it.
                $this->em->refresh($line->product, LockMode::PESSIMISTIC_WRITE);
                if ($line->product->getStock() < $line->quantity) {
                    throw new CheckoutException(sprintf('Il ne reste que %d « %s » en stock.', $line->product->getStock(), $line->product->getName()));
                }
                $line->product->decreaseStock($line->quantity);
                $order->addItem($line->product, $line->quantity);
            }
            $this->em->persist($order);
        });

        $this->cart->clear();

        return $order;
    }

    /** Safe to call twice (Stripe can send the same event more than once). */
    public function markPaid(Order $order, ?string $stripeSessionId = null): void
    {
        if ($order->getStatus() !== OrderStatus::Pending) {
            return;
        }
        $order->changeStatus(OrderStatus::Paid);
        if ($stripeSessionId) {
            $order->setStripeSessionId($stripeSessionId);
        }
        $this->em->flush();
    }

    /** Changes the status and gives the stock back when the order is cancelled. */
    public function changeStatus(Order $order, OrderStatus $next): void
    {
        $order->changeStatus($next);
        if ($next === OrderStatus::Cancelled) {
            foreach ($order->getItems() as $item) {
                $item->getProduct()?->increaseStock($item->getQuantity());
            }
        }
        $this->em->flush();
    }

    public function startCheckoutFor(User $customer): Order
    {
        $order = new Order($customer);
        $order->setShippingName($customer->getFullName());

        return $order;
    }
}
