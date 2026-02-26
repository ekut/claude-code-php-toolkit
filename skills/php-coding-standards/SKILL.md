---
name: php-coding-standards
description: Use this skill when setting up PHP projects, reviewing PSR compliance, configuring autoloading, or setting up PHP-CS-Fixer/Pint. Covers PSR-1, PSR-4, PSR-12, PER Coding Style 2.0.
origin: claude-code-php-toolkit
---

# PHP Coding Standards

## When to Activate

- Setting up a new PHP project
- Configuring autoloading or namespaces
- Reviewing code for PSR compliance
- Configuring PHP-CS-Fixer or Laravel Pint

## PSR-1: Basic Coding Standard

- Files MUST use only `<?php` or `<?=` tags
- Files MUST use only UTF-8 without BOM
- A file SHOULD declare symbols (classes, functions, constants) OR cause side-effects, but NOT both
- Namespaces and classes MUST follow PSR-4 autoloading
- Class names MUST be in `PascalCase`
- Class constants MUST be in `UPPER_SNAKE_CASE`
- Method names MUST be in `camelCase`

## PSR-4: Autoloading

Map namespace prefixes to directories in `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "App\\Tests\\": "tests/"
        }
    }
}
```

### Rules

- The fully qualified class name must map to a file path
- `App\Domain\User\UserRepository` → `src/Domain/User/UserRepository.php`
- Each namespace segment maps to a directory
- The class name maps to the filename (case-sensitive)

After changes: `composer dump-autoload`

## PSR-12: Extended Coding Style

### File Structure

```php
<?php

declare(strict_types=1);

namespace App\Domain;

use App\Shared\EventDispatcher;
use DateTimeImmutable;

final class User
{
    // ...
}
```

Order of elements:
1. Opening `<?php` tag
2. `declare(strict_types=1);`
3. Namespace declaration
4. `use` imports (grouped: PHP core, third-party, project)
5. Class/interface/trait/enum declaration

### Control Structures

```php
if ($condition) {
    // body
} elseif ($otherCondition) {
    // body
} else {
    // body
}

foreach ($items as $key => $value) {
    // body
}

try {
    // body
} catch (FirstException | SecondException $e) {
    // handler
} finally {
    // cleanup
}
```

### Methods

```php
public function methodName(
    string $param1,
    int $param2,
    ?DateTimeImmutable $param3 = null,
): ReturnType {
    // body
}
```

## PER Coding Style 2.0

PER-CS 2.0 extends PSR-12 with modern PHP features:

- Enum declarations follow class formatting rules
- Readonly properties and constructor promotion are permitted
- Trailing commas in parameter lists and `match` arms
- Named arguments in function calls
- Intersection and union types formatting
- `match` expression formatting:

```php
$result = match (true) {
    $value > 100 => 'high',
    $value > 50 => 'medium',
    default => 'low',
};
```

## PHP-CS-Fixer Configuration

Create `.php-cs-fixer.dist.php` in project root:

```php
<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

return (new Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'parameters']],
    ]);
```

Run: `vendor/bin/php-cs-fixer fix`
Dry run: `vendor/bin/php-cs-fixer fix --dry-run --diff`

## Laravel Pint Configuration

Create `pint.json` in project root:

```json
{
    "preset": "per",
    "rules": {
        "declare_strict_types": true,
        "no_unused_imports": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        }
    }
}
```

Run: `vendor/bin/pint`
Dry run: `vendor/bin/pint --test`

## Namespace Organization

Organize namespaces by domain concept, not technical layer:

```
src/
├── Domain/
│   ├── User/
│   │   ├── User.php               # App\Domain\User\User
│   │   ├── UserId.php             # App\Domain\User\UserId
│   │   ├── UserRepository.php     # App\Domain\User\UserRepository
│   │   └── Event/
│   │       └── UserRegistered.php # App\Domain\User\Event\UserRegistered
│   └── Order/
│       ├── Order.php
│       └── OrderRepository.php
├── Application/
│   └── User/
│       └── RegisterUserHandler.php
└── Infrastructure/
    └── Persistence/
        └── DoctrineUserRepository.php
```

## Checklist

- [ ] PSR-4 autoloading configured correctly
- [ ] `declare(strict_types=1)` in every file
- [ ] Use imports grouped and sorted alphabetically
- [ ] No unused imports
- [ ] Trailing commas in multiline constructs
- [ ] PHP-CS-Fixer or Pint configured and running
- [ ] Namespace matches directory structure exactly
