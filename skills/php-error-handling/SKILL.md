---
name: php-error-handling
description: Structured error handling and logging for PHP — exception hierarchies, RFC 9457 Problem Details, PSR-3 logging, retry patterns, Sentry integration.
origin: claude-code-php-toolkit
---

# PHP Error Handling & Structured Logging

Patterns for consistent error handling, API error responses, structured logging, and error reporting in PHP applications. Covers both Laravel and Symfony.

## When to Activate

- Designing exception hierarchies for a domain
- Implementing API error responses (JSON error envelopes)
- Setting up structured logging (Monolog channels, correlation IDs)
- Adding retry logic or circuit breaker patterns
- Integrating error reporting (Sentry, Bugsnag)
- Reviewing error handling for anti-patterns

---

## 1. Exception Hierarchy

### Base Domain Exception

```php
abstract class DomainException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatus = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
```

### Typed Exceptions with HTTP Status Mapping

```php
final class EntityNotFoundException extends DomainException
{
    public function __construct(string $entity, string|int $id, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: sprintf('%s with ID "%s" not found.', $entity, $id),
            errorCode: 'ENTITY_NOT_FOUND',
            httpStatus: 404,
            previous: $previous,
        );
    }
}

final class BusinessRuleViolation extends DomainException
{
    public function __construct(string $message, string $errorCode, ?\Throwable $previous = null)
    {
        parent::__construct($message, $errorCode, httpStatus: 422, previous: $previous);
    }
}

final class InsufficientPermissions extends DomainException
{
    public function __construct(string $action, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: sprintf('Insufficient permissions to %s.', $action),
            errorCode: 'INSUFFICIENT_PERMISSIONS',
            httpStatus: 403,
            previous: $previous,
        );
    }
}

final class ExternalServiceFailure extends DomainException
{
    public function __construct(string $service, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: sprintf('External service "%s" is unavailable.', $service),
            errorCode: 'EXTERNAL_SERVICE_FAILURE',
            httpStatus: 502,
            previous: $previous,
        );
    }
}
```

---

## 2. API Error Responses — RFC 9457 Problem Details

### Response Envelope

```php
final readonly class ProblemDetails implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        public ?string $instance = null,
        /** @var array<string, mixed> */
        public array $extensions = [],
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
            'instance' => $this->instance,
            ...$this->extensions,
        ], fn ($v) => $v !== null);
    }
}
```

### Laravel Exception Handler

```php
// bootstrap/app.php (Laravel 11+)
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (DomainException $e, Request $request) {
        if (! $request->expectsJson()) {
            return null;  // Let default handler render HTML
        }

        $problem = new ProblemDetails(
            type: 'https://api.example.com/errors/' . strtolower($e->errorCode),
            title: class_basename($e),
            status: $e->httpStatus,
            detail: $e->getMessage(),
            instance: $request->getPathInfo(),
        );

        return response()->json($problem, $e->httpStatus)
            ->header('Content-Type', 'application/problem+json');
    });

    $exceptions->render(function (ValidationException $e, Request $request) {
        if (! $request->expectsJson()) {
            return null;
        }

        $problem = new ProblemDetails(
            type: 'https://api.example.com/errors/validation',
            title: 'Validation Error',
            status: 422,
            detail: 'One or more fields failed validation.',
            instance: $request->getPathInfo(),
            extensions: ['errors' => $e->errors()],
        );

        return response()->json($problem, 422)
            ->header('Content-Type', 'application/problem+json');
    });
})
```

### Symfony Exception Listener

```php
#[AsEventListener(event: ExceptionEvent::class, priority: -10)]
final readonly class ProblemDetailsListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (! in_array('application/json', $request->getAcceptableContentTypes(), true)) {
            return;
        }

        if ($exception instanceof DomainException) {
            $problem = new ProblemDetails(
                type: 'https://api.example.com/errors/' . strtolower($exception->errorCode),
                title: (new \ReflectionClass($exception))->getShortName(),
                status: $exception->httpStatus,
                detail: $exception->getMessage(),
                instance: $request->getPathInfo(),
            );

            $response = new JsonResponse($problem, $exception->httpStatus);
            $response->headers->set('Content-Type', 'application/problem+json');
            $event->setResponse($response);
        }
    }
}
```

---

## 3. Structured Logging

### PSR-3 / Monolog Best Practices

**Log levels guide:**

| Level | Use For | Example |
|-------|---------|---------|
| `emergency` | System unusable | Database server down |
| `critical` | Immediate action needed | Payment gateway timeout |
| `error` | Runtime error, operation failed | Failed to create order |
| `warning` | Unusual but recoverable | Cache miss, deprecated API called |
| `notice` | Normal but significant | User login, config change |
| `info` | Informational | Request processed, job dispatched |
| `debug` | Detailed debugging | SQL queries, API payloads (dev only) |

### Request ID Correlation

```php
final class RequestIdMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id', (string) Str::uuid());
        $request->headers->set('X-Request-Id', $requestId);

        // Push to Monolog context — all logs in this request get the ID
        Log::shareContext(['request_id' => $requestId]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
```

### Monolog Channels

```php
// config/logging.php (Laravel)
'channels' => [
    'orders' => [
        'driver' => 'daily',
        'path' => storage_path('logs/orders.log'),
        'days' => 14,
    ],
    'payments' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payments.log'),
        'days' => 30,
    ],
],

// Usage
Log::channel('orders')->info('Order created', [
    'order_id' => $order->id,
    'total' => $order->total,
    'items_count' => $order->items->count(),
]);
```

