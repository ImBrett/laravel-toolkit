# Testing

- Use Laravel's `RefreshDatabase` or `DatabaseTransactions` as usual; slugs are generated synchronously inside model events.
- Need deterministic slugs? Override `slugSourceValue()` in your test model or swap in a fake generator via `SlugManager::instance()->setGenerator()`.
- To verify uniqueness handling, fake the resolver:

```php
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;
use ImBrett\LaravelSluggable\Support\SlugManager;

SlugManager::instance()->setUniqueResolver(new class implements SlugUniqueResolver {
    public function resolve(Model $model, string $column, string $slug, string $separator): string
    {
        return $slug; // disable uniqueness for this test
    }
});
// ... run assertions ...
SlugManager::reset();
```

- Clean up custom registrations with `SlugManager::reset()` inside your test `tearDown()` to keep suites isolated.
- Run the package suite with `make test TEST_ARGS="--testsuite=Sluggable"` to validate default behaviour.
