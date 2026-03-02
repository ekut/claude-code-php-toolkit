# Routing

## Route Definitions

Define routes in `routes/web.php` (session, CSRF) or `routes/api.php` (stateless, token auth).

```php
// routes/api.php
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;

Route::apiResource('users', UserController::class);
Route::apiResource('users.orders', OrderController::class)->shallow();

// Custom actions beyond CRUD
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
    ->name('orders.cancel');
```

## Route Model Binding

Laravel automatically resolves Eloquent models by route parameter name.

```php
// Implicit binding — {user} matches User model by primary key
Route::get('users/{user}', [UserController::class, 'show']);

// Customise the lookup column
Route::get('users/{user:slug}', [UserController::class, 'show']);

// Scoped binding — order must belong to user
Route::get('users/{user}/orders/{order}', [OrderController::class, 'show'])
    ->scopeBindings();
```

```php
// In the controller — $user is already a User instance
public function show(User $user): JsonResponse
{
    return response()->json($user);
}
```

## Resource Controllers

Use `apiResource` for API endpoints (excludes `create` and `edit` form routes).

```php
Route::apiResource('products', ProductController::class);

// Equivalent to:
// GET    /products          → index
// POST   /products          → store
// GET    /products/{product} → show
// PUT    /products/{product} → update
// DELETE /products/{product} → destroy
```

Partial resources when you only need a subset:

```php
Route::apiResource('photos', PhotoController::class)
    ->only(['index', 'show']);
```

## Route Groups

Group routes that share middleware, prefix, or namespace.

```php
Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {
        Route::apiResource('users', Admin\UserController::class);
        Route::apiResource('settings', Admin\SettingController::class);
    });
```

## Rate Limiting

Define rate limiters in `AppServiceProvider::boot()` or a dedicated provider.

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// In a service provider boot()
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()->id);
});
```

Apply to routes:

```php
Route::middleware('throttle:api')->group(function () {
    Route::apiResource('users', UserController::class);
});

Route::post('upload', [UploadController::class, 'store'])
    ->middleware('throttle:uploads');
```

## Versioning

```php
// routes/api.php — version via prefix
Route::prefix('v1')->group(function () {
    Route::apiResource('users', V1\UserController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('users', V2\UserController::class);
});
```
