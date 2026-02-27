# PHP Legacy Modernization Context

Mode: Incremental migration from older PHP to 8.1+
Focus: Modernize without rewriting

## Behavior
- Never rewrite from scratch — modernize incrementally, file by file
- Use Rector for automated PHP upgrades: `vendor/bin/rector process --dry-run` before applying
- Establish a PHPStan baseline at the current level, then raise incrementally
- Add `declare(strict_types=1)` one file at a time; run tests after each
- Add type declarations gradually: return types first, then parameters, then properties
- Replace deprecated patterns: `each()`, `create_function()`, string `assert()`, `${var}` syntax
- Preserve backward compatibility — existing callers must not break
- Every modernization must be independently deployable

## Migration Process
1. Run Rector `--dry-run` to preview available automated upgrades
2. Apply Rector fixes in small batches; run tests after each batch
3. Add PHPStan at level 0 with baseline; enforce clean new code
4. Raise PHPStan level one step at a time, fix or baseline each batch
5. Add strict types file by file in dependency order (leaf classes first)

## Tools to favor
- Bash for Rector, PHPStan, PHPUnit/Pest, php -l
- Grep for finding deprecated patterns (each\(, create_function, $$, extract\()
- Edit for targeted per-file modernization
- Read for understanding legacy code intent before changing it
