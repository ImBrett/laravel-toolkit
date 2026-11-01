# Schema & CLI Toolkit

Setting up temporal tables should be ergonomic. The package ships with blueprint macros, artisan generators, and publishable stubs so you can go from concept to migration in seconds.

## Blueprint Macros

Registered inside the `TemporalServiceProvider`, the macros augment `Illuminate\Database\Schema\Blueprint` with temporal awareness.

### `temporalColumns()`

Adds the canonical datetime columns with sensible defaults:

| Column | Type | Notes |
| ------ | ---- | ----- |
| `valid_from` | `datetime` | Start of the validity window. |
| `valid_to` | `datetime` | End of the validity window. Typically the “infinity” sentinel until closed. |
| `tx_at` | `datetime` | Transaction timestamp, defaults to `now()` via `useCurrent()`. |

### `temporalIndexes(string $foreignKey)`

Creates supporting indexes:

- `[$foreignKey, 'valid_to']` – fast lookup for the current row.
- `[$foreignKey, 'valid_from', 'valid_to']` – efficient range scans for “as-of” queries.

### Usage

```php
Schema::create('account_balances', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('account_id')->constrained()->cascadeOnDelete();
    $table->decimal('amount', 18, 4)->nullable();

    $table->temporalColumns();
    $table->string('source')->nullable();
    $table->temporalIndexes('account_id');
});
```

## Artisan Generator: `make:temporal`

Scaffold a temporal model plus matching migration in one command:

```bash
php artisan make:temporal AccountBalance "App\Models\Account" --columns=amount,available_credit
```

Generated artifacts:

- `app/Models/AccountBalance.php` – extends `TemporalModel`, populates `temporalColumns()` and `temporalForeignKey()`.
- `database/migrations/{timestamp}_create_accounts_table.php` – includes blueprint macros and requested decimal columns.

Under the hood the command copies publishable stubs from `packages/Temporal/stubs`.

## Customising Stubs

```bash
php artisan vendor:publish --tag=temporal-stubs
```

Now edit:

- `stubs/temporal-model.stub` – change namespace placement, docblocks, default casts, etc.
- `stubs/temporal-migration.stub` – swap decimal columns for enums, JSON fields, or anything else your domain requires.

## Advanced Scenarios

- **Multiple temporal columns** – pass `--columns=rate,limit` to generate multiple decimal placeholders; update the migration stub to adjust data types.
- **Non-decimal data** – publish the stubs and replace the generated column definitions with your preferred schema builder calls.
- **Custom destinations** – override the generator command or fork the stub to place models in a package namespace instead of `app/Models`.

Ready to validate your setup? Read the [Testing & Tooling](testing.md) guide for strategies that keep regressions at bay.
