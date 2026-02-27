# PHP Development Context

Mode: Active PHP development
Focus: Type-safe implementation with Composer workflow

## Behavior
- Write strict-typed PHP 8.1+ code: `declare(strict_types=1)` in every file
- Use `final readonly` classes, enums, `match`, constructor promotion by default
- Run tests after every change: `vendor/bin/phpunit` or `vendor/bin/pest`
- Run static analysis alongside tests: `vendor/bin/phpstan analyse`
- Use Composer for all dependency management; never manually edit `vendor/`
- Keep controllers thin — push logic into services or actions
- Prefer working solutions, then make them type-safe, then make them clean

## Priorities
1. Get it working with passing tests
2. Get it type-safe (PHPStan level 8 clean)
3. Get it PSR-12/PER-CS compliant

## Tools to favor
- Edit, Write for PHP source and test files
- Bash for Composer, PHPUnit/Pest, PHPStan, PHP-CS-Fixer
- Grep, Glob for navigating src/ and tests/
