# Getting Started

Install the package and publish the default config:

```bash
composer require imbrett/laravel-sluggable
php artisan vendor:publish --tag=sluggable-config
```

Add the `Sluggable` trait to any Eloquent model. Define the column the slug should track via the `$slugSourceColumn` property or override `slugSourceValue()`.

```php
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelSluggable\Traits\Sluggable;

class Article extends Model
{
    use Sluggable;

    protected $fillable = ['title', 'slug'];

    protected $slugSourceColumn = 'title';
}
```

- On `creating`, the slug is generated from the `title` column.
- On `updating`, it refreshes automatically when the title changes and you haven't touched the slug column yourself.
- Slugs are stored in the `slug` column by default; override `slugColumn()` or set `protected $slugColumn` to customise this.

### Regeneration rules

The trait only does work when it needs to:

- Create: generate a slug unless one is already present (or `shouldForceRegenerationOnCreate()` returns `true`).
- Update: regenerate when `shouldRegenerateSlugOnUpdate()` is truthy, the slug column is untouched, and the source column is dirty.
- Manual overrides (setting `$model->slug` explicitly) take precedence.

You can swap in completely custom behaviour at runtime via `SlugManager::instance()->setGenerator()` / `setUniqueResolver()`. Call `SlugManager::reset()` when you need to revert to the defaults (for example in your test teardown).

### Manual overrides

If you set the slug attribute yourself (`$model->slug = 'custom'`), the trait respects that value and skips regeneration on update unless you override `shouldRegenerateSlugOnUpdate()`.
