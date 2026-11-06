API Query Scopes
================

Composable query scopes for Laravel models that make it simple to power API-style filtering, sorting, and searching behaviour from request data.

Features
--------

- `filter()` – parse simple `field[operator]value` strings (including relation columns) into query constraints.
- `sort()` – order by model columns or by belongs-to / has-one relations using safe joins.
- `search()` – search fillable columns and related models with minimal configuration.
- `HasApiQueryScopes` trait that bundles all three scopes for convenient inclusion on your models.

Installation
------------

```bash
composer require imbrett/laravel-api-query
```

Usage
-----

```php
use ImBrett\LaravelApiQuery\Traits\HasApiQueryScopes;
use ImBrett\LaravelApiQuery\Data\SortData;

class Post extends Model
{
    use HasApiQueryScopes;

    protected $fillable = ['title', 'body'];
}

$posts = Post::query()
    ->search($request->input('search'), ['title', 'body'], ['author' => ['name']])
    ->filter($request->input('filters'))
    ->sort(new SortData($request->input('sort_column', 'title'), $request->input('sort_direction', 'asc')))
    ->paginate();
```

Filter syntax
-------------

Each filter string is composed as `relation.field[operator]value`. Supported operators:

| Operator | Description |
| -------- | ----------- |
| `=`      | equality |
| `!=`     | not equal |
| `~`      | LIKE search |
| `()`     | where-in list (comma separated) |
| `>` `<` `>=` `<=` | comparison operators |

Examples:

```php
Post::filter([
    'title~laravel',                 // title LIKE "%laravel%"
    'category.name=News',            // related category name equals News
    'author.name()!=Guest',          // author name not equal to Guest
    'views()100,200,300',            // views IN (100,200,300)
]);
```

Sorting
-------

`SortData` accepts a column (dot notation supported) and a direction.

```php
Post::sort(new SortData('author.name', 'desc'))->get();
```

Currently belongs-to and has-one relations are supported. Attempting to sort on other relation types raises an exception.

Searching
---------

`search()` defaults to the model's `$searchable` property (or fillable attributes) and allows specifying relation columns:

```php
Post::search('laravel', ['title'], ['category' => ['name']])->get();
```

Testing
-------

The package ships with PHPUnit coverage validated via `make test TEST_ARGS="--testsuite=ApiQuery"` in this monorepo.

Documentation
-------------

- [`docs/index.md`](docs/index.md) – documentation table of contents.
- [`docs/getting-started.md`](docs/getting-started.md) – model setup and request integration.
- [`docs/filters.md`](docs/filters.md) – filter syntax and relation support.
- [`docs/sorting-and-searching.md`](docs/sorting-and-searching.md) – ordering and free-text search helpers.
- [`docs/extending-and-testing.md`](docs/extending-and-testing.md) – custom operators, validation, and QA guidance.
- `FilterOperatorManager` and `RelationSortHandlerManager` are container singletons; extend them at runtime to support bespoke operators or relation types.

License
-------

MIT © Brett
