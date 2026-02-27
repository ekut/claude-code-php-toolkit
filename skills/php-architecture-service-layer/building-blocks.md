# Service-Oriented Building Blocks

## Service Layer

Services orchestrate business logic, manage transactions, and coordinate between repositories:

```php
final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private PricingService $pricingService,
        private EventDispatcherInterface $events,
        private EntityManagerInterface $em,
    ) {}

    public function createOrder(CreateOrderDTO $dto): OrderSummaryDTO
    {
        $this->em->beginTransaction();

        try {
            $order = new Order();
            $order->setCustomerId($dto->customerId);
            $order->setStatus(OrderStatus::Pending);
            $order->setCreatedAt(new \DateTimeImmutable());

            foreach ($dto->lines as $line) {
                $product = $this->productRepository->findOrFail($line->productId);

                if ($product->getStock() < $line->quantity) {
                    throw new InsufficientStockException($product->getId(), $line->quantity);
                }

                $unitPrice = $this->pricingService->calculatePrice($product, $line->quantity);

                $orderLine = new OrderLine();
                $orderLine->setProduct($product);
                $orderLine->setQuantity($line->quantity);
                $orderLine->setUnitPrice($unitPrice);

                $order->addLine($orderLine);
                $product->decrementStock($line->quantity);
            }

            $order->setTotal($this->pricingService->calculateTotal($order));

            $this->orderRepository->save($order);
            $this->em->commit();

            $this->events->dispatch(new OrderCreatedEvent($order->getId()));

            return OrderSummaryDTO::fromEntity($order);
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function cancelOrder(int $orderId, string $reason): void
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (!in_array($order->getStatus(), [OrderStatus::Pending, OrderStatus::Confirmed], true)) {
            throw new \DomainException("Order #{$orderId} cannot be cancelled in status {$order->getStatus()->value}");
        }

        $order->setStatus(OrderStatus::Cancelled);
        $order->setCancelledAt(new \DateTimeImmutable());
        $order->setCancellationReason($reason);

        $this->orderRepository->save($order);
    }

    public function getOrderSummary(int $orderId): OrderSummaryDTO
    {
        $order = $this->orderRepository->findOrFail($orderId);

        return OrderSummaryDTO::fromEntity($order);
    }

    /** @return list<OrderSummaryDTO> */
    public function listOrdersByCustomer(int $customerId, int $page = 1, int $perPage = 20): PaginatedResultDTO
    {
        return $this->orderRepository->findByCustomerPaginated($customerId, $page, $perPage);
    }
}
```

## Transaction Script

For simpler operations, a single method handles the entire flow:

```php
final readonly class PasswordResetService
{
    public function __construct(
        private UserRepository $users,
        private TokenGenerator $tokens,
        private MailerInterface $mailer,
    ) {}

    public function requestReset(string $email): void
    {
        $user = $this->users->findByEmail($email);

        // Silent return for non-existent users (prevent enumeration)
        if ($user === null) {
            return;
        }

        $token = $this->tokens->generate();
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->users->save($user);
        $this->mailer->send(new PasswordResetEmail($user->getEmail(), $token));
    }

    public function executeReset(string $token, string $newPassword): void
    {
        $user = $this->users->findByResetToken($token);

        if ($user === null || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new InvalidResetTokenException();
        }

        $user->setPasswordHash(password_hash($newPassword, PASSWORD_DEFAULT));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->users->save($user);
    }
}
```

## DTOs

Readonly data transfer objects with factory methods:

```php
final readonly class CreateOrderDTO
{
    public function __construct(
        public int $customerId,
        /** @var list<OrderLineDTO> */
        public array $lines,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validated();

        return new self(
            customerId: $data['customer_id'],
            lines: array_map(
                fn (array $line) => new OrderLineDTO(
                    productId: $line['product_id'],
                    quantity: $line['quantity'],
                ),
                $data['lines'],
            ),
            notes: $data['notes'] ?? null,
        );
    }
}

final readonly class OrderLineDTO
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}

final readonly class OrderSummaryDTO
{
    public function __construct(
        public int $id,
        public int $customerId,
        public string $status,
        public int $totalCents,
        public int $lineCount,
        public string $createdAt,
    ) {}

    public static function fromEntity(Order $order): self
    {
        return new self(
            id: $order->getId(),
            customerId: $order->getCustomerId(),
            status: $order->getStatus()->value,
            totalCents: $order->getTotal(),
            lineCount: count($order->getLines()),
            createdAt: $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}

final readonly class PaginatedResultDTO
{
    public function __construct(
        /** @var list<OrderSummaryDTO> */
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function hasNextPage(): bool
    {
        return ($this->page * $this->perPage) < $this->total;
    }
}
```

## Thin Models / Entities

Entities are ORM-mapped data containers — getters, setters, and relationship mappings:

```php
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $customerId;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $cancellationReason = null;

    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCustomerId(): int { return $this->customerId; }
    public function setCustomerId(int $customerId): void { $this->customerId = $customerId; }
    public function getStatus(): OrderStatus { return $this->status; }
    public function setStatus(OrderStatus $status): void { $this->status = $status; }
    public function getTotal(): int { return $this->total; }
    public function setTotal(int $total): void { $this->total = $total; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }
    public function getCancelledAt(): ?\DateTimeImmutable { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): void { $this->cancelledAt = $cancelledAt; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }
    public function setCancellationReason(?string $reason): void { $this->cancellationReason = $reason; }

    /** @return Collection<int, OrderLine> */
    public function getLines(): Collection { return $this->lines; }

    public function addLine(OrderLine $line): void
    {
        $line->setOrder($this);
        $this->lines->add($line);
    }
}
```

## Repository with Query Methods

Repositories encapsulate data access with named query methods:

```php
final class OrderRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function save(Order $order): void
    {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function findOrFail(int $id): Order
    {
        return $this->em->find(Order::class, $id)
            ?? throw new OrderNotFoundException($id);
    }

    public function findByCustomerPaginated(int $customerId, int $page, int $perPage): PaginatedResultDTO
    {
        $qb = $this->em->createQueryBuilder();

        $total = (clone $qb)
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getSingleScalarResult();

        $orders = $qb
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return new PaginatedResultDTO(
            items: array_map(OrderSummaryDTO::fromEntity(...), $orders),
            total: (int) $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /** @return list<Order> */
    public function findByStatus(OrderStatus $status): array
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Order> */
    public function findPendingOlderThan(\DateTimeImmutable $threshold): array
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt < :threshold')
            ->setParameter('status', OrderStatus::Pending)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
```
