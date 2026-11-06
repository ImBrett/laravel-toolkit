# Sorting & Searching

Complement `filter()` with opinionated helpers that cover the majority of API use-cases.

## Sorting

Use the `sort()` scope with a `SortData` DTO:

```php
use ImBrett\LaravelApiQuery\Data\SortData;

$posts = Post::query()
    ->sort(new SortData('author.name', 'desc'))
    ->get();
```

Supported relationships:

- BelongsTo
- HasOne

Anything else (e.g. HasMany) throws an exception to avoid ambiguous joins.

### Guardrails

- Direction defaults to `asc` but only accepts `asc` or `desc`.
- Columns are validated against the model’s schema metadata to prevent SQL injection.

### Custom relation handlers

The package ships with handlers for `BelongsTo` and `HasOne` relations. Register additional handlers with the `RelationSortHandlerManager` when you need to support other relation types:

```php
use ImBrett\LaravelApiQuery\Support\RelationSortHandlerManager;
use ImBrett\LaravelApiQuery\Support\Contracts\RelationSortHandler;
use Illuminate\Database\Eloquent\Relations\HasMany;

app(RelationSortHandlerManager::class)->extend(new class implements RelationSortHandler {
    public function supports(Relation $relation): bool
    {
        return $relation instanceof HasMany;
    }

    public function apply(Builder $query, Relation $relation, string $column, string $direction): Builder
    {
        // join + order logic for the custom relation
    }
});
```

Handlers are resolved from the container, so you can package them per project or bound them in service providers.

## Searching

`search()` performs a `LIKE` query across your chosen columns.

```php
$posts = Post::search(
    'laravel',
    ['title', 'excerpt'],
    ['author' => ['name'], 'category' => ['name']]
)->get();
```

- The second argument overrides the searchable columns on the model.
- The third argument accepts a relation → columns map.

### Fine-tuning

- Set `$searchable` on the model to change the default column set.
- Provide custom search scopes if you need full-text, trigram, or elastic integrations; chain them before/after `search()`.

Up next: [Extending & Testing](extending-and-testing.md) covers custom operators and confidence checks.
