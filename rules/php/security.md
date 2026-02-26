---
title: PHP Security Guidelines
scope: php
---

# PHP Security Guidelines

## SQL Injection Prevention

- **Always** use PDO prepared statements or the query builder's parameter binding
- Never concatenate user input into SQL queries
- Use named parameters for clarity:

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
```

## Cross-Site Scripting (XSS)

- Escape all output with `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`
- Use template engine auto-escaping (Twig, Blade)
- Set `Content-Type` headers correctly
- Use Content Security Policy headers

## Cross-Site Request Forgery (CSRF)

- Include CSRF tokens in all state-changing forms
- Validate tokens on the server side for every POST/PUT/DELETE request
- Use `SameSite=Lax` or `SameSite=Strict` cookie attribute

## Authentication & Passwords

- Use `password_hash()` with `PASSWORD_DEFAULT` (currently bcrypt, auto-upgrades)
- Use `password_verify()` for checking — never compare hashes directly
- Use `password_needs_rehash()` to upgrade hashes on login
- Implement rate limiting on login endpoints
- Use constant-time comparison (`hash_equals()`) for tokens

## Session Security

- Regenerate session ID after login: `session_regenerate_id(true)`
- Set secure cookie flags: `Secure`, `HttpOnly`, `SameSite`
- Set appropriate session lifetime
- Store sessions server-side (files, Redis, database)

## File Uploads

- Validate MIME type server-side (do not trust client headers)
- Restrict allowed extensions with an allowlist
- Store uploads outside the web root
- Generate random filenames — never use the original filename
- Set file size limits
- Scan for malware if possible

## Deserialization

- **Never** use `unserialize()` on untrusted data
- Use `json_decode()` for data interchange
- If `unserialize()` is required, use the `allowed_classes` option:

```php
unserialize($data, ['allowed_classes' => [AllowedClass::class]]);
```

## Dangerous Functions

Avoid or strictly control:
- `eval()`, `assert()` with string arguments
- `exec()`, `shell_exec()`, `system()`, `passthru()`, `proc_open()`
- `preg_replace()` with the `e` modifier (removed in PHP 8.0)
- `extract()` on user input
- `$$variable` (variable variables) with user input

If shell commands are necessary, use `escapeshellarg()` and `escapeshellcmd()`.

## HTTP Security Headers

Set these headers in your application or web server:

- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Content-Security-Policy: default-src 'self'`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`

## Dependency Security

- Run `composer audit` regularly to check for known vulnerabilities
- Keep dependencies updated
- Pin exact versions in `composer.lock`
- Review new dependencies before adding them
