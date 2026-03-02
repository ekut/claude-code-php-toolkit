# Validation

## FormRequest Classes

Extract validation logic from controllers into dedicated request classes.

```bash
php artisan make:request StoreOrderRequest
```

```php
namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'status' => ['required', new Enum(OrderStatus::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.street' => ['required', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.min' => 'At least one item is required.',
            'items.*.product_id.exists' => 'Product #:position does not exist.',
        ];
    }
}
```

Use in controller — validation runs automatically before the method body:

```php
public function store(StoreOrderRequest $request): JsonResponse
{
    $validated = $request->validated();
    $order = $this->orderService->create($validated);

    return response()->json($order, 201);
}
```

## Custom Validation Rules

```bash
php artisan make:rule Uppercase
```

```php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Uppercase implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strtoupper($value) !== $value) {
            $fail('The :attribute must be uppercase.');
        }
    }
}
```

Use in rules array:

```php
'code' => ['required', 'string', new Uppercase()],
```

## Conditional Validation

```php
public function rules(): array
{
    return [
        'payment_method' => ['required', Rule::in(['card', 'bank_transfer', 'invoice'])],
        'card_number' => ['required_if:payment_method,card', 'nullable', 'string'],
        'bank_account' => ['required_if:payment_method,bank_transfer', 'nullable', 'string'],
        'company_name' => ['required_if:payment_method,invoice', 'nullable', 'string'],
    ];
}

// Or using the after() hook for complex conditions
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($this->total_cents > 100_000 && ! $this->user()->isVerified()) {
                $validator->errors()->add(
                    'total_cents',
                    'Orders over $1,000 require a verified account.',
                );
            }
        },
    ];
}
```

## Array and Nested Validation

```php
public function rules(): array
{
    return [
        // Array of items
        'tags' => ['nullable', 'array', 'max:10'],
        'tags.*' => ['string', 'max:50', 'distinct'],

        // Nested objects in an array
        'addresses' => ['required', 'array', 'min:1', 'max:5'],
        'addresses.*.type' => ['required', Rule::in(['billing', 'shipping'])],
        'addresses.*.line1' => ['required', 'string', 'max:255'],
        'addresses.*.city' => ['required', 'string', 'max:100'],
        'addresses.*.country' => ['required', 'string', 'size:2'],
    ];
}
```

## Validation on Update

Use `Rule::unique` with `ignore` to allow the current record:

```php
final class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->route('user')),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
```
