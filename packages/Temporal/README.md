Temporal Package
================

Opinionated toolkit for modelling **temporal (history-aware) data** in Laravel applications. It provides:

- `TemporalModel` base class with atomic versioning helpers (`upsertTemporal`, query scopes).
- `HasTemporalAttributes` trait to expose temporal data as native model attributes.
- Schema macros (`temporalColumns`, `temporalIndexes`) to standardize table structure.
- `make:temporal` artisan command for one-step scaffolding.

Contents
--------

- [Installation](#installation)
- [Configuration](#configuration)
- [Database schema](#database-schema)
- [Temporal models](#temporal-models)
- [Temporal attributes](#temporal-attributes)
- [CLI scaffolding](#cli-scaffolding)
- [Testing](#testing)
- [Reference docs](#reference-docs)

Installation
------------

Add the package to your project (adjust for your distribution channel):

```bash
composer require imbrett/laravel-temporal
```

The service provider auto-discovers. For manual registration:

```php
'providers' => [
    ImBrett\LaravelTemporal\Providers\TemporalServiceProvider::class,
];
```

Configuration
-------------

Publish the configuration to customize the infinity marker or user resolver:

```bash
php artisan vendor:publish --tag=temporal-config
```

`config/temporal.php` defaults:

| Key | Purpose | Default |
| --- | ------- | ------- |
| `infinity` | Sentinel `valid_to` value used to mark “current” rows. | `9999-12-31 23:59:59` |
| `user_resolver` | Closure returning the identifier recorded in `source` during `upsertTemporal`. | Logged-in user email or `system` |

Database schema
---------------

### Blueprint macros

Use the provided macros inside your migrations:

```php
Schema::create('account_balances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('account_id')->constrained()->cascadeOnDelete();
    $table->decimal('amount', 18, 4)->nullable();
    $table->string('source')->nullable();

    $table->temporalColumns();          // valid_from, valid_to, tx_at
    $table->temporalIndexes('account_id');
});
```

Indexes ensure efficient *current* and *as-of* queries.

Temporal models
---------------

Implement your history tables by extending `TemporalModel`:

```php
use ImBrett\LaravelTemporal\Models\TemporalModel;

final class AccountBalance extends TemporalModel
{
    protected $table = 'account_balances';
    protected $fillable = ['account_id', 'amount', 'source'];

    public function temporalColumns(): array
    {
        return ['amount'];
    }

    public function temporalForeignKey(): string
    {
        return 'account_id';
    }
}
```

Key features provided by the `Temporal` trait:

- `scopeCurrent()` – only rows whose `valid_to` equals `config('temporal.infinity')`.
- `scopeAsOf($moment)` – row that was valid at a specific timestamp.
- `upsertTemporal($anchorId, array $attributes, ?string $source = null)` – transactional write pipeline that:
  1. Locks existing current row.
  2. Returns early if the requested values match (`temporalColumns()`).
  3. Closes the current row (`valid_to = now()`).
  4. Inserts the new version with the infinity marker.

Temporal attributes
-------------------

Expose temporal data on aggregate models via `HasTemporalAttributes`:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use ImBrett\LaravelTemporal\Traits\HasTemporalAttributes;

class Account extends Model
{
    use HasTemporalAttributes;

    protected function balance(): Attribute
    {
        return $this->temporalAttribute(AccountBalance::class, 'amount');
    }
}
```

- Reads (`$account->balance`) fetch the current temporal row.
- Writes on persisted models call `upsertTemporal`.
- Writes on unsaved models queue until the record receives a key.
- Optional closures let you normalize/transform values (`in`) or presentation (`out`).

Legacy `_pending_temporal_*` attributes are still processed to support older integrations.

CLI scaffolding
---------------

Generate a temporal model + migration from a single command:

```bash
php artisan make:temporal AccountBalance "App\Models\Account" --columns=amount,available_credit
```

Review the stubs in `stubs/temporal-model.stub` and `stubs/temporal-migration.stub` to tailor data types, namespaces, or naming conventions. Publish them via:

```bash
php artisan vendor:publish --tag=temporal-stubs
```

Testing
-------

The package ships with 80%+ unit coverage backed by Dockerized tooling. Typical workflow inside this repository:

```bash
make test
make test TEST_ARGS="--testsuite=Temporal"
make test-coverage
```

Inside consumer projects you can keep using your own PHPUnit setup; the traits and models do not require the monorepo harness.

Reference docs
--------------

- [`docs/index.md`](docs/index.md) – table of contents for every Temporal guide.
- [`docs/concepts.md`](docs/concepts.md) – internals, concurrency, and lifecycle deep dive.
- [`docs/attributes.md`](docs/attributes.md) – attribute lifecycle, transformations, testing patterns.
- [`docs/schema-and-cli.md`](docs/schema-and-cli.md) – schema macros, artisan generators, and stub customisation.
- [`docs/testing.md`](docs/testing.md) – package-specific testing workflow and fixtures.
- [`docs/playbook.md`](docs/playbook.md) – recipes, troubleshooting tips, and operational guidance.
- `TemporalComparatorRegistry` and `TemporalScaffolder` are resolved from the container, so you can bind your own implementations to support domain-specific equality logic or code generation.
- [`CHANGELOG.md`](CHANGELOG.md) – notable release changes and upgrade notes.
