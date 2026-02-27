# DDD Building Blocks

## Value Objects

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

## Entities

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

## Aggregates

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

## Domain Events

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

## CQRS (Command/Query Responsibility Segregation)

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

## Repository Interfaces as Ports

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
