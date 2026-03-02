---
name: php-deployment
description: Use this skill when deploying PHP applications, writing Dockerfiles, configuring php-fpm/Swoole/FrankenPHP, setting up CI/CD pipelines, or using Deployer/Envoyer. Covers containerization, runtime tuning, zero-downtime deployments, and health checks.
origin: claude-code-php-toolkit
---

# PHP Deployment Patterns

Deployment patterns for PHP applications covering Docker, runtimes (php-fpm, Swoole, FrankenPHP), CI/CD, and zero-downtime strategies.

## When to Activate

- Writing or optimizing Dockerfiles for PHP
- Configuring php-fpm, Swoole, or FrankenPHP
- Setting up CI/CD pipelines (GitHub Actions, GitLab CI)
- Using Deployer or Envoyer for deployments
- Implementing health checks and readiness probes
- Tuning OPcache for production

## Docker

### Multi-Stage Dockerfile

```dockerfile
# Stage 1: Dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# Stage 2: Production
FROM php:8.3-fpm-alpine AS production

# Install extensions
RUN docker-php-ext-install pdo_mysql opcache bcmath intl

# Copy application
COPY --from=vendor /app /var/www/html

# OPcache config
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Non-root user
RUN adduser -D -u 1000 appuser
USER appuser

WORKDIR /var/www/html
EXPOSE 9000
CMD ["php-fpm"]
```

### OPcache Production Config

```ini
; docker/php/opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0        ; disable in production — restart to pick up changes
opcache.save_comments=1              ; required for Doctrine annotations
opcache.preload=/var/www/html/config/preload.php
opcache.preload_user=appuser
opcache.jit=1255
opcache.jit_buffer_size=128M
```

### Docker Compose (Development)

```yaml
services:
  app:
    build:
      context: .
      target: production
    volumes:
      - ./src:/var/www/html/src   # hot-reload in dev
    depends_on:
      db:
        condition: service_healthy

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: app
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 3s
      retries: 5
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### Nginx Config for php-fpm

```nginx
server {
    listen 80;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass app:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## PHP Runtimes

### php-fpm

Traditional process manager. Each request gets a worker process.

```ini
; php-fpm.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500          ; restart workers after N requests (prevent memory leaks)
```

| Setting | Formula |
|---------|---------|
| `max_children` | Available RAM / avg PHP process memory (typically 30–60 MB) |
| `start_servers` | `min_spare_servers + (max_spare_servers - min_spare_servers) / 2` |

### Swoole / OpenSwoole

Long-running async PHP server. No Nginx needed for simple setups.

```php
// swoole-server.php
$server = new Swoole\HTTP\Server('0.0.0.0', 8080);
$server->set([
    'worker_num' => swoole_cpu_num() * 2,
    'max_request' => 10000,
    'enable_coroutine' => true,
]);

// For Symfony: use runtime component
// composer require runtime/swoole
// APP_RUNTIME=Runtime\\Swoole\\Runtime php public/index.php
```

### FrankenPHP

Modern PHP app server built on Caddy. Automatic HTTPS, HTTP/2, HTTP/3.

```Caddyfile
{
    frankenphp
    order php_server before file_server
}

localhost {
    root * /var/www/html/public
    php_server
}
```

```dockerfile
FROM dunglas/frankenphp:latest-php8.3-alpine
COPY --from=vendor /app /app
WORKDIR /app
```

## CI/CD

### GitHub Actions

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: test
        ports: ["3306:3306"]
        options: --health-cmd="mysqladmin ping" --health-interval=5s --health-timeout=3s --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          extensions: pdo_mysql, intl, opcache
          coverage: pcov

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Run tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml
        env:
          DATABASE_URL: mysql://root:secret@127.0.0.1:3306/test

      - name: Static analysis
        run: vendor/bin/phpstan analyse

      - name: Code style
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

### GitLab CI

```yaml
stages:
  - test
  - deploy

test:
  image: php:8.3-cli
  services:
    - mysql:8.0
  variables:
    MYSQL_ROOT_PASSWORD: secret
    MYSQL_DATABASE: test
  before_script:
    - docker-php-ext-install pdo_mysql
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --no-progress
  script:
    - vendor/bin/phpunit
    - vendor/bin/phpstan analyse

deploy:
  stage: deploy
  script:
    - vendor/bin/dep deploy production
  only:
    - main
  when: manual
```

## Deployer

```bash
composer require --dev deployer/deployer
vendor/bin/dep init
```

### deploy.php

```php
<?php

namespace Deployer;

require 'recipe/symfony.php'; // or 'recipe/laravel.php'

host('production')
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/myapp')
    ->set('branch', 'main');

set('keep_releases', 5);

// Shared files/dirs (persist across releases)
add('shared_files', ['.env.local']);
add('shared_dirs', ['var/log', 'var/sessions']);

// Writable dirs
add('writable_dirs', ['var/cache', 'var/log']);

after('deploy:failed', 'deploy:unlock');
```

```bash
vendor/bin/dep deploy production
vendor/bin/dep rollback production   # instant rollback to previous release
```

### Zero-Downtime Deploy Flow

```
releases/
├── 1/          ← previous release
├── 2/          ← current release
└── 3/          ← new release being deployed
shared/
├── .env.local
├── var/log/
└── var/sessions/
current → releases/3   ← symlink swapped atomically
```

1. Upload code to new release directory
2. Install dependencies (`composer install --no-dev`)
3. Run migrations
4. Warm caches (`bin/console cache:warmup`)
5. Swap `current` symlink (atomic operation)
6. Reload php-fpm (`kill -USR2 $(cat /run/php-fpm.pid)`)

## Health Checks

```php
// public/health.php — lightweight health endpoint
<?php

declare(strict_types=1);

header('Content-Type: application/json');

$checks = [];

// Database
try {
    $pdo = new PDO($_ENV['DATABASE_URL']);
    $pdo->query('SELECT 1');
    $checks['database'] = 'ok';
} catch (\Throwable) {
    $checks['database'] = 'fail';
    http_response_code(503);
}

// OPcache
$checks['opcache'] = opcache_get_status(false) ? 'ok' : 'disabled';

// Disk
$free = disk_free_space('/');
$checks['disk'] = $free > 100 * 1024 * 1024 ? 'ok' : 'low'; // 100 MB threshold

echo json_encode([
    'status' => http_response_code() === 200 ? 'healthy' : 'unhealthy',
    'checks' => $checks,
    'timestamp' => date('c'),
]);
```

## Checklist

- [ ] Multi-stage Dockerfile (vendor stage + production stage)
- [ ] OPcache enabled with `validate_timestamps=0` in production
- [ ] php-fpm `max_children` tuned for available memory
- [ ] `pm.max_requests` set to prevent memory leaks
- [ ] Health check endpoint accessible to load balancer
- [ ] CI pipeline runs tests, static analysis, and style checks
- [ ] Zero-downtime deploy with symlink swap
- [ ] Shared files/dirs configured (`.env.local`, logs)
- [ ] Rollback procedure tested
- [ ] No `composer install` with dev dependencies in production
