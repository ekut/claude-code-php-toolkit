---
name: php-security-scanning
description: Automated security scanning pipeline for PHP projects — Composer audit, Psalm taint analysis, PHPStan security rules, environment secrets check, debug statement detection, and CI/CD integration.
origin: claude-code-php-toolkit
---

# PHP Security Scanning

A 5-phase security scanning pipeline for PHP projects. Run before deployments, after dependency updates, and at regular intervals during development.

## When to Activate

- Before deploying to production
- After `composer update` or adding new dependencies
- During CI/CD pipeline security gates
- Periodic security hygiene checks (weekly/monthly)
- When onboarding to a new codebase
- After modifying authentication, authorization, or input handling code

## Phase 1: Dependency Audit

Check for known vulnerabilities in Composer dependencies.

```bash
# Composer built-in audit (Composer 2.4+)
composer audit

# With specific format for CI parsing
composer audit --format=json

# Check for abandoned packages
composer audit --abandoned=report
```

**Pass criteria:** Zero known CVEs. If vulnerabilities are found, check if patches are available.

### Roave Security Advisories

Prevent installing packages with known vulnerabilities at the Composer level.

```bash
# Install as a dev dependency
composer require --dev roave/security-advisories:dev-latest

# This package has no code — it uses Composer's conflict rules
# to prevent installing vulnerable versions
```

## Phase 2: Static Taint Analysis

Detect data flow from untrusted sources (user input) to sensitive sinks (SQL, file ops, output).

### Psalm Taint Analysis

```bash
# Run taint analysis (requires Psalm configured)
vendor/bin/psalm --taint-analysis

# With specific report format
vendor/bin/psalm --taint-analysis --output-format=json
```

Psalm tracks taint from sources like `$_GET`, `$_POST`, `$_COOKIE`, `$request->input()` to sinks like `echo`, `query()`, `exec()`, `file_get_contents()`.

```php
// Psalm will flag this as TaintedHtml
public function show(Request $request): Response
{
    $name = $request->query('name');
    return new Response("<h1>Hello {$name}</h1>");  // XSS!
}

// Fix: escape output
return new Response('<h1>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h1>');
```

### PHPStan Security Rules

```bash
# Install PHPStan security extensions
composer require --dev phpstan/phpstan-strict-rules
composer require --dev ekino/phpstan-banned-code

# Run analysis
vendor/bin/phpstan analyse --memory-limit=512M
```

Configure banned code in `phpstan.neon`:

```neon
includes:
    - vendor/ekino/phpstan-banned-code/extension.neon

parameters:
    bannedCode:
        functions:
            - { name: 'exec', message: 'Use symfony/process instead' }
            - { name: 'shell_exec', message: 'Use symfony/process instead' }
            - { name: 'system', message: 'Use symfony/process instead' }
            - { name: 'passthru', message: 'Use symfony/process instead' }
            - { name: 'eval', message: 'eval() is never safe' }
            - { name: 'extract', message: 'extract() causes variable injection' }
            - { name: 'compact', message: 'Prefer explicit arrays' }
```

## Phase 3: Environment and Secrets Check

Scan for hardcoded secrets, leaked credentials, and unsafe environment handling.

```bash
# Check for hardcoded passwords and API keys
grep -rn "password\s*=\s*['\"]" src/ config/ --include="*.php" --include="*.yaml" --include="*.yml"
grep -rn "api_key\|secret_key\|PRIVATE.KEY\|sk-\|sk_live_\|sk_test_" src/ config/ --include="*.php" --include="*.yaml"

# Check for .env in version control
git ls-files --error-unmatch .env 2>/dev/null && echo "WARNING: .env is tracked by git!"

# Verify .env is in .gitignore
grep -q "^\.env$" .gitignore || echo "WARNING: .env not in .gitignore"

# Check for APP_DEBUG=true in production configs
grep -rn "APP_DEBUG.*true\|APP_ENV.*local" .env.production .env.staging 2>/dev/null
```

### PHP-Specific Secret Patterns

| Pattern | Risk | Example |
|---------|------|---------|
| Hardcoded DB password | Credential leak | `'password' => 'secret123'` |
| API keys in source | Token exposure | `$apiKey = 'sk-abc123...'` |
| Private keys inline | Auth bypass | `$privateKey = '-----BEGIN RSA'` |
| `.env` committed | Full config leak | Git tracking `.env` file |
| Debug mode in prod | Info disclosure | `APP_DEBUG=true` |

## Phase 4: Debug Statement Detection

Find debug statements that should never reach production.

```bash
# Common PHP debug statements
grep -rn "var_dump\|print_r\|dd(\|dump(\|ray(" src/ --include="*.php"

# Error logging leftovers
grep -rn "error_log(" src/ --include="*.php"

# Laravel-specific debug helpers
grep -rn "Log::debug\|info(\|logger(" src/ --include="*.php" | grep -v "LoggerInterface"

# Xdebug triggers
grep -rn "xdebug_break\|xdebug_var_dump" src/ --include="*.php"

# PHP error display directives
grep -rn "ini_set.*display_errors\|ini_set.*error_reporting" src/ --include="*.php"
```

**Pass criteria:** Zero debug statements in `src/`. Logging calls (`Log::info()`, etc.) are acceptable if intentional.

