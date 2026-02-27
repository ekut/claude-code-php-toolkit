# Migrations

## Setup

```bash
composer require doctrine/doctrine-migrations-bundle
```

Configuration (`config/packages/doctrine_migrations.yaml` for Symfony):

```yaml
doctrine_migrations:
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/migrations'
    enable_profiler: false
```

## Workflow

```bash
# 1. Generate migration from entity changes
vendor/bin/doctrine-migrations diff

# 2. Review the generated migration file
# 3. Run pending migrations
vendor/bin/doctrine-migrations migrate

# 4. Check status
vendor/bin/doctrine-migrations status
```

## Writing Migrations

### Auto-Generated

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE products (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        price NUMERIC(10, 2) NOT NULL,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
        UNIQUE INDEX uniq_products_slug (slug),
        INDEX idx_products_status (status),
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE products');
}
```

### Data Migrations

When you need to migrate data alongside schema changes:

```php
public function up(Schema $schema): void
{
    // 1. Add new column (nullable first)
    $this->addSql('ALTER TABLE users ADD full_name VARCHAR(255) DEFAULT NULL');

    // 2. Migrate data
    $this->addSql("UPDATE users SET full_name = CONCAT(first_name, ' ', last_name)");

    // 3. Make column NOT NULL
    $this->addSql('ALTER TABLE users MODIFY full_name VARCHAR(255) NOT NULL');
}
```

## Zero-Downtime Migration Strategy

For production deployments, split breaking changes into multiple migrations:

### Removing a Column

```
Migration 1: Deploy code that no longer reads the column
Migration 2: ALTER TABLE ... DROP COLUMN (safe — no code references it)
```

### Renaming a Column

```
Migration 1: Add new column, copy data, update code to write both
Migration 2: Deploy code that reads from new column only
Migration 3: Drop old column
```

### Adding a NOT NULL Column

```
Migration 1: Add column as nullable with default
Migration 2: Backfill data (UPDATE ... SET col = default WHERE col IS NULL)
Migration 3: ALTER column to NOT NULL
```

## Best Practices

- **Never edit a migration that has already been executed** — create a new migration instead
- **Always review generated SQL** before running — auto-generated migrations can be wrong
- **Keep migrations idempotent** where possible
- **One logical change per migration** — easier to debug and roll back
- **Test migrations against a copy of production data** before deploying
- **Use transactions** — Doctrine wraps each migration in a transaction by default (MySQL DDL is not transactional; PostgreSQL DDL is)
- **Version control migrations** — commit them alongside the entity changes

## Rolling Back

```bash
# Revert the last migration
vendor/bin/doctrine-migrations migrate prev

# Revert to a specific version
vendor/bin/doctrine-migrations migrate 'DoctrineMigrations\Version20240101000000'
```

**Warning:** Down migrations are not always reliable. Prefer forward-fix migrations in production.

## Symfony Console Commands

```bash
vendor/bin/doctrine-migrations diff          # Generate from entity diff
vendor/bin/doctrine-migrations migrate       # Run pending
vendor/bin/doctrine-migrations status        # Show migration status
vendor/bin/doctrine-migrations list          # List all migrations
vendor/bin/doctrine-migrations current       # Show current version
vendor/bin/doctrine-migrations latest        # Show latest available
```
