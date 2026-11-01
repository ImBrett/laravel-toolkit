# Getting Started

API Query Scopes turn HTTP-friendly parameters into expressive Eloquent queries.

## Install

```bash
composer require imbrett/laravel-api-query
```

## Prepare a model

```php
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelApiQuery\Traits\HasApiQueryScopes;

class Post extends Model
{
    use HasApiQueryScopes;

    protected $fillable = ['title', 'body', 'status'];
    protected array $searchable = ['title', 'body']; // optional override
}
```

The trait registers three scopes: `filter`, `sort`, and `search`.

## Use in a controller

```php
use ImBrett\LaravelApiQuery\Data\SortData;

$posts = Post::query()
    ->search($request->input('q'))
    ->filter($request->input('filters', []))
    ->sort(new SortData(
        $request->input('sort.column', 'title'),
        $request->input('sort.direction', 'asc')
    ))
    ->paginate();
```

Request conventions:

- `q` – free text search value.
- `filters` – array of strings (`field[operator]value`).
- `sort.column`, `sort.direction` – map neatly onto `SortData`.

Continue with the [Filters](filters.md) guide for the full syntax reference.
