# Eloquent ORM

## Model Basics

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'total_cents',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,  // Backed enum
            'total_cents' => 'integer',
            'shipped_at' => 'immutable_datetime',
        ];
    }
}
```

> Prefer `$fillable` (allowlist) over `$guarded` (blocklist) to prevent mass assignment.

## Relationships

```php
// One-to-Many
final class User extends Model
{
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

// Belongs-To
final class Order extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// Many-to-Many
final class User extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withTimestamps()
            ->withPivot('assigned_by');
    }
}

// Has-Many-Through
final class Country extends Model
{
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, User::class);
    }
}

// Polymorphic
final class Comment extends Model
{
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

## Eager Loading

Always eager-load relationships in collection endpoints to prevent N+1 queries.

```php
// Controller — explicit eager loading
$users = User::with(['orders', 'profile'])->paginate(20);

// Nested eager loading
$users = User::with(['orders.items', 'orders.payments'])->get();

// Constrained eager loading
$users = User::with(['orders' => function (Builder $query) {
    $query->where('status', OrderStatus::Completed)
        ->latest()
        ->limit(5);
}])->get();

// Default eager loading on the model
final class Order extends Model
{
    protected $with = ['user'];  // Always loaded
}
```

## Query Scopes

Encapsulate reusable query logic in scopes.

```php
final class Order extends Model
{
    // Local scope — called as Order::active()
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Active);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

// Usage — scopes chain naturally
$orders = Order::active()->forUser($user)->recent(7)->paginate(20);
```

## Accessors and Mutators

Use PHP 8.1+ attribute syntax.

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

final class User extends Model
{
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }
}
```

## Soft Deletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

final class Order extends Model
{
    use SoftDeletes;
}

// Queries automatically exclude soft-deleted records
Order::all();                    // Only non-deleted
Order::withTrashed()->get();     // Include deleted
Order::onlyTrashed()->get();     // Only deleted

$order->delete();                // Soft delete
$order->restore();               // Restore
$order->forceDelete();           // Permanent delete
```

## Bulk Operations

```php
// Mass update (bypasses model events)
Order::where('status', OrderStatus::Pending)
    ->where('created_at', '<', now()->subDays(30))
    ->update(['status' => OrderStatus::Expired]);

// Upsert — insert or update matching records
Order::upsert(
    values: [
        ['external_id' => 'A1', 'status' => 'active', 'total_cents' => 1000],
        ['external_id' => 'A2', 'status' => 'active', 'total_cents' => 2000],
    ],
    uniqueBy: ['external_id'],
    update: ['status', 'total_cents'],
);

// Chunk processing for large datasets
Order::where('status', OrderStatus::Pending)
    ->chunkById(1000, function ($orders) {
        foreach ($orders as $order) {
            $order->process();
        }
    });
```
