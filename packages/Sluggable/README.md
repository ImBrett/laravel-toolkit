Laravel Sluggable
=================

Automatic, test-friendly slug generation for Eloquent models. Drop in the `Sluggable` trait, define a source value, and the package keeps the slug column in sync with your preferred identifier.

Installation
------------

```bash
composer require imbrett/laravel-sluggable

php artisan vendor:publish --tag=sluggable-config
```

Usage
-----

```php
use ImBrett\LaravelSluggable\Traits\Sluggable;

class Article extends Model
{
    use Sluggable;

    protected $slugSourceColumn = 'title';
}
```

- Slug created on `creating`, updated whenever the `title` column changes.
- Ensure uniqueness with `-1`, `-2`, … suffixes.
- Manual slug assignments (`$article->slug = 'custom'`) are honoured unless you explicitly force regeneration.

Configuration
-------------

- `column` – database column used to store slugs (default `slug`).
- `source_column` – default source column when the trait is used without overrides (default `title`).
- `separator` – separator for default slug generator.
- `unique` – toggle uniqueness enforcement.
- `regenerate_on_update` – regenerate slug when the source column changes.

Extension points
----------------

- `SlugManager::instance()->setGenerator()` – swap the slug generation strategy (snake case, transliteration, etc).
- `SlugManager::instance()->setUniqueResolver()` – inject tenant-aware or external uniqueness checks.
- `SlugManager::reset()` – clear all runtime overrides (handy for tests or background jobs).
- Override trait methods:
  * `slugColumn()` – change where the slug is stored per model.
  * `slugSourceValue()` – compute the string to slug from (default respects `$slugSourceColumn` or config `source_column`).
  * `shouldRegenerateSlugOnUpdate()` – opt out of automatic regeneration.

Runtime example:

```php
use ImBrett\LaravelSluggable\Support\SlugManager;
use ImBrett\LaravelSluggable\Contracts\SlugGenerator;

SlugManager::instance()->setGenerator(new class implements SlugGenerator {
    public function generate(Model $model, string $value): string
    {
        return Str::slug($value, '_');
    }
});

// ... your logic ...

SlugManager::reset();
```

Testing
-------

Run the package suite:

```bash
make test TEST_ARGS="--testsuite=Sluggable"
```

See `packages/Sluggable/docs/` for in-depth guidance.
