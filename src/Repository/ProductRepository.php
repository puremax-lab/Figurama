<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
class ProductRepository extends ServiceEntityRepository
{
    public const SORTS = [
        'nouveautes' => ['p.createdAt', 'DESC'],
        'prix-croissant' => ['p.priceCents', 'ASC'],
        'prix-decroissant' => ['p.priceCents', 'DESC'],
        'nom' => ['p.name', 'ASC'],
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /** Products on sale matching the filters, one page at a time. */
    public function search(?Category $category, string $query, string $sort, bool $inStockOnly, int $page, int $perPage): Paginator
    {
        [$field, $direction] = self::SORTS[$sort] ?? self::SORTS['nouveautes'];

        $qb = $this->createQueryBuilder('p')
            ->addSelect('c')
            ->join('p.category', 'c')
            ->where('p.active = true')
            ->orderBy($field, $direction)
            ->addOrderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($category) {
            $qb->andWhere('p.category = :category')->setParameter('category', $category);
        }
        if ($query !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :q OR LOWER(p.description) LIKE :q')
                ->setParameter('q', '%' . addcslashes(mb_strtolower($query), '%_') . '%');
        }
        if ($inStockOnly) {
            $qb->andWhere('p.stock > 0');
        }

        return new Paginator($qb);
    }

    /** @return array<int, string> one photo per category id, used to illustrate the collections */
    public function coverImages(): array
    {
        $covers = [];
        foreach ($this->findBy(['active' => true], ['featured' => 'DESC', 'priceCents' => 'DESC']) as $product) {
            $covers[$product->getCategory()->getId()] ??= $product->getImageUrl();
        }

        return $covers;
    }

    /** @return list<Product> */
    public function featured(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c')
            ->join('p.category', 'c')
            ->where('p.active = true AND p.featured = true')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Product> other products of the same category */
    public function similar(Product $product, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = true AND p.category = :category AND p != :product')
            ->setParameter('category', $product->getCategory())
            ->setParameter('product', $product)
            ->orderBy('p.stock', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Product> products to restock, for the admin dashboard */
    public function lowStock(int $threshold = 3): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = true AND p.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stock')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $ids
     * @return array<int, Product> indexed by id
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p', 'p.id')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
