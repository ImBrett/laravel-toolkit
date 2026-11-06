# Getting Started

Cached Paginator swaps the default Eloquent builder with one that reuses `COUNT(*)` totals per query signature. Setup is intentionally lightweight.

## Install

```bash
composer require imbrett/laravel-cached-paginator
```

The service provider auto-discovers. Publish the configuration if you need to customise defaults:

```bash
php artisan vendor:publish --tag=cached-paginator-config
```

## Configure

`config/cached-paginator.php` ships with:

| Option | Description | Default |
| ------ | ----------- | ------- |
| `ttl` | Cache lifetime (seconds). `0` disables caching. | `60` |
| `prefix` | Prefix prepended to every cache key. | `pagination` |
| `threshold` | Minimum total before caching kicks in. | `0` (always cache) |

## Apply to a model

```php
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelCachedPaginator\Traits\UsesCachedPagination;

class Post extends Model
{
    use UsesCachedPagination;

    protected int $cachedPaginationTtl = 120;
    protected string $cachedPaginationPrefix = 'posts';
    protected int $cachedPaginationThreshold = 100;
}
```

- Override the protected properties for static configuration.
- Implement `cachedPaginationTtl()`, `cachedPaginationPrefix()`, or `cachedPaginationThreshold()` when the values depend on runtime context (tenant, user, etc.).

## Paginate as usual

```php
$posts = Post::query()
    ->with('author')
    ->where('published', true)
    ->paginate(25);
```

No controller changes are required—the builder automatically caches totals when eligible.

Move on to [How It Works](internals.md) for an in-depth look at the caching algorithm.
