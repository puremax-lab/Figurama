<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Review> */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /** @return list<Review> */
    public function forProduct(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('a')
            ->join('r.author', 'a')
            ->where('r.product = :product')
            ->setParameter('product', $product)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<int, array{average: float, count: int}> rating of every reviewed product, by product id */
    public function statsByProduct(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.product) AS product', 'AVG(r.rating) AS average', 'COUNT(r.id) AS total')
            ->groupBy('r.product')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row['product']] = ['average' => round((float) $row['average'], 1), 'count' => (int) $row['total']];
        }

        return $stats;
    }

    /** Only customers who received a paid order with this piece may review it, once. */
    public function canReview(User $customer, Product $product): bool
    {
        $bought = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(OrderItem::class, 'i')
            ->join('i.order', 'o')
            ->where('i.product = :product AND o.customer = :customer AND o.status IN (:sold)')
            ->setParameter('product', $product)
            ->setParameter('customer', $customer)
            ->setParameter('sold', OrderStatus::sold())
            ->getQuery()
            ->getSingleScalarResult();

        return $bought > 0 && $this->count(['product' => $product, 'author' => $customer]) === 0;
    }
}
