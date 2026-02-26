---
name: php-patterns
description: Modern PHP idioms and best practices for PHP 8.1+ — enums, readonly, match, fibers, constructor promotion, union/intersection types, null-safe operator, first-class callables.
origin: claude-code-php-toolkit
---

# PHP Patterns & Idioms

## When to Activate

- Writing new PHP classes, functions, or modules
- Reviewing PHP code for idiomatic usage
- Refactoring legacy PHP code to modern standards
- Answering questions about PHP best practices

## Enums (PHP 8.1+)

Use enums instead of class constants for fixed sets of values:

```php
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Suspended => 'Suspended',
        };
    }
}

// Usage
$status = Status::from('active');
$status = Status::tryFrom($input); // returns null instead of throwing
```

## Readonly Properties & Classes (PHP 8.1/8.2)

```php
// Readonly properties for immutable data
final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}
}

// Readonly class (PHP 8.2) — all properties are implicitly readonly
final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}
}
```

## Constructor Promotion

Combine parameter declaration with property declaration:

```php
final class CreateUserCommand
{
    public function __construct(
        private readonly string $email,
        private readonly string $name,
        private readonly ?string $phone = null,
    ) {}
}
```

## Match Expression

Use `match` instead of `switch` when mapping values:

```php
$result = match ($statusCode) {
    200 => 'OK',
    301 => 'Moved Permanently',
    404 => 'Not Found',
    500 => 'Internal Server Error',
    default => 'Unknown',
};
```

`match` uses strict comparison and throws `UnhandledMatchError` if no arm matches (when no `default`).

## Named Arguments

Use named arguments for readability, especially with boolean flags or many parameters:

```php
$user = new User(
    name: 'John',
    email: 'john@example.com',
    isAdmin: false,
);

array_slice($array, offset: 2, length: 3, preserve_keys: true);
```

## Null-Safe Operator

Chain method calls safely with `?->`:

```php
// Before
$country = null;
if ($user !== null) {
    $address = $user->getAddress();
    if ($address !== null) {
        $country = $address->getCountry();
    }
}

// After
$country = $user?->getAddress()?->getCountry();
```

## First-Class Callable Syntax

Reference functions and methods as closures:

```php
// Before
$lengths = array_map('strlen', $strings);
$filtered = array_filter($items, [$this, 'isValid']);

// After
$lengths = array_map(strlen(...), $strings);
$filtered = array_filter($items, $this->isValid(...));
```

## Union & Intersection Types

```php
// Union: accepts either type
function process(string|int $value): string|false
{
    // ...
}

// Intersection: must implement all types
function handle(Countable&Iterator $collection): void
{
    // ...
}

// DNF (Disjunctive Normal Form) types (PHP 8.2)
function parse((Stringable&DateTimeInterface)|string $input): void
{
    // ...
}
```

## Arrow Functions

Short closures that capture variables by value:

```php
$doubled = array_map(fn(int $n): int => $n * 2, $numbers);

$filtered = array_filter(
    $users,
    fn(User $user): bool => $user->isActive() && $user->age >= 18,
);
```

## Fibers (PHP 8.1+)

Cooperative multitasking primitives:

```php
$fiber = new Fiber(function (): void {
    $value = Fiber::suspend('first');
    echo "Resumed with: $value\n";
    Fiber::suspend('second');
});

$result = $fiber->start();     // 'first'
$result = $fiber->resume('hello'); // 'second'
```

Use fibers through libraries (ReactPHP, Revolt) rather than directly in application code.

## Common Design Patterns in PHP

### Value Object

```php
final readonly class EmailAddress
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

### Repository Pattern

```php
interface UserRepository
{
    public function findById(UserId $id): ?User;
    public function save(User $user): void;
    public function remove(User $user): void;
}
```

### Service with Dependency Injection

```php
final class UserRegistrationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly EventDispatcher $events,
    ) {}

    public function register(RegisterUserCommand $command): User
    {
        $user = User::create(
            email: new EmailAddress($command->email),
            password: $this->hasher->hash($command->password),
        );

        $this->users->save($user);
        $this->events->dispatch(new UserRegistered($user));

        return $user;
    }
}
```

## Checklist

- [ ] Using strict types (`declare(strict_types=1)`)
- [ ] Type declarations on all parameters, returns, and properties
- [ ] Enums for fixed value sets (not class constants)
- [ ] Readonly properties for immutable data
- [ ] Match expressions instead of switch for value mapping
- [ ] Null-safe operator instead of nested null checks
- [ ] Constructor promotion for simple classes
- [ ] Named arguments for readability
- [ ] Final classes by default
