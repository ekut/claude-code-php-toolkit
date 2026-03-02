# Testing

## Feature Tests (HTTP)

Test the full request lifecycle — routing, middleware, validation, controller, response.

```php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->for($user)->create();
        Order::factory()->count(2)->create(); // Other user's orders

        $response = $this->actingAs($user)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'status', 'total_cents', 'created_at']],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_user_can_create_an_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price_cents' => 1500]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                'shipping_address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'postal_code' => '62701',
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_validation_rejects_empty_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'items' => [],
                'shipping_address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'postal_code' => '62701',
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_guest_cannot_access_orders(): void
    {
        $this->getJson('/api/v1/orders')
            ->assertUnauthorized();
    }
}
```

## Model Factories

```php
namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'total_cents' => fake()->numberBetween(1000, 50000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function completed(): self
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function withItems(int $count = 3): self
    {
        return $this->has(OrderItem::factory()->count($count), 'items');
    }
}
```

Usage in tests:

```php
// Simple
$order = Order::factory()->create();

// With state
$order = Order::factory()->completed()->create();

// With relationships
$order = Order::factory()->withItems(5)->create();

// Overriding attributes
$order = Order::factory()->create(['status' => OrderStatus::Cancelled]);
```

## Unit Tests

Test isolated logic without the framework.

```php
namespace Tests\Unit;

use App\Services\PricingService;
use PHPUnit\Framework\TestCase;

final class PricingServiceTest extends TestCase
{
    public function test_calculates_discount_for_bulk_orders(): void
    {
        $service = new PricingService();

        $total = $service->calculateTotal(quantity: 100, unitPrice: 1000);

        $this->assertSame(90_000, $total); // 10% bulk discount
    }

    public function test_no_discount_for_small_orders(): void
    {
        $service = new PricingService();

        $total = $service->calculateTotal(quantity: 5, unitPrice: 1000);

        $this->assertSame(5_000, $total);
    }
}
```

## Fakes

Replace real implementations with fakes for testing side effects.

```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

public function test_order_creation_dispatches_event(): void
{
    Event::fake([OrderPlaced::class]);

    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/orders', $this->validPayload());

    Event::assertDispatched(OrderPlaced::class, function ($event) use ($user) {
        return $event->order->user_id === $user->id;
    });
}

public function test_welcome_email_is_queued(): void
{
    Mail::fake();

    $user = User::factory()->create();

    Mail::assertQueued(WelcomeMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}

public function test_report_job_is_dispatched(): void
{
    Queue::fake();

    $this->actingAs($admin)->postJson('/api/v1/reports', ['type' => 'monthly']);

    Queue::assertPushed(GenerateReport::class);
    Queue::assertPushedOn('reports', GenerateReport::class);
}
```

## Database Assertions

```php
$this->assertDatabaseHas('orders', [
    'user_id' => $user->id,
    'status' => 'completed',
]);

$this->assertDatabaseMissing('orders', [
    'user_id' => $user->id,
    'status' => 'pending',
]);

$this->assertDatabaseCount('order_items', 3);

$this->assertSoftDeleted('orders', ['id' => $order->id]);
```

## Running Tests

```bash
# All tests
php artisan test

# Specific test class
php artisan test --filter=OrderControllerTest

# With coverage
php artisan test --coverage --min=80

# Parallel execution
php artisan test --parallel
```
