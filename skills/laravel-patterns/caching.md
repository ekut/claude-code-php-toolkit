# Caching

## Cache Facade

```php
use Illuminate\Support\Facades\Cache;

// Store a value (default driver)
Cache::put('user:123:profile', $profile, now()->addHours(1));

// Retrieve or compute and store
$users = Cache::remember('users:active', now()->addMinutes(30), function () {
    return User::where('active', true)->get();
});

// Retrieve or compute forever (no expiry)
$settings = Cache::rememberForever('app:settings', function () {
    return Setting::all()->pluck('value', 'key');
});

// Check and retrieve
if (Cache::has('user:123:profile')) {
    $profile = Cache::get('user:123:profile');
}

// Delete
Cache::forget('user:123:profile');

// Atomic lock (prevent concurrent execution)
$lock = Cache::lock('processing:order:123', 10);

if ($lock->get()) {
    try {
        // Process order...
    } finally {
        $lock->release();
    }
}
```

## Cache Drivers

Configure in `config/cache.php` or via `.env`:

| Driver | Use Case | Notes |
|--------|----------|-------|
| `redis` | Production default | Fast, supports tags, atomic operations |
| `memcached` | High-throughput caching | Distributed, no persistence |
| `database` | Simple setups | No extra infrastructure needed |
| `file` | Development | Filesystem-based, no setup |
| `array` | Testing | In-memory, per-request only |

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Tagged Cache

Group related cache entries for bulk invalidation. Requires `redis` or `memcached` driver.

```php
// Store tagged entries
Cache::tags(['users', 'profiles'])->put(
    "user:{$user->id}:profile",
    $profile,
    now()->addHours(1),
);

Cache::tags(['users', 'orders'])->put(
    "user:{$user->id}:recent_orders",
    $orders,
    now()->addMinutes(30),
);

// Flush all entries tagged with 'users'
Cache::tags(['users'])->flush();
```

## Cache Invalidation Strategies

### Model Observer

Invalidate cache when the underlying data changes.

```php
namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class UserObserver
{
    public function updated(User $user): void
    {
        Cache::forget("user:{$user->id}:profile");
        Cache::tags(['users'])->flush();
    }

    public function deleted(User $user): void
    {
        Cache::forget("user:{$user->id}:profile");
        Cache::tags(['users'])->flush();
    }
}
```

Register in a service provider:

```php
User::observe(UserObserver::class);
```

### Event-Based Invalidation

```php
// In a listener
final class InvalidateUserCache
{
    public function handle(UserUpdated $event): void
    {
        Cache::forget("user:{$event->user->id}:profile");
    }
}
```

## HTTP Cache Headers

```php
// In a controller
public function show(Product $product): JsonResponse
{
    return response()->json($product)
        ->header('Cache-Control', 'public, max-age=60')
        ->header('ETag', md5($product->updated_at->toIso8601String()));
}

// Using middleware
Route::middleware('cache.headers:public;max_age=300;etag')
    ->get('products/{product}', [ProductController::class, 'show']);
```

## Warming Cache

Preload frequently accessed data during deployment or via scheduler.

```php
// Artisan command
final class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm';

    public function handle(): void
    {
        Cache::rememberForever('app:settings', fn () => Setting::all()->pluck('value', 'key'));
        Cache::remember('products:featured', now()->addHours(1), fn () => Product::featured()->get());

        $this->info('Cache warmed successfully.');
    }
}

// Schedule cache warming
Schedule::command('cache:warm')->hourly();
```
