# Document Design

## Basic Document

```php
<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document(collection: 'products', repositoryClass: ProductRepository::class)]
#[ODM\Index(keys: ['slug' => 'asc'], options: ['unique' => true])]
#[ODM\Index(keys: ['status' => 1, 'createdAt' => -1])]
class Product
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: Type::STRING)]
    private string $name;

    #[ODM\Field(type: Type::STRING)]
    private string $slug;

    #[ODM\Field(type: Type::STRING, nullable: true)]
    private ?string $description = null;

    #[ODM\Field(type: Type::FLOAT)]
    private float $price;

    #[ODM\Field(type: Type::STRING, enumType: ProductStatus::class)]
    private ProductStatus $status = ProductStatus::Draft;

    #[ODM\Field(type: Type::DATE_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ODM\Field(type: Type::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name, string $slug, float $price)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->price = $price;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    // ... getters and domain methods
}
```

## ID Strategies

| Strategy | Generated as | Use when |
|----------|-------------|----------|
| `AUTO` (default) | MongoDB ObjectId | Most cases |
| `NONE` | You assign manually | Domain-specific IDs |
| `UUID` | UUID v4 string | Distributed systems |
| `INCREMENT` | Auto-incrementing int | Rare — not native to MongoDB |

```php
// Custom UUID ID
#[ODM\Id(strategy: 'NONE', type: 'string')]
private string $id;

public function __construct()
{
    $this->id = Uuid::v4()->toRfc4122();
}
```

## PHP 8.1+ Enums

```php
enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}

// In document mapping
#[ODM\Field(type: Type::STRING, enumType: ProductStatus::class)]
private ProductStatus $status;
```

## Embedded Documents

Embedded documents are stored inside the parent document — no separate collection.

```php
#[ODM\EmbeddedDocument]
class Address
{
    #[ODM\Field(type: Type::STRING)]
    private string $street;

    #[ODM\Field(type: Type::STRING)]
    private string $city;

    #[ODM\Field(type: Type::STRING)]
    private string $postalCode;

    #[ODM\Field(type: Type::STRING)]
    private string $country;

    public function __construct(string $street, string $city, string $postalCode, string $country)
    {
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->country = $country;
    }
}

// Usage in parent document
#[ODM\Document]
class Customer
{
    #[ODM\EmbedOne(targetDocument: Address::class)]
    private ?Address $billingAddress = null;

    /** @var Collection<int, Address> */
    #[ODM\EmbedMany(targetDocument: Address::class)]
    private Collection $shippingAddresses;

    public function __construct()
    {
        $this->shippingAddresses = new ArrayCollection();
    }
}
```

## Hash Fields (Schemaless Sub-Documents)

For flexible key-value data without a defined class:

```php
#[ODM\Field(type: Type::HASH)]
private array $metadata = [];
```

Stored as a BSON object. Useful for configuration or dynamic attributes.

## Lifecycle Callbacks

```php
#[ODM\Document]
#[ODM\HasLifecycleCallbacks]
class Article
{
    #[ODM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ODM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

Available events: `PrePersist`, `PostPersist`, `PreUpdate`, `PostUpdate`, `PreRemove`, `PostRemove`, `PreLoad`, `PostLoad`, `PreFlush`.

For complex logic, prefer event subscribers over lifecycle callbacks.

## Field Types Reference

| PHP Type | ODM Type | BSON Type |
|----------|----------|-----------|
| `string` | `string` | String |
| `int` | `int` | Int32/Int64 |
| `float` | `float` | Double |
| `bool` | `bool` | Boolean |
| `array` | `hash` | Object |
| `array` | `collection` | Array |
| `\DateTimeImmutable` | `date_immutable` | Date |
| `\DateTime` | `date` | Date |
| `BackedEnum` | `string`/`int` | String/Int |
| binary | `bin` | Binary |

**Prefer `DateTimeImmutable` over `DateTime`** — same reason as ORM: immutability prevents change-tracking issues.
