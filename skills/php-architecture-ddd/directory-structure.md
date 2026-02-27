# DDD Directory Structure

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

**Layer responsibilities:**

- **Domain** — pure business logic, zero framework dependencies
- **Application** — orchestrates use cases via Commands and Queries; depends only on Domain interfaces
- **Infrastructure** — implements Domain ports (repositories, event dispatchers, messaging)
- **UserInterface** — HTTP controllers, CLI commands; delegates to Application layer
