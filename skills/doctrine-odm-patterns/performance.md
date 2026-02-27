# Performance

## Indexing

### Attribute-Based Index Definitions

```php
#[ODM\Document(collection: 'products')]
#[ODM\Index(keys: ['status' => 1, 'createdAt' => -1])]
#[ODM\Index(keys: ['slug' => 1], options: ['unique' => true])]
#[ODM\Index(keys: ['name' => 'text', 'description' => 'text'])]
class Product { /* ... */ }
```

### Index Types

| Type | Syntax | Use when |
|------|--------|----------|
| Ascending | `1` | Equality + range queries, sorting |
| Descending | `-1` | Sorting in reverse |
| Text | `'text'` | Full-text search |
| 2dsphere | `'2dsphere'` | Geospatial queries |
| Hashed | `'hashed'` | Equality-only, sharding |
| TTL | `1` + `expireAfterSeconds` | Auto-expire documents |

### Compound Index Strategy

```php
// Matches: find by status, sort by createdAt
#[ODM\Index(keys: ['status' => 1, 'createdAt' => -1])]

// Matches: find by category + status, sort by price
#[ODM\Index(keys: ['category.$id' => 1, 'status' => 1, 'price' => 1])]
```

**ESR rule (Equality, Sort, Range):** put equality fields first, then sort fields, then range fields.

### TTL Index (Auto-Expiry)

```php
#[ODM\Document]
#[ODM\Index(keys: ['expiresAt' => 1], options: ['expireAfterSeconds' => 0])]
class Session
{
    #[ODM\Field(type: Type::DATE_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;
}
```

### Generating Indexes

```bash
# Create indexes from document mappings
vendor/bin/doctrine-mongodb odm:schema:create --index

# Update indexes (drops and recreates)
vendor/bin/doctrine-mongodb odm:schema:update --index
```

**Always create indexes before deploying.** MongoDB does full collection scans without them.

## Read Preferences

For replica sets, control where reads go:

```yaml
# config/packages/doctrine_mongodb.yaml
doctrine_mongodb:
    connections:
        default:
            options:
                readPreference: secondaryPreferred
```

| Preference | Use when |
|------------|----------|
| `primary` | Reads need latest data (default) |
| `primaryPreferred` | Prefer primary, fall back to secondary |
| `secondary` | Read-only analytics, reduce primary load |
| `secondaryPreferred` | Prefer secondary, fall back to primary |
| `nearest` | Lowest latency (geo-distributed) |

## Batch Operations

### Bulk Inserts

```php
$batchSize = 100;
for ($i = 0; $i < $total; $i++) {
    $doc = new Product(/* ... */);
    $dm->persist($doc);

    if (($i % $batchSize) === 0) {
        $dm->flush();
        $dm->clear();
    }
}
$dm->flush();
$dm->clear();
```

### Atomic Bulk Updates

For large-scale updates, use the Query Builder `updateMany()` to bypass hydration:

```php
$dm->createQueryBuilder(Product::class)
    ->updateMany()
    ->field('status')->set('archived')
    ->field('status')->equals('draft')
    ->field('createdAt')->lte(new \DateTimeImmutable('-1 year'))
    ->getQuery()
    ->execute();
```

## Schema Design Tips

### Embed vs Reference Decision Matrix

| Scenario | Strategy | Reason |
|----------|----------|--------|
| Order + OrderLines | Embed | Lines are part of the order aggregate |
| User + Address (1–3 addresses) | Embed | Small, bounded collection |
| Article + Comments (thousands) | Reference | Unbounded growth |
| Product + Category | Reference | Category is shared across products |
| User + Preferences | Embed (hash) | Always loaded together, schema-flexible |

### Denormalization

MongoDB favors reads over writes. Duplicate frequently-read data:

```php
#[ODM\Document]
class Order
{
    // Store customer name directly for display (denormalized)
    #[ODM\Field(type: Type::STRING)]
    private string $customerName;

    // Also keep the reference for joins when needed
    #[ODM\ReferenceOne(targetDocument: Customer::class, storeAs: 'id')]
    private Customer $customer;
}
```

Update denormalized fields in event subscribers or message handlers when the source changes.

## Profiling Queries

### MongoDB Profiler

```javascript
// Enable profiling (mongosh)
db.setProfilingLevel(2) // log all queries

// Find slow queries
db.system.profile.find({ millis: { $gt: 100 } }).sort({ ts: -1 }).limit(5)
```

### Symfony Profiler

The DoctrineMongoDBBundle toolbar panel shows:
- Number of queries per request
- Query execution time
- Query details with `explain()` output

## Checklist

- [ ] All query fields have appropriate indexes (ESR rule)
- [ ] Compound indexes match actual query patterns
- [ ] No unbounded embedded arrays (risk of hitting 16 MB limit)
- [ ] Batch operations use `flush()` + `clear()` cycles
- [ ] Read preferences configured for replica sets
- [ ] TTL indexes set for expiring documents (sessions, logs)
- [ ] Denormalized fields have update mechanisms
- [ ] Aggregation pipelines use `$match` early to reduce documents
