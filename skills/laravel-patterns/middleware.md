# Middleware

## Creating Middleware

```bash
php artisan make:middleware EnsureJsonResponse
```

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
```

## Middleware with Parameters

```php
final class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()?->hasAnyRole($roles)) {
            abort(403, 'Insufficient permissions.');
        }

        return $next($request);
    }
}
```

Apply with parameters:

```php
Route::middleware('role:admin,editor')->group(function () {
    Route::apiResource('posts', PostController::class);
});
```

## Middleware Groups

Register middleware in `bootstrap/app.php` (Laravel 11+):

```php
// bootstrap/app.php
use App\Http\Middleware\EnsureJsonResponse;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware (runs on every request)
        $middleware->append(EnsureJsonResponse::class);

        // Named middleware (use in routes)
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Middleware groups
        $middleware->appendToGroup('api', [
            EnsureJsonResponse::class,
        ]);
    })
    ->create();
```

## Before and After Middleware

```php
// Before middleware — runs before the controller
final class LogRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('Incoming request', [
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        return $next($request);
    }
}

// After middleware — runs after the controller
final class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

## Terminable Middleware

Runs after the response has been sent to the browser. Useful for logging, analytics, and cleanup.

```php
final class CollectMetrics implements TerminableMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('start_time', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $duration = microtime(true) - $request->attributes->get('start_time');

        Metrics::record('http_request', [
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
        ]);
    }
}
```

## Excluding Routes from Middleware

```php
$middleware->redirectGuestsTo('/login');

// Exclude specific routes from CSRF verification (Laravel 11)
$middleware->validateCsrfTokens(except: [
    'webhooks/*',
    'api/stripe/webhook',
]);
```
