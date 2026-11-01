# Recipes

Practical techniques for real-world projects using Cached Paginator.

## Multi-tenant prefixes

```php
class Post extends Model
{
    use UsesCachedPagination;

    public function cachedPaginationPrefix(): ?string
    {
        return sprintf('tenant:%s:posts', tenancy()->tenant->getKey());
    }
}
```

Each tenant gets isolated totals with predictable invalidation.

## Bust cache on update

```php
Post::saved(function (Post $post): void {
    $builder = $post->newModelQuery();
    $key = (fn () => $this->makeCacheKey())->call($builder); // access protected method

    Cache::forget($key);
});
```

Wrap the closure in a macro or observer in your project to avoid duplication.

## Disable caching for specific workloads

```php
$reports = Post::query()
    ->tap(fn ($query) => $query->getModel()->cachedPaginationTtl = 0)
    ->paginate(100);
```

Alternatively, override `cachedPaginationTtl()` to inspect the current request route or user role.

## Testing

- Flip the cache store to `array` in your test environment (`config(['cache.default' => 'array']);`).
- Assert that `Cache::put` is called using Laravel’s cache fake when verifying behaviour.
- Run `make test TEST_ARGS="--testsuite=CachedPaginator"` inside this repo to execute the package’s own test coverage.

## Custom cache keys

Bind your own resolver when the default hash doesn’t capture tenant context or additional parameters:

```php
use ImBrett\LaravelCachedPaginator\Support\CacheKeyResolver;

app()->bind(CacheKeyResolver::class, TenantAwareResolver::class);
```

Resolvers receive the underlying query builder (Eloquent or base) and must return the unique hash segment. The package still prefixes the model table and configured prefix for consistency.
