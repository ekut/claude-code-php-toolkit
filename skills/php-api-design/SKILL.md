---
name: php-api-design
description: REST API design patterns for PHP — resource naming, HTTP methods, status codes, pagination, filtering, error responses, versioning, rate limiting, and implementation with Laravel and API Platform.
origin: claude-code-php-toolkit
---

# PHP API Design Patterns

Conventions and best practices for designing consistent, developer-friendly REST APIs in PHP.

## When to Activate

- Designing new API endpoints
- Reviewing existing API contracts
- Adding pagination, filtering, or sorting
- Implementing error handling for APIs
- Planning API versioning strategy
- Building public or partner-facing APIs
- Setting up Laravel API Resources or API Platform

## Resource Design

### URL Structure

```
# Resources are nouns, plural, lowercase, kebab-case
GET    /api/v1/users
GET    /api/v1/users/{id}
POST   /api/v1/users
PUT    /api/v1/users/{id}
PATCH  /api/v1/users/{id}
DELETE /api/v1/users/{id}

# Sub-resources for relationships
GET    /api/v1/users/{id}/orders
POST   /api/v1/users/{id}/orders

# Actions that don't map to CRUD (use verbs sparingly)
POST   /api/v1/orders/{id}/cancel
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
```

### Naming Rules

```
# GOOD
/api/v1/team-members          # kebab-case for multi-word resources
/api/v1/orders?status=active  # query params for filtering
/api/v1/users/123/orders      # nested resources for ownership

# BAD
/api/v1/getUsers              # verb in URL
/api/v1/user                  # singular (use plural)
/api/v1/team_members          # snake_case in URLs
/api/v1/users/123/getOrders   # verb in nested resource
```

## HTTP Methods and Status Codes

### Method Semantics

| Method | Idempotent | Safe | Use For |
|--------|-----------|------|---------|
| GET | Yes | Yes | Retrieve resources |
| POST | No | No | Create resources, trigger actions |
| PUT | Yes | No | Full replacement of a resource |
| PATCH | No* | No | Partial update of a resource |
| DELETE | Yes | No | Remove a resource |

*PATCH can be made idempotent with proper implementation.

### Status Code Reference

```
# Success
200 OK                    — GET, PUT, PATCH (with response body)
201 Created               — POST (include Location header)
204 No Content            — DELETE, PUT (no response body)

# Client Errors
400 Bad Request           — Malformed JSON, invalid syntax
401 Unauthorized          — Missing or invalid authentication
403 Forbidden             — Authenticated but not authorized
404 Not Found             — Resource doesn't exist
409 Conflict              — Duplicate entry, state conflict
422 Unprocessable Entity  — Valid JSON, but validation failed
429 Too Many Requests     — Rate limit exceeded

# Server Errors
500 Internal Server Error — Unexpected failure (never expose details)
502 Bad Gateway           — Upstream service failed
503 Service Unavailable   — Temporary overload, include Retry-After
```

### Common Mistakes

```php
// BAD: 200 for everything
return response()->json(['success' => false, 'error' => 'Not found'], 200);

// GOOD: Use HTTP status codes semantically
return response()->json(['error' => ['code' => 'not_found', 'message' => 'User not found']], 404);

// BAD: 500 for validation errors
// GOOD: 422 with field-level details

// BAD: 200 for created resources
// GOOD: 201 with Location header
return response()->json(['data' => $user], 201)
    ->header('Location', "/api/v1/users/{$user->id}");
```

## Response Format

### Success Response

```json
{
  "data": {
    "id": "abc-123",
    "email": "alice@example.com",
    "name": "Alice",
    "created_at": "2025-01-15T10:30:00Z"
  }
}
```

### Collection Response (with Pagination)

```json
{
  "data": [
    { "id": "abc-123", "name": "Alice" },
    { "id": "def-456", "name": "Bob" }
  ],
  "meta": {
    "total": 142,
    "page": 1,
    "per_page": 20,
    "total_pages": 8
  },
  "links": {
    "self": "/api/v1/users?page=1&per_page=20",
    "next": "/api/v1/users?page=2&per_page=20",
    "last": "/api/v1/users?page=8&per_page=20"
  }
}
```

### Error Response

```json
{
  "error": {
    "code": "validation_error",
    "message": "Request validation failed",
    "details": [
      {
        "field": "email",
        "message": "Must be a valid email address",
        "code": "invalid_format"
      },
      {
        "field": "age",
        "message": "Must be between 0 and 150",
        "code": "out_of_range"
      }
    ]
  }
}
```

## Pagination

### Offset-Based (Simple)

```
GET /api/v1/users?page=2&per_page=20
```

```php
// Laravel implementation
$users = User::query()
    ->orderByDesc('created_at')
    ->paginate(perPage: $request->integer('per_page', 20));

return UserResource::collection($users);
```

**Pros:** Easy to implement, supports "jump to page N".
**Cons:** Slow on large offsets (OFFSET 100000), inconsistent with concurrent inserts.

