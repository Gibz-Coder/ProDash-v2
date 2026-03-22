---
inclusion: always
---

# Laravel Architecture Patterns

Source: adapted from affaan-m/everything-claude-code (MIT)

## Layer Boundaries

Controllers → Services/Actions → Models. Keep controllers thin.

```
app/
├── Actions/        # Single-purpose use cases
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/   # Form request validation
│   └── Resources/  # API resources
├── Jobs/
├── Models/
├── Policies/
├── Services/       # Coordinating domain services
└── Support/
```

## Controller Pattern

```php
declare(strict_types=1);

final class OrdersController extends Controller
{
    public function __construct(private OrderService $service) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->service->placeOrder($request->toDto());

        return response()->json([
            'success' => true,
            'data'    => OrderResource::make($order),
            'error'   => null,
            'meta'    => null,
        ], 201);
    }
}
```

## API Response Envelope

All API responses use a consistent envelope:

```json
{
    "success": true,
    "data": {},
    "error": null,
    "meta": { "page": 1, "per_page": 25, "total": 120 }
}
```

## Action Pattern

```php
final class CreateOrderAction
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(CreateOrderData $data): Order
    {
        return $this->orders->create($data);
    }
}
```

## Form Request + DTO

```php
final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user(); }

    public function rules(): array
    {
        return [
            'items'            => ['required', 'array', 'min:1'],
            'items.*.sku'      => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDto(): CreateOrderData
    {
        return new CreateOrderData(
            userId: (int) $this->user()->id,
            items: $this->validated('items'),
        );
    }
}
```

## Eloquent Model

```php
final class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'owner_id', 'status'];

    protected $casts = [
        'status'      => ProjectStatus::class,
        'archived_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
```

## Key Rules

- Use `$fillable` or `$guarded`; avoid `Model::unguard()`
- Eager load to avoid N+1: `->with(['customer', 'items.product'])`
- Wrap multi-step updates in `DB::transaction()`
- Use route model binding and scoped bindings for nested routes
- Bind interfaces to implementations in `AppServiceProvider`
- Use `Bus::fake()`, `Queue::fake()`, `Mail::fake()` in tests
- Cache read-heavy queries; invalidate on model events
- Keep secrets in `.env`, config in `config/*.php`
- Use `config:cache`, `route:cache`, `view:cache` in production
