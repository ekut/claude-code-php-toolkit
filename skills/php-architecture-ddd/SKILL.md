---
name: php-architecture-ddd
description: Domain-Driven Design architecture for PHP — Rich Domain Model, Hexagonal Architecture, CQRS, Bounded Contexts, Aggregates, Value Objects, Domain Events.
origin: claude-code-php-toolkit
---

# Domain-Driven Design (DDD) Architecture

Domain-Driven Design places the business domain at the center of the software. The code mirrors the language and rules of the business, and the architecture enforces a strict dependency direction: infrastructure depends on the domain, never the reverse.

DDD is not a universal solution — it earns its complexity when the domain itself is complex. Projects with many business invariants, bounded contexts, and long lifespans benefit most. For simpler CRUD apps, the ceremony adds overhead without proportional value.

## When to Use

- The domain has **complex business rules** — invariants, state machines, multi-step processes
- Multiple **bounded contexts** exist (e.g., Orders, Inventory, Billing are separate sub-domains)
- **Domain experts** are available and the team invests in a ubiquitous language
- The team has **DDD experience** or is willing to invest in learning
- The application is **long-lived** (3+ years) and will evolve significantly
- **Multiple interfaces** consume the same domain logic (web, CLI, API, async workers)
- Business rules change more often than infrastructure

## Core Principles

### Dependency Direction Inward
Dependencies always point inward: Infrastructure → Application → Domain. The Domain layer has zero external dependencies — no framework imports, no ORM annotations, no HTTP concerns.

### Ubiquitous Language
Code uses the same terms as domain experts. If the business says "Place an Order," the code has `Order::place()`, not `OrderService::process()`. Class names, method names, and event names all reflect business vocabulary.

### Bounded Contexts
Each sub-domain has its own models and vocabulary. A `Product` in the Catalog context is different from a `Product` in Inventory. Contexts communicate through explicit contracts (events, shared IDs), never by sharing entities.

### Aggregate Consistency
An aggregate is a cluster of objects treated as a unit for data changes. Only the aggregate root is directly accessible. All mutations go through the root, which enforces business invariants before accepting the change.

## Directory Structure

```
src/
├── Domain/
│   ├── Order/
│   │   ├── Order.php                   # Aggregate root
│   │   ├── OrderLine.php               # Entity within aggregate
│   │   ├── OrderStatus.php             # Enum (PHP 8.1+)
│   │   ├── OrderId.php                 # Value Object (identity)
│   │   ├── OrderRepositoryInterface.php # Port (interface)
│   │   ├── Event/
│   │   │   ├── OrderPlaced.php         # Domain event
│   │   │   └── OrderCancelled.php
│   │   ├── Exception/
│   │   │   ├── OrderAlreadySubmittedException.php
│   │   │   └── EmptyOrderException.php
│   │   └── Policy/
│   │       └── OrderDiscountPolicy.php # Domain service
│   ├── Product/
│   │   ├── Product.php
│   │   ├── ProductId.php
│   │   └── ProductRepositoryInterface.php
│   └── Shared/
│       ├── Money.php                   # Shared Value Object
│       ├── Currency.php
│       └── AggregateRoot.php           # Base class (optional)
│
├── Application/
│   ├── Order/
│   │   ├── Command/
│   │   │   ├── PlaceOrderCommand.php   # Command DTO
│   │   │   └── PlaceOrderHandler.php   # Use case
│   │   ├── Query/
│   │   │   ├── GetOrderQuery.php
│   │   │   └── GetOrderHandler.php
│   │   └── DTO/
│   │       └── OrderSummaryDTO.php     # Read model
│   └── Shared/
│       ├── CommandBusInterface.php
│       └── QueryBusInterface.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── DoctrineOrderRepository.php # Adapter (implements port)
│   │   └── DoctrineProductRepository.php
│   ├── Messaging/
│   │   └── SymfonyEventDispatcher.php
│   └── ReadModel/
│       └── DbalOrderReadRepository.php # Optimized reads
│
└── UserInterface/
    ├── Http/
    │   ├── Controller/
    │   │   └── OrderController.php
    │   └── Request/
    │       └── PlaceOrderRequest.php
    └── Cli/
        └── ProcessOrdersCommand.php
```

## Building Blocks

### Value Objects

Immutable objects compared by value, not identity. Use PHP 8.2+ `readonly` classes:

```php
final readonly class Money
{
    public function __construct(
        public int $amount,
        public Currency $currency,
    ) {}

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException(
                "Cannot add {$this->currency->value} to {$other->currency->value}"
            );
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException();
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}

final readonly class EmailAddress
{
    public string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($value);
        }

        $this->value = mb_strtolower($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function domain(): string
    {
        return mb_substr($this->value, mb_strpos($this->value, '@') + 1);
    }
}
```

### Entities

Objects with a unique identity that persists over time. They encapsulate business rules:

```php
final class Order
{
    private OrderStatus $status;
    /** @var list<OrderLine> */
    private array $lines = [];
    /** @var list<object> */
    private array $domainEvents = [];

    public function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->status = OrderStatus::Draft;
    }

    public function addLine(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new OrderAlreadySubmittedException($this->id);
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1');
        }

        $this->lines[] = new OrderLine($productId, $quantity, $unitPrice);
    }

    public function place(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new OrderAlreadySubmittedException($this->id);
        }

        if ($this->lines === []) {
            throw new EmptyOrderException($this->id);
        }

        $this->status = OrderStatus::Placed;
        $this->domainEvents[] = new OrderPlaced(
            orderId: $this->id,
            customerId: $this->customerId,
            total: $this->calculateTotal(),
            placedAt: new \DateTimeImmutable(),
        );
    }

    public function cancel(string $reason): void
    {
        if (!$this->status->isCancellable()) {
            throw new OrderCannotBeCancelledException($this->id, $this->status);
        }

        $this->status = OrderStatus::Cancelled;
        $this->domainEvents[] = new OrderCancelled($this->id, $reason);
    }

    public function calculateTotal(): Money
    {
        $total = new Money(0, Currency::USD);

        foreach ($this->lines as $line) {
            $total = $total->add($line->lineTotal());
        }

        return $total;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }
}
```

