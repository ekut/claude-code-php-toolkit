# Action-Based Directory Structure

## Standard Layout

```
src/
├── Action/
│   ├── Order/
│   │   ├── CreateOrderAction.php      # POST /orders
│   │   ├── GetOrderAction.php         # GET /orders/{id}
│   │   ├── ListOrdersAction.php       # GET /orders
│   │   ├── CancelOrderAction.php      # POST /orders/{id}/cancel
│   │   └── ExportOrdersCsvAction.php  # GET /orders/export
│   ├── Product/
│   │   ├── CreateProductAction.php
│   │   ├── UpdateProductAction.php
│   │   └── ListProductsAction.php
│   └── Auth/
│       ├── LoginAction.php
│       └── LogoutAction.php
│
├── Command/                            # Write operations
│   ├── CreateOrderCommand.php          # Immutable command DTO
│   ├── CreateOrderHandler.php          # Business logic
│   ├── CancelOrderCommand.php
│   └── CancelOrderHandler.php
│
├── Query/                              # Read operations
│   ├── GetOrderQuery.php
│   ├── GetOrderHandler.php
│   ├── ListOrdersQuery.php
│   └── ListOrdersHandler.php
│
├── DTO/
│   ├── OrderResponseDTO.php
│   └── PaginatedResponseDTO.php
│
├── Entity/
│   ├── Order.php
│   └── Product.php
│
├── Repository/
│   ├── OrderRepository.php
│   └── ProductRepository.php
│
└── Responder/                          # ADR: response formatting (optional)
    ├── JsonResponder.php
    └── CsvResponder.php
```

## ADR Variant

The full Action-Domain-Responder variant gives each use case its own sub-directory:

```
src/
├── UI/
│   ├── Order/
│   │   ├── CreateOrder/
│   │   │   ├── CreateOrderAction.php    # Action: HTTP → Domain
│   │   │   ├── CreateOrderResponder.php # Responder: Domain → HTTP
│   │   │   └── CreateOrderInput.php     # Input validation
│   │   └── ListOrders/
│   │       ├── ListOrdersAction.php
│   │       └── ListOrdersResponder.php
│   └── Product/
│       └── ...
├── Domain/
│   ├── Order.php
│   └── OrderRepository.php
└── Infrastructure/
    └── ...
```

**When to use ADR variant:** API-first applications where response formatting is complex or varies by client (JSON, CSV, XML). The separate `Responder` class handles all serialisation/HTTP concerns so the Action stays free of formatting logic.
