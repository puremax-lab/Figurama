<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Category> */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** @return list<array{category: Category, products: int}> categories with their number of products on sale */
    public function withProductCount(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c AS category', 'COUNT(p.id) AS products')
            ->leftJoin(Product::class, 'p', 'WITH', 'p.category = c AND p.active = true')
            ->groupBy('c.id')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();

        return array_map(fn (array $row) => ['category' => $row['category'], 'products' => (int) $row['products']], $rows);
    }
}
