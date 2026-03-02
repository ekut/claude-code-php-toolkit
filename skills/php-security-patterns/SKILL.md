---
name: php-security-patterns
description: Security implementation patterns for PHP — authentication, authorization, CORS, security headers, PII protection, with Laravel and Symfony examples.
origin: claude-code-php-toolkit
---

# PHP Security Patterns

Implementation-level security patterns for PHP applications. Bridges the gap between security *scanning* (`php-security-scanning/`) and security *review* (`php-security-reviewer` agent) by covering how to **build secure features**.

## When to Activate

- Implementing authentication (login, API tokens, JWT, OAuth)
- Adding authorization (roles, permissions, gates, policies, voters)
- Configuring CORS for an API
- Setting up security headers (CSP, HSTS, X-Frame-Options)
- Handling PII or sensitive data in logs
- Reviewing code for security anti-patterns

---

## 1. Authentication

### 1.1 Laravel Sanctum — SPA + API Tokens

Sanctum provides two authentication mechanisms: cookie-based SPA auth and token-based API auth.

**SPA Authentication (cookie-based):**

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000')),

// routes/api.php
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (! Auth::attempt($credentials)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $request->session()->regenerate();

    return response()->json(Auth::user());
});
```

**API Token Authentication:**

```php
// Issue token with abilities (scopes)
$token = $user->createToken('api-client', ['read:orders', 'write:orders']);
return ['token' => $token->plainTextToken];

// Protect routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});

// Check abilities in controller
public function destroy(Request $request, Order $order): JsonResponse
{
    if (! $request->user()->tokenCan('write:orders')) {
        abort(403);
    }
    $order->delete();
    return response()->json(status: 204);
}
```

### 1.2 Symfony Security — Firewalls & Custom Authenticators

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: auto  # bcrypt or sodium, auto-selects best

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\ApiTokenAuthenticator
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
            logout:
                path: app_logout

    access_control:
        - { path: ^/api/public, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_API_USER }
        - { path: ^/admin, roles: ROLE_ADMIN }
```

**Custom Authenticator:**

```php
final class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $token = str_replace('Bearer ', '', $request->headers->get('Authorization', ''));

        if ('' === $token) {
            throw new CustomUserMessageAuthenticationException('No API token provided.');
        }

        return new SelfValidatingPassport(
            new UserBadge($token, fn (string $t) => $this->tokenRepository->findUserByToken($t))
        );
    }
}
```

### 1.3 JWT with lcobucci/jwt

```php
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

final readonly class JwtService
{
    private Configuration $config;

    public function __construct(string $secret)
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret)
        );
    }

    public function issue(User $user): string
    {
        $now = new \DateTimeImmutable();

        $token = $this->config->builder()
            ->issuedBy('https://api.example.com')
            ->permittedFor('https://app.example.com')
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('uid', $user->getId())
            ->withClaim('roles', $user->getRoles())
            ->getToken($this->config->signer(), $this->config->signingKey());

        return $token->toString();
    }

    public function parse(string $jwt): \Lcobucci\JWT\Token\Plain
    {
        $token = $this->config->parser()->parse($jwt);
        assert($token instanceof \Lcobucci\JWT\Token\Plain);

        $constraints = [
            new \Lcobucci\JWT\Validation\Constraint\IssuedBy('https://api.example.com'),
            new \Lcobucci\JWT\Validation\Constraint\PermittedFor('https://app.example.com'),
            new \Lcobucci\JWT\Validation\Constraint\StrictValidAt(
                new \Lcobucci\Clock\SystemClock(new \DateTimeZone('UTC'))
            ),
            new \Lcobucci\JWT\Validation\Constraint\SignedWith(
                $this->config->signer(),
                $this->config->verificationKey()
            ),
        ];

        $this->config->validator()->assert($token, ...$constraints);

        return $token;
    }
}
```

---

## 2. Authorization

### 2.1 Laravel Gates & Policies

```php
// app/Policies/OrderPolicy.php
final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            || $user->hasRole('admin');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            && $order->status === OrderStatus::Draft;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }
}

// In controller — automatic 403 on failure
public function update(Request $request, Order $order): JsonResponse
{
    $this->authorize('update', $order);
    // ...
}
```

### 2.2 spatie/laravel-permission — Role-Based Access

```php
// Assign roles and permissions
$user->assignRole('editor');
$user->givePermissionTo('publish articles');

// Middleware
Route::middleware(['role:admin'])->group(fn () => /* ... */);
Route::middleware(['permission:publish articles'])->group(fn () => /* ... */);

// Blade
@can('publish articles')
    <button>Publish</button>
@endcan
```

### 2.3 Symfony Voters

