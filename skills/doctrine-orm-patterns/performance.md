# Performance

## N+1 Query Prevention

The most common Doctrine performance issue. Accessing a lazy-loaded collection in a loop triggers one query per entity.

### Problem

```php
// BAD: N+1 — one query for orders, then one query per order for lines
$orders = $orderRepo->findAll();
foreach ($orders as $order) {
    foreach ($order->getLines() as $line) { // triggers lazy load
        // ...
    }
}
```

### Solution: JOIN FETCH

```php
// GOOD: Single query with JOIN FETCH
$orders = $em->createQuery(
    'SELECT o, l FROM App\Entity\Order o JOIN o.lines l'
)->getResult();
```

### Solution: QueryBuilder with join

```php
$orders = $orderRepo->createQueryBuilder('o')
    ->addSelect('l') // must select the joined entity
    ->join('o.lines', 'l')
    ->getQuery()
    ->getResult();
```

**Rule:** If you access a relationship in a loop, always use JOIN FETCH or a dedicated query that loads the data eagerly.

## Batch Processing

### Bulk Inserts

```php
$batchSize = 50;
for ($i = 0; $i < $total; $i++) {
    $entity = new Product(/* ... */);
    $em->persist($entity);

    if (($i % $batchSize) === 0) {
        $em->flush();
        $em->clear(); // detaches all entities — frees memory
    }
}
$em->flush();
$em->clear();
```

### Bulk Updates (DQL)

For large-scale updates, use DQL UPDATE to bypass hydration:

```php
$em->createQuery(
    'UPDATE App\Entity\Product p SET p.status = :new WHERE p.status = :old'
)
->setParameter('new', ProductStatus::Archived)
->setParameter('old', ProductStatus::Draft)
->execute();
```

**Note:** DQL UPDATE/DELETE bypass the entity lifecycle (no events, no cascade). Clear the EntityManager after bulk DQL operations.

### Iterating Large Result Sets

```php
$query = $em->createQuery('SELECT p FROM App\Entity\Product p');

foreach ($query->toIterable() as $product) {
    // process one entity at a time — low memory usage
    $em->detach($product); // free memory after processing
}
```

## Indexing

### Attribute-Based Indexes

```php
#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_status_created')]
#[ORM\Index(columns: ['category_id'], name: 'idx_category')]
#[ORM\UniqueConstraint(columns: ['slug'], name: 'uniq_slug')]
class Product { /* ... */ }
```

### Index Strategy

| Query pattern                         | Index type                                   |
|---------------------------------------|----------------------------------------------|
| `WHERE status = ?`                    | Single column B-tree                         |
| `WHERE status = ? AND created_at > ?` | Composite (equality first, then range)       |
| `WHERE slug = ?`                      | Unique index                                 |
| Foreign key columns                   | B-tree (always index FKs)                    |
| Full-text search                      | DB-specific (MySQL FULLTEXT, PostgreSQL GIN) |

**Composite index column order matters:** put equality conditions first, range conditions last.

## Second-Level Cache

Caches entity data across requests. Useful for read-heavy, rarely-changing data.

```php
#[ORM\Entity]
#[ORM\Cache(usage: 'READ_ONLY')] // or NONSTRICT_READ_WRITE, READ_WRITE
class Country
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Cache]
    #[ORM\OneToMany(targetEntity: City::class, mappedBy: 'country')]
    private Collection $cities;
}
```

| Mode                   | Use when                                    |
|------------------------|---------------------------------------------|
| `READ_ONLY`            | Data never changes (countries, currencies)  |
| `NONSTRICT_READ_WRITE` | Rare updates, eventual consistency OK       |
| `READ_WRITE`           | Frequent updates, strict consistency needed |

**Configuration** (Symfony):

```yaml
doctrine:
    orm:
        second_level_cache:
            enabled: true
            region_cache_driver:
                type: pool
                pool: doctrine.second_level_cache_pool
```

## Query Result Caching

```php
$products = $repo->createQueryBuilder('p')
    ->where('p.status = :status')
    ->setParameter('status', ProductStatus::Active)
    ->getQuery()
    ->enableResultCache(3600, 'active_products') // TTL in seconds
    ->getResult();
```

Invalidate manually when data changes:

```php
$cache = $em->getConfiguration()->getResultCache();
$cache->deleteItem('active_products');
```

## Profiling and Debugging

### Symfony Profiler

The Doctrine toolbar panel shows:
- Number of queries per request
- Query execution time
- Duplicate queries (potential N+1)

### SQL Logging

```yaml
# config/packages/dev/doctrine.yaml
doctrine:
    dbal:
        logging: true
        profiling: true
```

### PHPStan + Doctrine Extension

```bash
composer require --dev phpstan/phpstan-doctrine
```

Catches type errors in DQL, invalid entity references, and wrong column types at analysis time.

## Checklist

- [ ] No N+1 queries — verify with Symfony profiler or SQL logging
- [ ] Foreign key columns are indexed
- [ ] Composite indexes match query patterns (equality first, range last)
- [ ] Batch operations use `flush()` + `clear()` cycles
- [ ] Large result sets use `toIterable()` or pagination
- [ ] Read-only queries use DTO projections instead of full entities
- [ ] Second-level cache configured for read-heavy reference data
- [ ] No `EAGER` fetch on collections
