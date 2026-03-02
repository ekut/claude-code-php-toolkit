# Service Layer

## Service Classes

Extract business logic from controllers into service classes. Inject via constructor.

```php
namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PaymentService $payment,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = $user->orders()->create([
                'status' => OrderStatus::Pending,
                'total_cents' => $this->calculateTotal($data['items']),
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create($item);
                $this->inventory->reserve($item['product_id'], $item['quantity']);
            }

            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => OrderStatus::Cancelled]);

            foreach ($order->items as $item) {
                $this->inventory->release($item->product_id, $item->quantity);
            }

            if ($order->payment) {
                $this->payment->refund($order->payment);
            }
        });
    }
}
```

## Dependency Injection

Laravel's service container autowires constructor dependencies automatically.

```php
// Controller — inject the service
final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create(
            $request->user(),
            $request->validated(),
        );

        return response()->json($order, 201);
    }
}
```

### Interface Binding

Bind interfaces to implementations in a service provider for swappable dependencies.

```php
// AppServiceProvider or a dedicated provider
public function register(): void
{
    $this->app->bind(
        PaymentGatewayInterface::class,
        StripePaymentGateway::class,
    );

    // Singleton for expensive-to-construct services
    $this->app->singleton(
        ReportCompiler::class,
        fn () => new ReportCompiler(config('reports.cache_ttl')),
    );
}
```

## Repository Pattern

Optional abstraction over Eloquent for complex query logic.

```php
namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrderRepository
{
    public function findPendingForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Order::where('user_id', $userId)
            ->where('status', OrderStatus::Pending)
            ->with(['items.product'])
            ->latest()
            ->paginate($perPage);
    }

    public function calculateRevenueForPeriod(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return Order::where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->sum('total_cents');
    }
}
```

> Use repositories when queries are complex or reused across multiple services. For simple CRUD, Eloquent in the service is fine.

## Action Classes

Single-purpose action classes for operations that don't warrant a full service.

```php
namespace App\Actions;

use App\Models\User;

final class CreateUser
{
    public function __construct(
        private readonly AvatarService $avatarService,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (isset($data['avatar'])) {
            $this->avatarService->upload($user, $data['avatar']);
        }

        $user->notify(new WelcomeNotification());

        return $user;
    }
}
```

Use from controller:

```php
public function store(StoreUserRequest $request, CreateUser $action): JsonResponse
{
    $user = $action->execute($request->validated());

    return response()->json($user, 201);
}
```

## Transactions

Always wrap multi-model writes in a transaction.

```php
use Illuminate\Support\Facades\DB;

// Simple transaction
DB::transaction(function () use ($order) {
    $order->update(['status' => OrderStatus::Completed]);
    $order->payment()->create(['amount' => $order->total_cents]);
});

// With retry on deadlock (default 1, set higher for concurrent writes)
DB::transaction(function () use ($data) {
    // ... multiple writes
}, attempts: 3);
```