```php
#[AsTaggedItem('security.voter')]
final class OrderVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Order
            && in_array($attribute, ['VIEW', 'EDIT', 'DELETE'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return match ($attribute) {
            'VIEW' => $subject->getOwner() === $user || $this->isAdmin($user),
            'EDIT' => $subject->getOwner() === $user && $subject->isDraft(),
            'DELETE' => $this->isAdmin($user),
            default => false,
        };
    }

    private function isAdmin(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}

// In controller
$this->denyAccessUnlessGranted('EDIT', $order);
```

### 2.4 RBAC vs ABAC Decision Guide

| Factor | RBAC (Role-Based) | ABAC (Attribute-Based) |
|--------|-------------------|----------------------|
| Complexity | Low | High |
| Flexibility | Fixed roles | Dynamic rules on any attribute |
| Best for | Admin/editor/viewer patterns | Multi-tenant, time-based, contextual access |
| PHP tools | `spatie/laravel-permission`, Symfony roles | Custom Voters, Laravel Gates with closures |
| Start with | RBAC | Migrate to ABAC when RBAC gets too many roles |

---

## 3. CORS

### 3.1 Laravel CORS

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => [env('FRONTEND_URL', 'https://app.example.com')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposed_headers' => ['X-Request-Id'],
    'max_age' => 3600,
    'supports_credentials' => true,  // Required for Sanctum SPA auth
];
```

### 3.2 Symfony — nelmio/cors-bundle

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: false
        allow_origin: ['%env(FRONTEND_URL)%']
        allow_methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['X-Request-Id']
        max_age: 3600
    paths:
        '^/api/':
            allow_credentials: true
```

---

## 4. Security Headers

### 4.1 Laravel Middleware

```php
final class SecurityHeadersMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '0');  // Disabled — CSP is the modern replacement
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';"
        );
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

// Register in bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeadersMiddleware::class);
})
```

### 4.2 Symfony Event Subscriber

```php
#[AsEventListener(event: ResponseEvent::class)]
final readonly class SecurityHeadersListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
```

---

## 5. PII Protection in Logs

### Monolog Processor — Redact Sensitive Fields

```php
final class PiiRedactingProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = ['password', 'token', 'secret', 'credit_card', 'ssn', 'api_key'];
    private const REDACTED = '***REDACTED***';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(context: $this->redact($record->context));
    }

    /** @param array<string, mixed> $data */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            } elseif ($this->isSensitive($key)) {
                $data[$key] = self::REDACTED;
            }
        }
        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }
        return false;
    }
}
```

**Register in Laravel:**

```php
// AppServiceProvider::boot()
$this->app->resolving('log', function ($logger) {
    foreach ($logger->getChannels() as $channel) {
        $channel->pushProcessor(new PiiRedactingProcessor());
    }
});
```

**Register in Symfony:**

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            channels: ['!event']
    services:
        App\Logging\PiiRedactingProcessor:
            tags: [{ name: monolog.processor }]
```

---

## 6. Anti-Patterns

| Anti-Pattern | Risk | Correct Approach |
|-------------|------|-----------------|
| Rolling your own auth (custom password hashing, session management) | Timing attacks, weak hashing, session fixation | Use `password_hash()` / `password_verify()` with `PASSWORD_DEFAULT`. Use framework auth. |
| Storing JWT/tokens in `localStorage` | XSS can steal tokens | Use `httpOnly` + `Secure` + `SameSite=Lax` cookies |
| Permissive CORS (`allowed_origins: ['*']`) | Any site can make authenticated requests | Whitelist specific origins, never wildcard with credentials |
| Checking permissions in Blade/Twig only | API still accessible without UI check | Always enforce in controllers/middleware, use Blade/Twig for UX only |
| Hardcoded secrets in config files | Secrets leak via git | Use environment variables, never commit `.env` |
| Missing CSRF protection on state-changing routes | Cross-site form submission | Use framework CSRF middleware; exclude only webhook endpoints explicitly |
| Logging full request bodies | PII/passwords in plaintext logs | Use `PiiRedactingProcessor`, log only what you need |

---

## 7. Pre-Release Security Checklist

- [ ] Authentication: framework-provided or well-tested library (Sanctum, Symfony Security, lcobucci/jwt)
- [ ] Password hashing: `PASSWORD_DEFAULT` algorithm (bcrypt/argon2)
- [ ] Authorization: policies/voters on every state-changing endpoint
- [ ] CORS: specific origins, no wildcard with credentials
- [ ] Security headers: CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- [ ] CSRF: enabled on all non-API state-changing routes
- [ ] Tokens: stored in httpOnly cookies, not localStorage
- [ ] PII: sensitive data redacted from logs
- [ ] Secrets: all in environment variables, `.env` in `.gitignore`
- [ ] Dependencies: `composer audit` clean, `roave/security-advisories` installed
- [ ] Rate limiting: applied to login, registration, password reset endpoints
- [ ] Input validation: server-side validation on all user input
