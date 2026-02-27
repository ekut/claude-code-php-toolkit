# Action-Based Building Blocks

## Single Action Controller

An invokable controller that handles one HTTP endpoint:

```php
final readonly class CreateOrderAction
{
    public function __construct(
        private CreateOrderHandler $handler,
    ) {}

    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        $command = new CreateOrderCommand(
            customerId: $request->integer('customer_id'),
            lines: array_map(
                fn (array $line) => new OrderLineInput(
                    productId: $line['product_id'],
                    quantity: $line['quantity'],
                ),
                $request->validated('lines'),
            ),
        );

        $orderId = ($this->handler)($command);

        return new JsonResponse(
            ['id' => $orderId, 'status' => 'created'],
            Response::HTTP_CREATED,
        );
    }
}

final readonly class CancelOrderAction
{
    public function __construct(
        private CancelOrderHandler $handler,
    ) {}

    public function __invoke(int $orderId, CancelOrderRequest $request): JsonResponse
    {
        ($this->handler)(new CancelOrderCommand(
            orderId: $orderId,
            reason: $request->validated('reason'),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

final readonly class ListOrdersAction
{
    public function __construct(
        private ListOrdersHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = ($this->handler)(new ListOrdersQuery(
            customerId: $request->query->getInt('customer_id'),
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 20),
        ));

        return new JsonResponse($result);
    }
}
```

## Command Handler

Handles a write operation. Accepts an immutable command, performs the action, returns minimal result:

```php
final readonly class CreateOrderCommand
{
    public function __construct(
        public int $customerId,
        /** @var list<OrderLineInput> */
        public array $lines,
    ) {}
}

final readonly class OrderLineInput
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}

final readonly class CreateOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductRepository $products,
        private EventDispatcherInterface $events,
    ) {}

    public function __invoke(CreateOrderCommand $command): int
    {
        $order = new Order();
        $order->setCustomerId($command->customerId);
        $order->setStatus(OrderStatus::Pending);

        foreach ($command->lines as $line) {
            $product = $this->products->findOrFail($line->productId);

            if ($product->getStock() < $line->quantity) {
                throw new InsufficientStockException($product->getId());
            }

            $order->addLine($product, $line->quantity, $product->getPrice());
            $product->decrementStock($line->quantity);
        }

        $this->orders->save($order);
        $this->events->dispatch(new OrderCreatedEvent($order->getId()));

        return $order->getId();
    }
}
```

## Query Handler

Handles a read operation. Returns DTOs, never modifies state:

```php
final readonly class GetOrderQuery
{
    public function __construct(
        public int $orderId,
    ) {}
}

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderReadRepository $repository,
    ) {}

    public function __invoke(GetOrderQuery $query): OrderResponseDTO
    {
        return $this->repository->findSummary($query->orderId)
            ?? throw new OrderNotFoundException($query->orderId);
    }
}

final readonly class ListOrdersQuery
{
    public function __construct(
        public int $customerId,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}

final readonly class ListOrdersHandler
{
    public function __construct(
        private OrderReadRepository $repository,
    ) {}

    public function __invoke(ListOrdersQuery $query): PaginatedResponseDTO
    {
        return $this->repository->findByCustomerPaginated(
            $query->customerId,
            $query->page,
            $query->perPage,
        );
    }
}
```

## ADR Responder

Separates domain result from HTTP response formatting:

```php
final readonly class JsonResponder
{
    public function success(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(['data' => $data], $status);
    }

    public function created(mixed $data): JsonResponse
    {
        return $this->success($data, Response::HTTP_CREATED);
    }

    public function noContent(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    public function paginated(PaginatedResponseDTO $result): JsonResponse
    {
        return new JsonResponse([
            'data' => $result->items,
            'meta' => [
                'total' => $result->total,
                'page' => $result->page,
                'per_page' => $result->perPage,
                'has_next' => $result->hasNextPage(),
            ],
        ]);
    }
}

final readonly class CsvResponder
{
    public function respond(string $filename, iterable $rows, array $headers): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($rows, $headers): void {
                $output = fopen('php://output', 'wb');
                fputcsv($output, $headers);

                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }
}

// Usage in ADR-style action:
final readonly class CreateOrderAction
{
    public function __construct(
        private CreateOrderHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        $command = CreateOrderCommand::fromRequest($request);
        $orderId = ($this->handler)($command);

        return $this->responder->created(['id' => $orderId]);
    }
}
```

## Route Registration

Invokable controllers integrate with standard framework routing:

```php
// Laravel (routes/api.php)
Route::prefix('orders')->group(function () {
    Route::get('/', ListOrdersAction::class);
    Route::post('/', CreateOrderAction::class);
    Route::get('/{order}', GetOrderAction::class);
    Route::post('/{order}/cancel', CancelOrderAction::class);
    Route::get('/export', ExportOrdersCsvAction::class);
});

// Symfony (config/routes.php or attributes)
#[Route('/api/orders', name: 'order_create', methods: ['POST'])]
final readonly class CreateOrderAction { /* ... */ }

#[Route('/api/orders/{id}', name: 'order_get', methods: ['GET'])]
final readonly class GetOrderAction { /* ... */ }

// Symfony routing config alternative
return static function (RoutingConfigurator $routes): void {
    $routes->add('order_create', '/api/orders')
        ->controller(CreateOrderAction::class)
        ->methods(['POST']);
    $routes->add('order_get', '/api/orders/{id}')
        ->controller(GetOrderAction::class)
        ->methods(['GET']);
};
```
