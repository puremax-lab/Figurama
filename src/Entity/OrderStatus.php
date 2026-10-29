<?php

namespace App\Entity;

/**
 * Life cycle of an order:
 *   pending → paid → shipped → delivered
 *   pending or paid → cancelled (the stock is given back)
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de paiement',
            self::Paid => 'Payée',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    /** CSS class of the status badge. */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'primary',
            self::Shipped => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'secondary',
        };
    }

    public function canBecome(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        }, true);
    }

    /** Statuses that count as revenue. */
    public static function sold(): array
    {
        return [self::Paid, self::Shipped, self::Delivered];
    }
}
