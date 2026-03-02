# Events & Queues

## Events and Listeners

### Defining Events

```php
namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderPlaced
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}
```

### Defining Listeners

```php
namespace App\Listeners;

use App\Events\OrderPlaced;

final class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        $event->order->user->notify(
            new OrderConfirmationNotification($event->order),
        );
    }
}
```

### Queued Listeners

Implement `ShouldQueue` to process the listener asynchronously.

```php
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendOrderConfirmation implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(OrderPlaced $event): void
    {
        $event->order->user->notify(
            new OrderConfirmationNotification($event->order),
        );
    }

    public function failed(OrderPlaced $event, \Throwable $exception): void
    {
        Log::error('Failed to send order confirmation', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Auto-Discovery

Laravel 11+ auto-discovers listeners by type-hinting the event in the `handle` method. No manual registration needed.

### Dispatching Events

```php
// Dispatch from anywhere
OrderPlaced::dispatch($order);

// Or via the event helper
event(new OrderPlaced($order));
```

## Jobs (Queued Work)

```php
namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];  // Exponential backoff

    public int $timeout = 300;  // 5 minutes max

    public function __construct(
        public readonly Report $report,
    ) {}

    public function handle(): void
    {
        $data = ReportService::compile($this->report);
        $this->report->update([
            'status' => 'completed',
            'data' => $data,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->report->update(['status' => 'failed']);
        Log::error('Report generation failed', [
            'report_id' => $this->report->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Dispatching Jobs

```php
// Dispatch to default queue
GenerateReport::dispatch($report);

// Dispatch to specific queue
GenerateReport::dispatch($report)->onQueue('reports');

// Delay dispatch
GenerateReport::dispatch($report)->delay(now()->addMinutes(5));

// Dispatch after response is sent (sync-like but non-blocking)
GenerateReport::dispatchAfterResponse($report);

// Job chaining — run sequentially
Bus::chain([
    new ProcessPayment($order),
    new GenerateInvoice($order),
    new SendOrderConfirmation($order),
])->dispatch();

// Job batching — run in parallel, track progress
Bus::batch([
    new ImportChunk($chunk1),
    new ImportChunk($chunk2),
    new ImportChunk($chunk3),
])
    ->name('CSV Import')
    ->onQueue('imports')
    ->dispatch();
```

## Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry <id>

# Retry all failed jobs
php artisan queue:retry all

# Flush old failed jobs
php artisan queue:flush
```

## Task Scheduling

Define schedules in `routes/console.php` (Laravel 11+):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('reports:daily')->dailyAt('06:00');

Schedule::job(new CleanupExpiredSessions())->hourly();

Schedule::call(function () {
    DB::table('sessions')
        ->where('last_active', '<', now()->subHours(24))
        ->delete();
})->daily();
```

Run the scheduler via cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```
