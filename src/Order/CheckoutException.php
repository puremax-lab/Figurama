<?php

namespace App\Order;

/** A problem the customer can fix (empty cart, product out of stock). */
final class CheckoutException extends \RuntimeException
{
    /** @param array<string, mixed> $parameters values of the translated message */
    public function __construct(string $message, public readonly array $parameters = [])
    {
        parent::__construct($message);
    }
}
