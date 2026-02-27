# Configuration

## Environment Variables

`.env` holds defaults (committed). `.env.local` overrides locally (not committed). Never put secrets in `.env`:

```bash
# .env
APP_ENV=dev
APP_SECRET=change-me-in-production
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

```bash
# .env.local  (git-ignored)
DATABASE_URL="postgresql://myuser:mypassword@127.0.0.1:5432/mydb?serverVersion=16"
STRIPE_SECRET_KEY=sk_test_...
```

## Parameters

Always prefix custom parameters with `app.` to avoid collisions with Symfony and third-party parameters:

```yaml
# config/services.yaml
parameters:
    app.payment.currency: 'USD'
    app.upload.max_size: 10485760  # 10 MB

services:
    _defaults:
        bind:
            $uploadMaxSize: '%app.upload.max_size%'
```

For options that rarely change and are referenced only in PHP, prefer class constants over parameters:

```php
final class InvoiceService
{
    private const VAT_RATE = 0.21;
    private const DUE_DAYS = 30;
}
```

## Symfony Secrets (Production)

Use `secrets:set` (Symfony vault) to store production secrets — the vault file is safe to commit (encrypted with a deploy key):

```bash
bin/console secrets:set STRIPE_SECRET_KEY
bin/console secrets:set DATABASE_PASSWORD
```

Reference secrets in code exactly like env vars:

```php
#[Autowire(env: 'STRIPE_SECRET_KEY')]
private readonly string $stripeKey,
```

Rule: `.env.local` for local dev secrets; Symfony vault (`secrets:set`) for production. Never put real secrets in `.env`.

## Package Config

Per-package configuration lives in `config/packages/`. Example for the cache pool:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            app.product_cache:
                adapter: cache.app
                default_lifetime: 3600
```

Per-environment overrides live in `config/packages/{env}/`:

```yaml
# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        url: '%env(DATABASE_URL)%'
```
