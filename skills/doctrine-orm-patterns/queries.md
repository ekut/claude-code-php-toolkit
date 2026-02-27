# Queries

## DQL (Doctrine Query Language)

DQL operates on entities, not tables.

```php
// Simple query
$query = $em->createQuery(
    'SELECT p FROM App\Entity\Product p WHERE p.status = :status ORDER BY p.createdAt DESC'
);
$query->setParameter('status', ProductStatus::Active);
$products = $query->getResult();

// Single result
$product = $query->setMaxResults(1)->getOneOrNullResult();
```

### JOIN FETCH (N+1 prevention)

```php
// Load product with its category in a single query
$dql = 'SELECT p, c FROM App\Entity\Product p JOIN p.category c WHERE p.id = :id';
$product = $em->createQuery($dql)
    ->setParameter('id', $id)
    ->getOneOrNullResult();

// LEFT JOIN FETCH for optional relationships
$dql = 'SELECT o, l FROM App\Entity\Order o LEFT JOIN o.lines l WHERE o.id = :id';
```

### Aggregate Queries

```php
$dql = 'SELECT c.name, COUNT(p.id) as productCount
        FROM App\Entity\Category c
        LEFT JOIN c.products p
        GROUP BY c.id
        HAVING COUNT(p.id) > :min';
```

## QueryBuilder

Fluent API for building DQL programmatically.

```php
$qb = $em->createQueryBuilder();
$qb->select('p')
   ->from(Product::class, 'p')
   ->where('p.status = :status')
   ->andWhere('p.price > :minPrice')
   ->orderBy('p.createdAt', 'DESC')
   ->setParameter('status', ProductStatus::Active)
   ->setParameter('minPrice', '10.00');

$products = $qb->getQuery()->getResult();
```

### Dynamic Filters

```php
public function findByFilters(
    ?ProductStatus $status = null,
    ?string $search = null,
    ?string $minPrice = null,
): array {
    $qb = $this->createQueryBuilder('p');

    if ($status !== null) {
        $qb->andWhere('p.status = :status')
           ->setParameter('status', $status);
    }

    if ($search !== null) {
        $qb->andWhere('p.name LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($minPrice !== null) {
        $qb->andWhere('p.price >= :minPrice')
           ->setParameter('minPrice', $minPrice);
    }

    return $qb->getQuery()->getResult();
}
```

## Custom Repositories

```php
/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findActiveByCategory(Category $category): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->where('c = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', ProductStatus::Active)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

## DTO Projections

Return lightweight DTOs instead of full entities for read-only queries.

```php
// Using NEW operator in DQL
$dql = 'SELECT NEW App\DTO\ProductSummary(p.id, p.name, p.price, c.name)
        FROM App\Entity\Product p
        JOIN p.category c
        WHERE p.status = :status';

$summaries = $em->createQuery($dql)
    ->setParameter('status', ProductStatus::Active)
    ->getResult();
```

```php
// The DTO class
final readonly class ProductSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public string $price,
        public string $categoryName,
    ) {}
}
```

## Pagination

### Doctrine Paginator

```php
use Doctrine\ORM\Tools\Pagination\Paginator;

public function findPaginated(int $page, int $limit = 20): Paginator
{
    $query = $this->createQueryBuilder('p')
        ->orderBy('p.createdAt', 'DESC')
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->getQuery();

    return new Paginator($query, fetchJoinCollection: true);
}

// Usage
$paginator = $repo->findPaginated(page: 2, limit: 20);
$totalItems = count($paginator); // COUNT query
foreach ($paginator as $product) { /* ... */ }
```

### Cursor-Based Pagination

More efficient for large datasets — avoids OFFSET performance degradation.

```php
public function findAfter(?int $lastId, int $limit = 20): array
{
    $qb = $this->createQueryBuilder('p')
        ->orderBy('p.id', 'ASC')
        ->setMaxResults($limit);

    if ($lastId !== null) {
        $qb->where('p.id > :lastId')
           ->setParameter('lastId', $lastId);
    }

    return $qb->getQuery()->getResult();
}
```

## Native SQL

For complex queries that DQL cannot express.

```php
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

$rsm = new ResultSetMappingBuilder($em);
$rsm->addRootEntityFromClassMetadata(Product::class, 'p');

$sql = 'SELECT p.* FROM products p WHERE p.price > :min ORDER BY RAND() LIMIT 5';
$query = $em->createNativeQuery($sql, $rsm);
$query->setParameter('min', '10.00');
$products = $query->getResult();
```

**Prefer DQL/QueryBuilder over native SQL.** Use native SQL only for DB-specific features (window functions, `RAND()`, full-text search, CTEs).

## Query Hints and Caching

```php
// Result cache (Doctrine 3.x uses cache pools)
$query->enableResultCache(3600, 'products_active');

// Disable hydration for read-only data
$query->setHint(\Doctrine\ORM\Query::HINT_READ_ONLY, true);
```
