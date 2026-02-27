# Service-Oriented Directory Structure

```
src/
├── Controller/
│   ├── OrderController.php          # HTTP layer, delegates to services
│   ├── ProductController.php
│   └── Api/
│       └── OrderApiController.php   # API-specific controllers
│
├── Service/
│   ├── OrderService.php             # Business logic for orders
│   ├── ProductService.php
│   ├── PricingService.php           # Cross-entity logic
│   └── NotificationService.php
│
├── Repository/
│   ├── OrderRepository.php          # Data access for orders
│   ├── ProductRepository.php
│   └── Interface/                   # Optional: interfaces for testing
│       ├── OrderRepositoryInterface.php
│       └── ProductRepositoryInterface.php
│
├── DTO/
│   ├── CreateOrderDTO.php           # Input DTO
│   ├── OrderSummaryDTO.php          # Output DTO
│   ├── UpdateProductDTO.php
│   └── PaginatedResultDTO.php
│
├── Entity/
│   ├── Order.php                    # ORM-mapped entity (thin)
│   ├── OrderLine.php
│   ├── Product.php
│   └── User.php
│
├── Enum/
│   ├── OrderStatus.php
│   └── PaymentMethod.php
│
├── Exception/
│   ├── OrderNotFoundException.php
│   ├── InsufficientStockException.php
│   └── PaymentFailedException.php
│
└── Event/
    ├── OrderCreatedEvent.php        # Application events
    └── StockDepletedEvent.php
```

**Layer responsibilities:**

- **Controller** — validates input, calls one service method, returns response; no business logic
- **Service** — owns all business rules, transactions, and orchestration between repositories
- **Repository** — encapsulates data access; all queries live here, not in controllers or services
- **DTO** — carries data between layers; services accept/return DTOs, never framework Request/Response
- **Entity** — thin ORM-mapped data container; state plus getters/setters, minimal behavior