### Symfony Monolog Channels

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['orders', 'payments']
    handlers:
        orders:
            type: rotating_file
            path: '%kernel.logs_dir%/orders.log'
            max_files: 14
            channels: ['orders']
        payments:
            type: rotating_file
            path: '%kernel.logs_dir%/payments.log'
            max_files: 30
            channels: ['payments']
```

```php
// Inject named logger
public function __construct(
    #[Target('orders')]
    private readonly LoggerInterface $logger,
) {}

$this->logger->info('Order created', ['order_id' => $order->getId()]);
```

---

## 4. Retry & Circuit Breaker

### Exponential Backoff

```php
final class RetryableOperation
{
    /**
     * @template T
     * @param callable(): T $operation
     * @param int $maxAttempts
     * @param list<class-string<\Throwable>> $retryOn
     * @return T
     */
    public static function execute(
        callable $operation,
        int $maxAttempts = 3,
        array $retryOn = [\RuntimeException::class],
        int $baseDelayMs = 100,
    ): mixed {
        $attempt = 0;

        while (true) {
            try {
                return $operation();
            } catch (\Throwable $e) {
                $attempt++;
                $isRetryable = false;
                foreach ($retryOn as $class) {
                    if ($e instanceof $class) {
                        $isRetryable = true;
                        break;
                    }
                }

                if (! $isRetryable || $attempt >= $maxAttempts) {
                    throw $e;
                }

                $delayMs = $baseDelayMs * (2 ** ($attempt - 1)) + random_int(0, 50);
                usleep($delayMs * 1000);
            }
        }
    }
}

// Usage
$response = RetryableOperation::execute(
    fn () => $httpClient->request('GET', 'https://api.example.com/data'),
    maxAttempts: 3,
    retryOn: [TransportExceptionInterface::class],
);
```

### Laravel Job Retries

```php
final class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;  // Seconds, or array [10, 30, 60] for progressive
    public int $maxExceptions = 2;

    public function handle(PaymentGateway $gateway): void
    {
        $gateway->charge($this->order);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('payments')->error('Payment failed permanently', [
            'order_id' => $this->order->id,
            'exception' => $exception->getMessage(),
        ]);
        // Notify admin, refund, etc.
    }
}
```

### Symfony Messenger Retry

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000        # 1s initial delay
                    multiplier: 2      # Exponential backoff
                    max_delay: 60000   # Cap at 60s
        failure_transport: failed

        routing:
            App\Message\ProcessPayment: async
```

---

## 5. Error Reporting — Sentry Integration

### Laravel (`sentry/sentry-laravel`)

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=https://examplePublicKey@o0.ingest.sentry.io/0
```

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->reportable(function (\Throwable $e) {
        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    });

    // Don't report expected domain exceptions
    $exceptions->dontReport([
        EntityNotFoundException::class,
        BusinessRuleViolation::class,
    ]);
})
```

### Symfony (`sentry/sentry-symfony`)

```bash
composer require sentry/sentry-symfony
```

```yaml
# config/packages/sentry.yaml
sentry:
    dsn: '%env(SENTRY_DSN)%'
    options:
        environment: '%kernel.environment%'
        release: '%env(APP_VERSION)%'
        send_default_pii: false
        traces_sample_rate: 0.2
```

---

## 6. Decision Table

### Throw vs Return (Result Pattern)

| Scenario | Approach | Why |
|----------|----------|-----|
| Entity not found | Throw `EntityNotFoundException` | Exceptional — caller expects entity to exist |
| Validation failure | Return validation errors | Expected — user input is often invalid |
| Business rule violation | Throw `BusinessRuleViolation` | Caller should not have reached this state |
| External service down | Throw `ExternalServiceFailure` | Retry or circuit-break at a higher level |
| Parse error on optional data | Return `null` or default | Caller can handle absence gracefully |

### Log Level Selection

| Situation | Level | Why |
|-----------|-------|-----|
| Unhandled exception | `error` | Operation failed, needs investigation |
| Expected failure (invalid input) | `warning` | Interesting but not broken |
| External service retry succeeded | `warning` | Degraded but recovered |
| External service failed after retries | `critical` | Downstream dependency broken |
| User action (login, order) | `info` | Business audit trail |
| Cache miss | `debug` | Only useful when diagnosing performance |

---

## 7. Anti-Patterns

| Anti-Pattern | Problem | Fix |
|-------------|---------|-----|
| `catch (\Exception $e) {}` (swallow) | Hides bugs, makes debugging impossible | Catch specific types, always log or rethrow |
| `catch (\Exception $e) { Log::error($e); throw $e; }` (double log) | Exception logged twice (handler + catch) | Catch only if you handle it, otherwise let it bubble |
| Returning HTTP status codes from service layer | Couples domain logic to HTTP transport | Use domain exceptions, map to HTTP in controller/handler |
| Using error codes as strings without constants | Typos, inconsistency | Use backed enums or class constants for error codes |
| Logging `$e->getMessage()` only | Missing stack trace and context | Log `$e` as context: `Log::error('Failed', ['exception' => $e])` |
| Same log message at multiple levels | Confusing alerts | One log per event, choose the right level |