### Aggregates

Aggregates define consistency boundaries. The root entity controls all access:

```php
// OrderLine is an entity WITHIN the Order aggregate — never accessed directly
final readonly class OrderLine
{
    public function __construct(
        private ProductId $productId,
        private int $quantity,
        private Money $unitPrice,
    ) {}

    public function lineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}

// Aggregates reference other aggregates by ID, not by object
// CORRECT: Order stores CustomerId (value object)
// WRONG:   Order stores Customer (entity reference)
```

### Domain Events

Record what happened in the domain. Dispatch after persistence succeeds:

```php
final readonly class OrderPlaced
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public Money $total,
        public \DateTimeImmutable $placedAt,
    ) {}
}

final readonly class OrderCancelled
{
    public function __construct(
        public OrderId $orderId,
        public string $reason,
    ) {}
}
```

### CQRS (Command/Query Responsibility Segregation)

Separate write operations (commands) from read operations (queries):

```php
// Command — intent to change state (no return value)
final readonly class PlaceOrderCommand
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        /** @var list<array{productId: string, quantity: int, unitPrice: int}> */
        public array $lines,
    ) {}
}

// Command Handler — executes the use case
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventDispatcherInterface $events,
    ) {}

    public function __invoke(PlaceOrderCommand $command): void
    {
        $order = new Order(
            id: new OrderId($command->orderId),
            customerId: new CustomerId($command->customerId),
            createdAt: new \DateTimeImmutable(),
        );

        foreach ($command->lines as $line) {
            $order->addLine(
                new ProductId($line['productId']),
                $line['quantity'],
                new Money($line['unitPrice'], Currency::USD),
            );
        }

        $order->place();
        $this->orders->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }
    }
}

// Query — request for data (returns DTO, no side effects)
final readonly class GetOrderQuery
{
    public function __construct(
        public string $orderId,
    ) {}
}

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderReadRepositoryInterface $readRepository,
    ) {}

    public function __invoke(GetOrderQuery $query): OrderSummaryDTO
    {
        return $this->readRepository->findSummary($query->orderId)
            ?? throw new OrderNotFoundException($query->orderId);
    }
}
```

### Repository Interfaces as Ports

The interface lives in the Domain layer; the implementation in Infrastructure:

```php
// Domain/Order/OrderRepositoryInterface.php (Port)
interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    public function nextIdentity(): OrderId;
}

// Infrastructure/Persistence/DoctrineOrderRepository.php (Adapter)
final readonly class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function save(Order $order): void
    {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->em->find(Order::class, $id->value);
    }

    public function nextIdentity(): OrderId
    {
        return new OrderId(Uuid::uuid4()->toString());
    }
}
```

## Anti-Patterns

- **Anemic Domain Model** — entities with only getters/setters, all logic pushed to services. If your entities are data bags, you lose the core benefit of DDD
- **Framework coupling in Domain** — domain classes extending framework base classes, using ORM annotations in entity logic, or importing HTTP/Request objects
- **Aggregate references by object** — storing a full `Customer` entity inside `Order` instead of `CustomerId`. This breaks aggregate boundaries and creates loading/consistency issues
- **Domain events with infrastructure concerns** — events that contain database IDs, serialization logic, or queue metadata instead of pure domain concepts
- **Leaky abstractions** — domain layer aware of database columns, HTTP status codes, or queue names
- **God Aggregate** — an aggregate that tries to enforce consistency across too many entities. If it grows beyond ~5-7 child entities, consider splitting bounded contexts

## Cross-Cutting Concerns

- **Logging** — infrastructure adapters log; domain layer raises events instead of logging directly
- **Authentication/Authorization** — handled in UserInterface or Application layer via middleware/guards, never in Domain
- **Validation** — domain objects self-validate in constructors and methods (Value Objects reject invalid state). Application-level validation (format, permissions) happens in Command/Query handlers or middleware
- **Error handling** — domain throws domain-specific exceptions (`OrderAlreadySubmittedException`). Application layer catches and translates them. Infrastructure layer handles technical errors (connection lost, timeout)

## When to Migrate Away

- DDD ceremony **slows the team** — simple CRUD features require command, handler, event, DTO, repository interface, repository implementation for what could be a single service method
- **Most features are CRUD** with minimal business logic — the domain layer is mostly anemic despite effort to avoid it
- **No domain experts** available — the ubiquitous language is invented by developers, not validated by the business
- Team turnover is high and **new developers struggle** with the architecture for weeks

## Checklist

- [ ] Domain layer has zero framework imports
- [ ] All dependencies point inward (Infrastructure → Application → Domain)
- [ ] Entities encapsulate business rules (not just getters/setters)
- [ ] Value Objects are immutable and self-validating
- [ ] Aggregates enforce consistency boundaries
- [ ] Domain events are dispatched after persistence
- [ ] Repository interfaces defined in Domain, implemented in Infrastructure
- [ ] Bounded contexts communicate via events or shared IDs, not shared entities
- [ ] Commands and queries are separated (CQRS)
- [ ] Ubiquitous language reflected in class/method names
