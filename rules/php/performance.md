---
title: PHP Performance Guidelines
scope: php
---

# PHP Performance Guidelines

## OPcache

- Enable OPcache in production (it is included with PHP)
- Key settings:
  - `opcache.enable=1`
  - `opcache.memory_consumption=256`
  - `opcache.max_accelerated_files=20000`
  - `opcache.validate_timestamps=0` in production (restart/clear cache on deploy)
  - `opcache.jit=1255` for PHP 8.0+ JIT compilation

## Preloading (PHP 7.4+)

- Use `opcache.preload` to load frequently used classes into shared memory
- Preload interfaces, abstract classes, and commonly used value objects
- Generate the preload script as part of the build process

## Memory Management

- Use generators (`yield`) for processing large datasets instead of loading everything into memory:

```php
function readLargeFile(string $path): Generator
{
    $handle = fopen($path, 'r');
    while (($line = fgets($handle)) !== false) {
        yield $line;
    }
    fclose($handle);
}
```

- Use `SplFixedArray` for large fixed-size collections
- Unset large variables when no longer needed in long-running processes
- Monitor memory with `memory_get_usage()` and `memory_get_peak_usage()`

## Database Performance

### N+1 Query Prevention

- Eager-load relationships instead of lazy-loading in loops
- Use JOINs or batch queries for related data
- Monitor query count in development (e.g., Laravel Debugbar, Symfony Profiler)

### Query Optimization

- Add indexes for columns used in WHERE, JOIN, ORDER BY
- Use `EXPLAIN` to analyze slow queries
- Prefer specific column selection over `SELECT *`
- Use pagination for large result sets
- Cache frequently accessed, rarely changing data

## Caching

- Use PSR-6 or PSR-16 cache interfaces for portability
- Cache layers (from fastest to slowest):
  1. OPcache (bytecode)
  2. APCu (in-process key-value)
  3. Redis/Memcached (shared, network)
  4. Filesystem cache
- Cache invalidation strategy: TTL-based or event-based (prefer event-based for consistency)

## String and Array Operations

- Use `str_contains()`, `str_starts_with()`, `str_ends_with()` (PHP 8.0+) instead of `strpos()` hacks
- Use `array_key_exists()` for key checks, `in_array()` with strict mode (`true` as third argument)
- For large lookups, flip arrays (`array_flip()`) and use `isset()` instead of `in_array()`
- Use `implode()`/`explode()` instead of manual string concatenation in loops

## Autoloading

- Use Composer's optimized autoloader in production: `composer dump-autoload --optimize --classmap-authoritative`
- Keep `composer.json` autoload configuration clean and accurate

## Long-running Processes

- Reset state between iterations (clear entity managers, release connections)
- Implement graceful shutdown for queue workers
- Monitor memory leaks with `memory_get_usage()`
- Use connection pooling for database connections (e.g., with Swoole or RoadRunner)

## Profiling

- Use Xdebug profiler or Blackfire for identifying bottlenecks
- Profile in conditions close to production
- Focus on the critical path first — optimize what matters
