# Entity Design

## Basic Entity

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\Index(columns: ['status'], name: 'idx_products_status')]
#[ORM\UniqueConstraint(columns: ['slug'], name: 'uniq_products_slug')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(length: 20, enumType: ProductStatus::class)]
    private ProductStatus $status = ProductStatus::Draft;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name, string $slug, string $price)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->price = $price;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // ... getters and domain methods
}
```

## PHP 8.1+ Enums as Column Types

```php
enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
```

Use `enumType` in the column mapping — Doctrine stores the backed value (`string` or `int`).

## Embeddables (Value Objects)

```php
#[ORM\Embeddable]
class Money
{
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 3)]
    private string $currency;

    public function __construct(string $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }
}

// Usage in entity
#[ORM\Embedded(class: Money::class, columnPrefix: 'price_')]
private Money $price;
```

## Lifecycle Callbacks

```php
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

For complex logic, prefer Doctrine event subscribers over lifecycle callbacks.

## Inheritance Mapping

### Single Table Inheritance (recommended for few subtypes)

```php
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['standard' => StandardUser::class, 'admin' => AdminUser::class])]
abstract class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
}
```

### Class Table Inheritance (for many subtypes with distinct columns)

```php
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type')]
#[ORM\DiscriminatorMap(['card' => CardPayment::class, 'bank' => BankPayment::class])]
abstract class Payment { /* ... */ }
```

**Rule of thumb:** Prefer Single Table for 2–4 subtypes with similar columns. Use Joined for many subtypes with distinct columns. Avoid Mapped Superclass for polymorphic queries.

## ID Strategies

| Strategy   | When to use                                  |
|------------|----------------------------------------------|
| `IDENTITY` | Auto-increment (MySQL default)               |
| `SEQUENCE` | PostgreSQL sequences                         |
| `UUID`     | Distributed systems, no DB dependency for ID |
| `CUSTOM`   | Domain-specific IDs (e.g., Snowflake)        |

```php
// UUID as primary key
#[ORM\Id]
#[ORM\Column(type: 'uuid', unique: true)]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
private ?Uuid $id = null;
```

## Column Types Reference

| PHP Type             | Doctrine Type          | DB Column            |
|----------------------|------------------------|----------------------|
| `int`                | `integer` / `bigint`   | INT / BIGINT         |
| `string`             | `string`               | VARCHAR(255)         |
| `string`             | `text`                 | TEXT                 |
| `float`              | `float`                | DOUBLE               |
| `string`             | `decimal`              | DECIMAL(p,s)         |
| `bool`               | `boolean`              | TINYINT(1) / BOOLEAN |
| `\DateTimeImmutable` | `datetime_immutable`   | DATETIME             |
| `\DateTimeImmutable` | `datetimetz_immutable` | DATETIMETZ           |
| `array`              | `json`                 | JSON                 |
| `BackedEnum`         | `string` / `integer`   | VARCHAR / INT        |

**Prefer `DateTimeImmutable` over `DateTime`** — immutability prevents accidental state changes that Doctrine's change tracking would miss.
