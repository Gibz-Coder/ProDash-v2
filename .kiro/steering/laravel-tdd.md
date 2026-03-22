---
inclusion: always
---

# Laravel TDD Workflow

Source: adapted from affaan-m/everything-claude-code (MIT)

## Red-Green-Refactor

1. Write a failing test
2. Implement the minimal change to pass
3. Refactor while keeping tests green

## Test Layers

- Unit — pure PHP classes, value objects, services
- Feature — HTTP endpoints, auth, validation, policies
- Integration — database + queue + external boundaries

## Database Strategy

- `RefreshDatabase` — default for tests that touch the DB
- `DatabaseTransactions` — when schema is already migrated, only need per-test rollback
- Use `:memory:` SQLite in `phpunit.xml` for speed

## Framework

- Default to **Pest** for new tests
- Use **PHPUnit** only if the project already standardizes on it

## Examples

### Pest (preferred)

```php
uses(RefreshDatabase::class);

test('owner can create project', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/projects', ['name' => 'New Project']);

    $response->assertCreated();
    assertDatabaseHas('projects', ['name' => 'New Project']);
});
```

### PHPUnit

```php
final class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/projects', ['name' => 'New Project']);

        $response->assertCreated();
        $this->assertDatabaseHas('projects', ['name' => 'New Project']);
    }
}
```

### Fakes for Side Effects

```php
Queue::fake();
dispatch(new SendOrderConfirmation($order->id));
Queue::assertPushed(SendOrderConfirmation::class);
```

### Sanctum Auth

```php
Sanctum::actingAs($user);
$this->getJson('/api/projects')->assertOk();
```

### Inertia

```php
$response->assertInertia(fn (AssertableInertia $page) => $page
    ->component('Dashboard')
    ->where('user.id', $user->id)
);
```

## Coverage Target

- Enforce **80%+ coverage** for unit + feature tests
- Use `XDEBUG_MODE=coverage php artisan test --coverage` in CI

## Commands

```bash
php artisan test
vendor/bin/pest
vendor/bin/phpunit
```
