# References

## ReferenceOne (Many-to-One / One-to-One)

```php
#[ODM\Document]
class Order
{
    #[ODM\ReferenceOne(targetDocument: Customer::class, storeAs: 'id')]
    private Customer $customer;
}
```

### Storage Strategies

| Strategy | What is stored | Use when |
|----------|---------------|----------|
| `id` (default) | Only the referenced ID | Most cases — lightweight |
| `dbRef` | `{ $ref, $id }` | Legacy compatibility |
| `dbRefWithDb` | `{ $ref, $id, $db }` | Cross-database references |
| `ref` | `{ id }` (BSON document) | When you need discriminator fields |

**Prefer `storeAs: 'id'`** — smallest storage footprint, fastest queries.

## ReferenceMany (One-to-Many)

```php
#[ODM\Document]
class Customer
{
    /** @var Collection<int, Order> */
    #[ODM\ReferenceMany(targetDocument: Order::class, mappedBy: 'customer')]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }
}

#[ODM\Document]
class Order
{
    #[ODM\ReferenceOne(targetDocument: Customer::class, inversedBy: 'orders')]
    private Customer $customer;
}
```

### Owning vs Inverse Side

Same concept as Doctrine ORM:
- **Owning side** — holds the reference (stores the ID). Changes here are persisted.
- **Inverse side** — uses `mappedBy`. Read-only for persistence purposes.

Always synchronize both sides:

```php
class Customer
{
    public function addOrder(Order $order): void
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCustomer($this);
        }
    }
}
```

## Many-to-Many

```php
#[ODM\Document]
class Article
{
    /** @var Collection<int, Tag> */
    #[ODM\ReferenceMany(targetDocument: Tag::class, storeAs: 'id')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }
}
```

MongoDB stores the referenced IDs as an array in the owning document:

```json
{
    "_id": "...",
    "title": "My Article",
    "tags": ["tagId1", "tagId2", "tagId3"]
}
```

**Caution:** If the array of references grows unbounded, consider an inverse reference or a join collection instead.

## Cascade Options

| Option | Effect |
|--------|--------|
| `persist` | Persisting parent also persists referenced documents |
| `remove` | Removing parent also removes referenced documents |
| `merge` | Merging parent also merges referenced documents |
| `detach` | Detaching parent also detaches referenced documents |
| `refresh` | Refreshing parent also refreshes referenced documents |
| `all` | All of the above |

```php
#[ODM\ReferenceMany(targetDocument: OrderLine::class, cascade: ['persist', 'remove'])]
private Collection $lines;
```

**Use `cascade: ['persist']` liberally** to save boilerplate. Use `cascade: ['remove']` carefully — deleting a parent should only cascade when children have no meaning without it.

## Polymorphic References

Reference different document types in one field using discriminator mapping:

```php
#[ODM\Document]
class Activity
{
    #[ODM\ReferenceOne(
        discriminatorField: 'type',
        discriminatorMap: [
            'comment' => Comment::class,
            'review' => Review::class,
        ],
    )]
    private Comment|Review $target;
}
```

## Prime (Eager-Load References)

Prevent N+1 when loading referenced documents in a loop:

```php
$query = $dm->createQueryBuilder(Order::class)
    ->field('status')->equals('pending')
    ->getQuery();

// Prime loads all referenced customers in a single query
$query->prime('customer');

$orders = $query->execute();
```

This is the ODM equivalent of `JOIN FETCH` in ORM.
