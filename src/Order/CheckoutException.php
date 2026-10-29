<?php

namespace App\Order;

/** A problem the customer can fix (empty cart, product out of stock). */
final class CheckoutException extends \RuntimeException
{
}