## Phase 5: OWASP Top 10 Pattern Scan

Automated checks for common vulnerability patterns.

### SQL Injection

```bash
# Raw query concatenation
grep -rn "->whereRaw\|->selectRaw\|->havingRaw\|DB::raw\|DB::statement" src/ --include="*.php"

# Manual PDO without prepared statements
grep -rn "->query(\|->exec(" src/ --include="*.php"
```

Review each match — `whereRaw()` and `DB::raw()` are safe if parameterized:

```php
// SAFE — parameterized
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

// UNSAFE — concatenated
DB::select("SELECT * FROM users WHERE email = '{$email}'");
```

### Cross-Site Scripting (XSS)

```bash
# Unescaped output in Blade templates
grep -rn "{!!\|@php\s*echo" resources/views/ --include="*.blade.php"

# Raw HTML output in controllers
grep -rn "->setContent\|new Response(" src/ --include="*.php"
```

### Mass Assignment

```bash
# Models without $fillable or $guarded
# Check each model has explicit mass assignment protection
find src/ app/ -name "*.php" -path "*/Models/*" -exec grep -L "fillable\|guarded" {} \;
```

### Insecure Deserialization

```bash
# Unsafe unserialize usage
grep -rn "unserialize(" src/ --include="*.php"

# Should use json_decode or symfony/serializer instead
```

## Severity Classification

| Severity | Response Time | Examples |
|----------|--------------|---------|
| Critical | Fix immediately | Known CVE with exploit, hardcoded production credentials |
| High | Fix before next deploy | SQL injection pattern, missing CSRF protection |
| Medium | Fix within sprint | Debug statements in src/, abandoned dependencies |
| Low | Track and plan | Missing Content-Security-Policy headers, verbose error messages |
| Info | Awareness only | Dependencies with newer major versions available |

## CI/CD Integration

### GitHub Actions

```yaml
name: Security Scan
on: [push, pull_request]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer

      - run: composer install --no-interaction

      # Phase 1: Dependency audit
      - name: Composer Audit
        run: composer audit

      # Phase 2: Static analysis
      - name: PHPStan
        run: vendor/bin/phpstan analyse --error-format=github

      - name: Psalm Taint Analysis
        run: vendor/bin/psalm --taint-analysis --output-format=github
        continue-on-error: true

      # Phase 4: Debug statements
      - name: Debug Statement Check
        run: |
          if grep -rn "var_dump\|print_r\|dd(\|dump(" src/ --include="*.php"; then
            echo "::error::Debug statements found in src/"
            exit 1
          fi
```

### GitLab CI

```yaml
security-scan:
  stage: test
  script:
    - composer audit
    - vendor/bin/phpstan analyse
    - vendor/bin/psalm --taint-analysis
    - |
      if grep -rn "var_dump\|print_r\|dd(\|dump(" src/ --include="*.php"; then
        echo "Debug statements found in src/"
        exit 1
      fi
```

## OWASP Mapping

| OWASP Category | Phase | Tool |
|----------------|-------|------|
| A01 Broken Access Control | 5 | Manual review, policy tests |
| A02 Cryptographic Failures | 3 | Secrets check, config review |
| A03 Injection | 2, 5 | Psalm taint, raw query scan |
| A04 Insecure Design | — | Architecture review (agent) |
| A05 Security Misconfiguration | 3 | Env check, debug detection |
| A06 Vulnerable Components | 1 | Composer audit, Roave |
| A07 Authentication Failures | 5 | Auth pattern review |
| A08 Data Integrity Failures | 5 | Deserialization check |
| A09 Logging Failures | 4 | Debug statement scan |
| A10 SSRF | 2 | Psalm taint analysis |

## Output Template

```
SECURITY SCAN REPORT
====================
Phase 1 — Dependencies:  [PASS/FAIL] (CVEs: X, abandoned: X)
Phase 2 — Taint Analysis: [PASS/FAIL] (Psalm: X issues, PHPStan: X issues)
Phase 3 — Secrets:        [PASS/FAIL] (hardcoded: X, .env exposed: Y/N)
Phase 4 — Debug:          [PASS/FAIL] (debug statements: X)
Phase 5 — OWASP Patterns: [PASS/FAIL] (SQLi: X, XSS: X, mass assign: X)

Overall: [SECURE / NEEDS ATTENTION / CRITICAL]

Critical Issues:
1. ...

Recommendations:
1. ...
```

## Checklist

- [ ] `composer audit` reports no known CVEs
- [ ] `roave/security-advisories` installed as dev dependency
- [ ] Psalm taint analysis passes (or findings reviewed and addressed)
- [ ] PHPStan security rules pass at configured level
- [ ] No hardcoded secrets in source files
- [ ] `.env` is in `.gitignore` and not tracked by git
- [ ] `APP_DEBUG=false` in production environment
- [ ] No debug statements (`var_dump`, `dd`, `dump`, `ray`) in `src/`
- [ ] All database queries use parameterized statements
- [ ] Blade templates use `{{ }}` (escaped) by default, `{!! !!}` only with sanitized content
- [ ] Models define explicit `$fillable` arrays
- [ ] No `unserialize()` on user-controlled data
- [ ] CI/CD pipeline includes security scan step
