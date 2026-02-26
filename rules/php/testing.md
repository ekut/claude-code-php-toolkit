---
title: PHP Testing Requirements
scope: php
---

# PHP Testing Requirements

## Coverage Target

- Minimum 80% line coverage for new code
- Critical paths (authentication, payment, data mutation) require 95%+ coverage
- Use PCOV for fast coverage collection; Xdebug for debugging only

## Test Frameworks

- **PHPUnit 10+** as the primary test framework
- **Pest 2+** as an alternative (especially for Laravel projects)
- Do not mix PHPUnit and Pest in the same project without team agreement

## Test Structure

### Naming

- Test classes: `{ClassName}Test` in a mirrored directory structure
- Test methods: `test_it_does_something` or `it('does something')` in Pest
- Use descriptive names that explain the behavior, not the implementation

### Organization

```
tests/
├── Unit/           # Isolated tests, no I/O
├── Integration/    # Tests with real dependencies (DB, filesystem)
├── Functional/     # End-to-end feature tests
└── Fixtures/       # Shared test data
```

### Patterns

- **Arrange-Act-Assert** — clear separation in every test
- One assertion per test concept (multiple assertions are okay if testing the same behavior)
- Use data providers for testing multiple inputs:

```php
#[DataProvider('validEmailProvider')]
public function test_it_validates_email(string $email, bool $expected): void
{
    $this->assertSame($expected, $this->validator->isValid($email));
}

public static function validEmailProvider(): iterable
{
    yield 'standard email' => ['user@example.com', true];
    yield 'missing @' => ['userexample.com', false];
    yield 'empty string' => ['', false];
}
```

## Mocking

- Use PHPUnit's built-in mock builder for simple cases
- Use Mockery for complex mock scenarios
- Never mock value objects or DTOs — use real instances
- Mock at boundaries: HTTP clients, database connections, external services
- Prefer fakes and stubs over mocks when possible

## Database Testing

- Use transactions that rollback after each test
- Use factories for test data generation
- Never rely on specific database IDs
- Test against the same database engine used in production

## What to Test

- Public API of each class
- Edge cases: empty inputs, boundary values, null handling
- Error paths: exceptions, validation failures
- Integration points: database queries, API calls, file operations

## What NOT to Test

- Private methods directly (test through public API)
- Framework internals
- Third-party library behavior
- Trivial getters/setters without logic
