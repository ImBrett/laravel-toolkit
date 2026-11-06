# Filters

The `filter()` scope converts simple strings into expressive query constraints. Combine them to compose complex API filtering logic without bespoke controllers.

## Syntax

```
relation.column[operator]value
```

- **relation** (optional) – dot notation for relations (`author`, `category.parent`). Leave blank for root columns.
- **column** – database column on the current scope.
- **operator** – one of the supported tokens below.
- **value** – raw string or comma-separated list, depending on operator.

## Supported Operators

| Operator | Meaning | Example |
| -------- | ------- | ------- |
| `=` | Equality | `status=published` |
| `!=` | Inequality | `status!=draft` |
| `~` | `LIKE` search (wraps value in `%`) | `title~laravel` |
| `()` | `WHERE IN` (comma-separated list) | `status()draft,published` |
| `>` `<` `>=` `<=` | Comparison operators | `views>=1000` |

Unsupported operators raise descriptive exceptions, making it safe to expose directly to API consumers.

## Custom operators

Extend the `FilterOperatorManager` to add domain-specific behaviour. Register extensions in a service provider so they load early:

```php
use Illuminate\Support\ServiceProvider;
use ImBrett\LaravelApiQuery\Support\FilterOperatorManager;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(FilterOperatorManager::class)->extend('between', function ($query, string $field, string $value) {
            [$start, $end] = array_pad(explode(',', $value), 2, null);

            return $query->whereBetween($field, [$start, $end]);
        });
    }
}
```

Filters like `viewsbetween10,25` will now resolve using your custom handler.

## Related Columns

Filters can traverse relationships:

```php
Post::filter([
    'category.slug=laravel',
    'author.company.name=Acme',
]);
```

Behind the scenes the package adds the necessary joins and aliases to keep SQL deterministic.

## Validation Tips

- Whitelist valid columns/relations before passing user input straight to `filter()`.
- Combine with Laravel Form Requests or custom DTOs to guard against invalid operators.
- Use descriptive error responses—`filter()` throws `InvalidArgumentException` variants you can catch and translate.

Ready to order results? Head over to [Sorting & Searching](sorting-and-searching.md).
