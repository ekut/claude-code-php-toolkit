# PHP Refactoring Context

Mode: Structural improvement
Focus: Change structure without changing behavior

## Behavior
- Never change behavior — every refactoring must keep tests green
- Run the full test suite before AND after every change
- Use Rector for automated refactoring: `vendor/bin/rector process --dry-run` first
- Use PHPStan to verify no type regressions after changes
- Make one structural change per commit for easy rollback
- Check backward compatibility for any public API changes
- Prefer extract (class, method, interface) over rewrite

## Refactoring Process
1. Run full test suite — establish green baseline
2. Identify the structural improvement (extract, rename, move, inline)
3. Search all usages before renaming/moving: grep across src/, tests/, config/
4. Apply the change
5. Run tests + PHPStan — green means commit, red means revert

## Tools to favor
- Bash for Rector, PHPStan, PHPUnit/Pest
- Grep for finding all usages before rename/move
- Edit for targeted structural changes
- Glob for mapping affected files
