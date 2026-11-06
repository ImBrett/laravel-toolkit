# Extending

### Custom slug generator

Swap the generator that transforms your source string into a slug:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ImBrett\LaravelSluggable\Contracts\SlugGenerator;
use ImBrett\LaravelSluggable\Support\SlugManager;

SlugManager::instance()->setGenerator(new class implements SlugGenerator {
    public function generate(Model $model, string $value): string
    {
        return Str::snake($value);
    }
});
```

Call `SlugManager::instance()->setGenerator(null)` to restore the default behaviour.

### Custom uniqueness logic

Plug in your own unique resolver to incorporate tenant scopes, soft deletes, or external stores:

```php
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;
use ImBrett\LaravelSluggable\Support\SlugManager;

SlugManager::instance()->setUniqueResolver(new MyTenantAwareResolver());
```

Resolvers receive the model instance, target column, generated slug, and configured separator. Return the final unique slug string. Reset with `SlugManager::instance()->setUniqueResolver(null)` when you no longer need the override.

Call `SlugManager::reset()` to clear both generator and uniqueness overrides in one go (useful in test tearDown hooks).

### Per-model overrides

- `slugSourceValue()` – compute the string to slug from.
- `slugColumn()` – change where slugs are stored.
- `slugSeparator()` – customise the separator per model.
- `shouldRegenerateSlugOnUpdate()` – disable automatic regeneration.

Each hook returns simple values so you can adapt to unusual business logic without touching the package internals.
