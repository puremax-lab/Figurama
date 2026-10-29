<?php

namespace App\Tests;

use App\Entity\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function testFollowsTheOrderLifeCycle(): void
    {
        $this->assertTrue(OrderStatus::Pending->canBecome(OrderStatus::Paid));
        $this->assertTrue(OrderStatus::Paid->canBecome(OrderStatus::Shipped));
        $this->assertTrue(OrderStatus::Shipped->canBecome(OrderStatus::Delivered));
        $this->assertTrue(OrderStatus::Paid->canBecome(OrderStatus::Cancelled));
    }

    public function testRefusesSkippingStepsOrGoingBack(): void
    {
        $this->assertFalse(OrderStatus::Pending->canBecome(OrderStatus::Shipped));
        $this->assertFalse(OrderStatus::Shipped->canBecome(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Delivered->canBecome(OrderStatus::Paid));
        $this->assertFalse(OrderStatus::Cancelled->canBecome(OrderStatus::Paid));
    }
}
