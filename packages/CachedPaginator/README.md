Cached Paginator
================

Drop-in cached pagination for Laravel Eloquent builders. Replace the default builder on a model to cache expensive `COUNT(*)` queries while leaving page results fresh.

Features
--------

- Reuses the most recent total count for identical queries.
- Generates deterministic cache keys based on SQL + bindings.
- Configurable TTL, cache prefix, and minimum result threshold.
- Opt-in per model via the `UsesCachedPagination` trait.

Installation
------------

```bash
composer require imbrett/laravel-cached-paginator
```

The service provider is auto-discovered. To publish defaults:

```bash
php artisan vendor:publish --tag=cached-paginator-config
```

Configuration
-------------

`config/cached-paginator.php` exposes:

| Option | Description | Default |
| ------ | ----------- | ------- |
| `ttl` | Cache lifetime in seconds. `0` disables caching. | `60` |
| `prefix` | String prefixed to every cache key. | `pagination` |
| `threshold` | Skip caching when total results are below this number. | `0` (always cache) |

Usage
-----

1. Add the trait to your model.
2. Optionally override TTL/prefix/threshold via properties or methods.
3. Paginate as usual – totals will be cached automatically.

```php
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelCachedPaginator\Traits\UsesCachedPagination;

class Post extends Model
{
    use UsesCachedPagination;

    // Optional overrides:
    protected int $cachedPaginationTtl = 120;          // seconds
    protected string $cachedPaginationPrefix = 'posts';
    protected int $cachedPaginationThreshold = 100;    // minimum results before caching
}

// Controllers or services
$posts = Post::query()
    ->with('author')
    ->where('published', true)
    ->paginate(25);
```

How it works
------------

- The custom builder hashes the SQL statement and bindings to create a cache key (`prefix:table:hash`).
- On the first request it performs the usual `COUNT(*)`, stores the total, and returns paginated results.
- Subsequent identical queries reuse the cached total but still fetch the current page rows from the database.
- When TTL expires or query shape changes, the count is recomputed automatically.

Advanced customisation
----------------------

Implement the following optional methods on your model to control behaviour at runtime:

```php
public function cachedPaginationTtl(): ?int         // return 0 to disable for this model
public function cachedPaginationPrefix(): ?string   // dynamic prefix per tenant, user, etc.
public function cachedPaginationThreshold(): ?int   // vary threshold per workload
```

Testing
-------

Inside this repository the package ships with PHPUnit coverage:

```bash
make test TEST_ARGS="--testsuite=CachedPaginator"
```

In your own project you can swap the cache store for `array` while running tests to avoid polluting external stores.

Documentation
-------------

- [`docs/index.md`](docs/index.md) – documentation table of contents.
- [`docs/getting-started.md`](docs/getting-started.md) – installation, configuration, and basic usage.
- [`docs/internals.md`](docs/internals.md) – cache key algorithm, TTL logic, and invalidation practices.
- [`docs/recipes.md`](docs/recipes.md) – multi-tenant prefixes, cache busting, and testing tips.
- Swap the cache key strategy by binding your own `CacheKeyResolver` in the container.

License
-------

MIT © Brett
