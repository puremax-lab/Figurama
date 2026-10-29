<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Order> */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /** @return list<Order> */
    public function forCustomer(User $customer): array
    {
        return $this->findBy(['customer' => $customer], ['createdAt' => 'DESC']);
    }

    /** @return list<Order> */
    public function latest(?OrderStatus $status, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('o')
            ->addSelect('u')
            ->join('o.customer', 'u')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit);
        if ($status) {
            $qb->where('o.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function revenueCents(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.totalCents), 0)')
            ->where('o.status IN (:sold)')
            ->setParameter('sold', OrderStatus::sold())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<string, int> number of orders per status value */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status', 'COUNT(o.id) AS total')
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        $counts = array_fill_keys(array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases()), 0);
        foreach ($rows as $row) {
            $status = $row['status'] instanceof OrderStatus ? $row['status']->value : $row['status'];
            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }
}
