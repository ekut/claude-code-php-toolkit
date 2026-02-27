# Queries

## Query Builder

```php
use App\Document\Product;

$qb = $dm->createQueryBuilder(Product::class);

// Simple filter
$products = $qb
    ->field('status')->equals('active')
    ->field('price')->gte(10.0)
    ->sort('createdAt', 'desc')
    ->limit(20)
    ->getQuery()
    ->execute();
```

### Common Operators

```php
$qb->field('status')->equals('active');
$qb->field('status')->notEqual('archived');
$qb->field('status')->in(['active', 'featured']);
$qb->field('status')->notIn(['archived', 'deleted']);
$qb->field('price')->gte(10.0);
$qb->field('price')->lte(100.0);
$qb->field('price')->range(10.0, 100.0);
$qb->field('name')->equals(new \MongoDB\BSON\Regex('phone', 'i'));
$qb->field('tags')->all(['php', 'symfony']);
$qb->field('tags')->size(3);
$qb->field('deletedAt')->exists(false);
```

### Dynamic Filters

```php
public function findByFilters(
    ?string $status = null,
    ?string $search = null,
    ?float $minPrice = null,
): array {
    $qb = $this->createQueryBuilder();

    if ($status !== null) {
        $qb->field('status')->equals($status);
    }

    if ($search !== null) {
        $qb->field('name')->equals(new \MongoDB\BSON\Regex(preg_quote($search), 'i'));
    }

    if ($minPrice !== null) {
        $qb->field('price')->gte($minPrice);
    }

    return $qb->getQuery()->execute()->toArray();
}
```

## Custom Repositories

```php
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<Product>
 */
class ProductRepository extends ServiceDocumentRepository
{
    public function findActiveByCategory(string $categoryId): array
    {
        return $this->createQueryBuilder()
            ->field('category.$id')->equals(new \MongoDB\BSON\ObjectId($categoryId))
            ->field('status')->equals('active')
            ->sort('name', 'asc')
            ->getQuery()
            ->execute()
            ->toArray();
    }
}
```

## Aggregation Pipeline

For complex data transformations, reporting, and analytics.

```php
$builder = $dm->createAggregationBuilder(Order::class);

// Group orders by status, count and sum totals
$builder
    ->match()
        ->field('createdAt')
        ->gte(new \DateTimeImmutable('-30 days'))
    ->group()
        ->field('id')->expression('$status')
        ->field('count')->sum(1)
        ->field('totalAmount')->sum('$amount')
    ->sort(['totalAmount' => -1]);

$results = $builder->execute()->toArray();
// [{ _id: 'completed', count: 150, totalAmount: 45000.00 }, ...]
```

### Common Aggregation Stages

```php
// $match — filter documents
$builder->match()
    ->field('status')->equals('active');

// $group — aggregate values
$builder->group()
    ->field('id')->expression('$category')
    ->field('avgPrice')->avg('$price')
    ->field('count')->sum(1);

// $project — reshape documents
$builder->project()
    ->field('name')->expression(1)
    ->field('price')->expression(1)
    ->field('discountedPrice')->multiply('$price', 0.9);

// $unwind — flatten arrays
$builder->unwind('$tags');

// $lookup — join with another collection
$builder->lookup('categories')
    ->localField('categoryId')
    ->foreignField('_id')
    ->alias('category');

// $sort and $limit
$builder->sort(['count' => -1])->limit(10);
```

### Aggregation Example: Monthly Revenue

```php
$builder = $dm->createAggregationBuilder(Order::class);
$builder
    ->match()
        ->field('status')->equals('completed')
        ->field('createdAt')->gte(new \DateTimeImmutable('-12 months'))
    ->group()
        ->field('id')
        ->expression([
            'year' => ['$year' => '$createdAt'],
            'month' => ['$month' => '$createdAt'],
        ])
        ->field('revenue')->sum('$totalAmount')
        ->field('orderCount')->sum(1)
    ->sort(['_id.year' => 1, '_id.month' => 1]);

$monthlyRevenue = $builder->execute()->toArray();
```

## Pagination

### Offset-Based

```php
public function findPaginated(int $page, int $limit = 20): array
{
    return $this->createQueryBuilder()
        ->sort('createdAt', 'desc')
        ->skip(($page - 1) * $limit)
        ->limit($limit)
        ->getQuery()
        ->execute()
        ->toArray();
}
```

### Cursor-Based (recommended for large datasets)

```php
public function findAfter(?string $lastId, int $limit = 20): array
{
    $qb = $this->createQueryBuilder()
        ->sort('_id', 'asc')
        ->limit($limit);

    if ($lastId !== null) {
        $qb->field('_id')->gt(new \MongoDB\BSON\ObjectId($lastId));
    }

    return $qb->getQuery()->execute()->toArray();
}
```

## Update Operations

### Atomic Updates (no hydration needed)

```php
$dm->createQueryBuilder(Product::class)
    ->updateMany()
    ->field('status')->set('archived')
    ->field('archivedAt')->set(new \DateTimeImmutable())
    ->field('viewCount')->inc(1)
    ->field('tags')->push('on-sale')
    ->field('oldField')->unsetField()
    ->field('updatedAt')->gte(new \DateTimeImmutable('-1 year'))
    ->getQuery()
    ->execute();
```

Atomic updates bypass ODM hydration and lifecycle events — use for bulk operations.
