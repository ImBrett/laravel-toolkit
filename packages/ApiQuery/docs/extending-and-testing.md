# Extending & Testing

Tailor API Query Scopes to your application while keeping regressions out.

## Custom Operators

Extend the operator resolver to support domain-specific behaviour:

```php
use ImBrett\LaravelApiQuery\Support\FilterOperatorManager;

app(FilterOperatorManager::class)->extend('between', function ($builder, $column, $value) {
    [$start, $end] = array_pad(explode(',', $value), 2, null);
    $builder->whereBetween($column, [$start, $end]);
});
```

Place this in a service provider so it registers early in the request lifecycle.

## Whitelisting filters

```php
$allowed = ['status', 'category.slug', 'author.name'];

$filters = array_values(array_intersect($allowed, $request->input('filters', [])));

$query->filter($filters);
```

Combining whitelists with Laravel Form Requests keeps the package safe to expose to third-parties.

## Testing Strategy

- Leverage the package’s own tests by running `make test TEST_ARGS="--testsuite=ApiQuery"`.
- In consumer projects, seed fake data and assert the generated SQL using `$query->toSql()` when necessary.
- Use Laravel’s `dumpRawSql()` (on recent versions) to inspect the final query when debugging complex relation sorting.

## Performance Tips

- Index columns you frequently filter or sort on—especially relation joins.
- Cache request DTOs if you parse large filter sets often.
- Combine with pagination caching (see the CachedPaginator package) for even faster API responses.

Return to the [documentation index](index.md) whenever you need a refresher.
