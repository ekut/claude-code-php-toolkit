---
name: php-testing
description: Use this skill when writing PHP tests, configuring PHPUnit or Pest, debugging test failures, or setting up code coverage. Covers PHPUnit 10+, Pest 2+, Mockery, data providers, PCOV/Xdebug coverage.
origin: claude-code-php-toolkit
---

# PHP Testing

## When to Activate

- Writing new tests for PHP code
- Setting up PHPUnit or Pest in a project
- Debugging test failures
- Configuring code coverage
- Reviewing test quality

## PHPUnit 10+ Setup

### Configuration (`phpunit.xml.dist`)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="vendor/autoload.php"
    colors="true"
    failOnRisky="true"
    failOnWarning="true"
    cacheDirectory=".phpunit.cache"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>

    <coverage>
        <report>
            <html outputDirectory="coverage"/>
        </report>
    </coverage>

    <php>
        <env name="APP_ENV" value="testing"/>
    </php>
</phpunit>
```

### Basic Test

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_money_with_valid_amount(): void
    {
        $money = new Money(1000, 'USD');

        $this->assertSame(1000, $money->amount);
        $this->assertSame('USD', $money->currency);
    }

    #[Test]
    public function it_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be non-negative');

        new Money(-1, 'USD');
    }

    #[Test]
    #[DataProvider('additionProvider')]
    public function it_adds_money(int $a, int $b, int $expected): void
    {
        $result = (new Money($a, 'USD'))->add(new Money($b, 'USD'));

        $this->assertSame($expected, $result->amount);
    }

    public static function additionProvider(): iterable
    {
        yield 'zero plus zero' => [0, 0, 0];
        yield 'positive values' => [100, 200, 300];
        yield 'zero plus value' => [0, 500, 500];
    }
}
```

### PHPUnit Attributes (replacing annotations)

```php
use PHPUnit\Framework\Attributes\{
    Test,
    DataProvider,
    Depends,
    Group,
    CoversClass,
    TestDox,
    Before,
    After,
};

#[CoversClass(UserService::class)]
final class UserServiceTest extends TestCase
{
    #[Before]
    protected function setUpDependencies(): void { /* ... */ }

    #[Test]
    #[Group('integration')]
    #[TestDox('it registers a new user with valid data')]
    public function it_registers_user(): void { /* ... */ }
}
```

## Pest 2+ Setup

### Configuration (`tests/Pest.php`)

```php
<?php

declare(strict_types=1);

uses(Tests\TestCase::class)->in('Unit', 'Integration');
```

### Basic Test

```php
<?php

declare(strict_types=1);

use App\Domain\Money;

describe('Money', function (): void {
    it('creates money with valid amount', function (): void {
        $money = new Money(1000, 'USD');

        expect($money->amount)->toBe(1000);
        expect($money->currency)->toBe('USD');
    });

    it('rejects negative amount', function (): void {
        expect(fn() => new Money(-1, 'USD'))
            ->toThrow(InvalidArgumentException::class, 'Amount must be non-negative');
    });

    it('adds money correctly', function (int $a, int $b, int $expected): void {
        $result = (new Money($a, 'USD'))->add(new Money($b, 'USD'));

        expect($result->amount)->toBe($expected);
    })->with([
        'zero plus zero' => [0, 0, 0],
        'positive values' => [100, 200, 300],
        'zero plus value' => [0, 500, 500],
    ]);
});
```

## Mocking

### PHPUnit Mock Builder

```php
$repository = $this->createMock(UserRepository::class);
$repository
    ->expects($this->once())
    ->method('save')
    ->with($this->isInstanceOf(User::class));
```

### Mockery

```php
use Mockery;

$mailer = Mockery::mock(Mailer::class);
$mailer
    ->shouldReceive('send')
    ->once()
    ->with(Mockery::type(Email::class))
    ->andReturnTrue();

// In tearDown or using Mockery integration trait
Mockery::close();
```

### Test Doubles Hierarchy

1. **Dummy** — passed but never used
2. **Stub** — returns predefined values
3. **Spy** — records calls for later assertion
4. **Mock** — stubs + expectations on calls
5. **Fake** — working implementation (e.g., in-memory repository)

Prefer fakes for repositories and stubs for simple returns. Use mocks sparingly.

## Coverage

### PCOV (recommended for speed)

Install: `pecl install pcov`

```ini
; php.ini
extension=pcov
pcov.enabled=1
```

Run: `php -dpcov.enabled=1 vendor/bin/phpunit --coverage-html coverage`

### Xdebug

```ini
; php.ini
xdebug.mode=coverage
```

Run: `XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage`

### Coverage Thresholds

```xml
<!-- phpunit.xml.dist -->
<coverage>
    <report>
        <clover outputFile="coverage.xml"/>
    </report>
</coverage>
```

Enforce in CI: `vendor/bin/phpunit --coverage-clover coverage.xml && php coverage-check.php 80`

## Checklist

- [ ] Tests follow Arrange-Act-Assert pattern
- [ ] Descriptive test method names
- [ ] Data providers for multiple input scenarios
- [ ] Mocking only at boundaries (I/O, external services)
- [ ] Coverage configured with PCOV or Xdebug
- [ ] Separate unit and integration test suites
- [ ] Tests run in isolation (no shared state between tests)
- [ ] No logic in tests (no if/else, loops)
