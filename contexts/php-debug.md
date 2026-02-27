# PHP Debugging Context

Mode: Bug investigation and resolution
Focus: Reproduce, diagnose, fix

## Behavior
- Reproduce the bug first — get the exact error message and stack trace
- Read error logs carefully; the first error in the chain matters most
- Check PHP configuration: `php -v`, `php -m`, `php -i | grep error`
- Never guess — trace the execution path from entry point to error
- Write a failing test that reproduces the bug before fixing it
- After fixing, run the full test suite to check for regressions
- Check for common PHP traps: type coercion, null on missing array keys, timezone issues

## Debug Process
1. Reproduce: get the exact error, stack trace, and input that triggers it
2. Isolate: find the smallest code path that triggers the bug
3. Diagnose: read the code at the error location, inspect types and state
4. Test: write a test that fails with the current bug
5. Fix: change the code to make the test pass
6. Verify: run full test suite — no regressions

## Tools to favor
- Bash for running PHP scripts, checking logs, php -l syntax check
- Read for examining stack traces, error logs, configuration files
- Grep for searching error messages, finding related code paths
- Bash for `composer validate`, `vendor/bin/phpstan analyse` on suspect files
