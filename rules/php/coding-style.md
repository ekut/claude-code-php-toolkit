---
title: PHP Coding Style
scope: php
---

# PHP Coding Style

## Standards

Follow PSR-12 and PER Coding Style 2.0 as the baseline. Key rules:

### File Structure

- Start every PHP file with `<?php` (no closing tag)
- Declare `declare(strict_types=1);` in every file
- One class/interface/trait/enum per file
- Follow PSR-4 autoloading: namespace maps to directory structure

### Naming

- **Classes, interfaces, traits, enums**: `PascalCase`
- **Methods, functions, variables**: `camelCase`
- **Constants**: `UPPER_SNAKE_CASE`
- **Namespaces**: `PascalCase`, matching directory structure

### Type Declarations

- Always declare parameter types, return types, and property types
- Use union types (`string|int`) instead of docblock `@param string|int`
- Use `mixed` explicitly when the type is truly unknown
- Use `void` return type for methods that return nothing
- Prefer `readonly` properties over getters for value objects
- Use constructor promotion for simple DTOs and value objects

### Modern PHP (8.1+)

- Use `enum` instead of class constants for fixed sets of values
- Use `readonly` properties for immutable data
- Use `match` instead of `switch` when returning values
- Use named arguments for clarity when calling functions with many parameters
- Use first-class callable syntax `strlen(...)` instead of `'strlen'` strings
- Use null-safe operator `?->` instead of nested null checks
- Use fiber-based async when appropriate

### Class Design

- Prefer `final` classes by default — only remove `final` when extension is explicitly needed
- Prefer composition over inheritance
- Keep classes small and focused (Single Responsibility)
- Use interfaces for contracts, abstract classes only when sharing implementation

### Formatting

- 4 spaces indentation (no tabs)
- Opening braces on the same line for control structures
- Opening braces on the next line for classes and methods
- One blank line between methods
- No trailing whitespace
- Single blank line at end of file

### Tooling

- Use PHP-CS-Fixer or Laravel Pint for automated formatting
- Configure rules to match PSR-12/PER-CS
- Run formatter on save or pre-commit