### Cursor-Based (Scalable)

```
GET /api/v1/users?cursor=eyJpZCI6MTIzfQ&limit=20
```

```php
// Laravel implementation
$users = User::query()
    ->orderBy('id')
    ->cursorPaginate(perPage: $request->integer('limit', 20));

return UserResource::collection($users);
```

**Pros:** Consistent performance regardless of position, stable with concurrent inserts.
**Cons:** Cannot jump to arbitrary page, cursor is opaque.

### When to Use Which

| Use Case | Pagination Type |
|----------|----------------|
| Admin dashboards, small datasets (<10K) | Offset |
| Infinite scroll, feeds, large datasets | Cursor |
| Public APIs | Cursor (default) with offset (optional) |
| Search results | Offset (users expect page numbers) |

## Filtering and Sorting

### Filtering

```
# Simple equality
GET /api/v1/orders?status=active&customer_id=123

# Comparison operators (bracket notation)
GET /api/v1/products?price[gte]=10&price[lte]=100
GET /api/v1/orders?created_at[after]=2025-01-01

# Multiple values (comma-separated)
GET /api/v1/products?category=electronics,clothing
```

```php
// Laravel implementation with Spatie Query Builder
use Spatie\QueryBuilder\QueryBuilder;

$products = QueryBuilder::for(Product::class)
    ->allowedFilters(['status', 'category', AllowedFilter::scope('price_between')])
    ->allowedSorts(['price', 'created_at', 'name'])
    ->allowedIncludes(['category', 'reviews'])
    ->paginate();
```

### Sorting

```
# Single field (prefix - for descending)
GET /api/v1/products?sort=-created_at

# Multiple fields (comma-separated)
GET /api/v1/products?sort=-featured,price,-created_at
```

### Sparse Fieldsets

```
GET /api/v1/users?fields=id,name,email
```

## Authentication and Authorization

### Token-Based Auth

```
# Bearer token in Authorization header
GET /api/v1/users
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

# API key (for server-to-server)
GET /api/v1/data
X-API-Key: sk_live_abc123
```

### Laravel Implementation

```php
// Sanctum token auth (API)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('orders', OrderController::class);
});

// Controller-level authorization via policies
public function update(UpdateOrderRequest $request, Order $order): JsonResponse
{
    $this->authorize('update', $order);

    $order->update($request->validated());

    return new OrderResource($order);
}
```

## Rate Limiting

### Response Headers

```
HTTP/1.1 200 OK
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640000000

# When exceeded
HTTP/1.1 429 Too Many Requests
Retry-After: 60
```

### Laravel Implementation

```php
// AppServiceProvider::boot()
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('public', function (Request $request) {
    return Limit::perMinute(30)->by($request->ip());
});
```

### Rate Limit Tiers

| Tier | Limit | Window | Use Case |
|------|-------|--------|----------|
| Anonymous | 30/min | Per IP | Public endpoints |
| Authenticated | 100/min | Per user | Standard API access |
| Premium | 1000/min | Per API key | Paid API plans |
| Internal | 10000/min | Per service | Service-to-service |

## Versioning

### URL Path Versioning (Recommended)

```
/api/v1/users
/api/v2/users
```

### Versioning Strategy

1. Start with `/api/v1/` — don't version until you need to
2. Maintain at most 2 active versions (current + previous)
3. Deprecation timeline: announce 6 months before sunset for public APIs
4. Add `Sunset` header: `Sunset: Sat, 01 Jan 2027 00:00:00 GMT`
5. Non-breaking changes don't need a new version (adding fields, optional params, new endpoints)
6. Breaking changes require a new version (removing/renaming fields, changing types)

## Laravel API Resources

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
            'orders_count' => $this->whenCounted('orders'),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
        ];
    }
}
```

## API Platform Implementation

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(validationContext: ['groups' => ['create']]),
    ],
    paginationType: 'cursor',
    order: ['createdAt' => 'DESC'],
)]
final class Product
{
    // API Platform auto-generates endpoints, serialization, and docs
}
```

## API Design Checklist

Before shipping a new endpoint:

- [ ] Resource URL follows naming conventions (plural, kebab-case, no verbs)
- [ ] Correct HTTP method (GET for reads, POST for creates, etc.)
- [ ] Appropriate status codes (not 200 for everything)
- [ ] Input validated with FormRequest or API Platform validators
- [ ] Error responses follow standard format with codes and messages
- [ ] Pagination implemented for list endpoints (cursor or offset)
- [ ] Authentication required (or explicitly marked as public)
- [ ] Authorization checked (user can only access their own resources)
- [ ] Rate limiting configured
- [ ] Response does not leak internal details (stack traces, SQL errors)
- [ ] Consistent naming with existing endpoints
- [ ] OpenAPI spec updated (auto-generated via Scramble, L5-Swagger, or API Platform)
