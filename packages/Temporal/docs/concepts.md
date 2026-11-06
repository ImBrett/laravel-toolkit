# Core Concepts

Temporal data captures *what changed* and *when it changed* without overwriting the past. This guide explains how the Temporal package models that behaviour, the guarantees you inherit, and how to extend the primitives to fit your domain.

## Design Goals

- **Immutable history** – each update produces a new row while prior rows remain untouched.
- **Transactional safety** – updates run inside database transactions and acquire row locks to prevent overlap.
- **First-class querying** – ask “what is current?” or “what was true at time *t*?” with expressive Eloquent scopes.
- **Laravel-native ergonomics** – keep using Models, relationships, and attribute accessors; no custom query DSL to learn.

## Architecture Overview

| Element | Role |
| ------- | ---- |
| `ImBrett\LaravelTemporal\Models\TemporalModel` | Abstract base that disables default timestamps, applies casts, and enforces temporal metadata. |
| `ImBrett\LaravelTemporal\Traits\Temporal` | Implements query scopes plus the atomic `upsertTemporal` workflow. Included by the base model. |
| `ImBrett\LaravelTemporal\Contracts\TemporalContract` | Interface that documents required hooks (`temporalColumns`, `temporalForeignKey`). Helpful for static analysis. |

Your concrete models extend `TemporalModel` and implement two methods:

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

- `temporalColumns()` lists the value columns that must be equal to treat two rows as identical. They drive the change-detection logic.
- `temporalForeignKey()` identifies the column anchoring the history table to its parent aggregate.

## Upsert Lifecycle

`upsertTemporal(int $anchorId, array $attributes, ?string $source = null)` executes the full workflow inside a single `DB::transaction()`:

1. **Lock the current row** (if present) using `SELECT … FOR UPDATE` to avoid overlapping history ranges.
2. **Short-circuit unchanged writes** by comparing every column returned from `temporalColumns()`. Identical payloads return the existing row.
3. **Close the current version** by stamping `valid_to` with `now()`.
4. **Insert the new version** with:
   - `valid_from = now()`
   - `valid_to = config('temporal.infinity')`
   - `tx_at = now()`
   - `source = $source` (or the configured default)

All state transitions are atomic; concurrent writers serialize on the row lock.

## Query Helpers

```php
AccountBalance::current()->get();             // every active row
AccountBalance::asOf('2025-01-01 12:34');     // row valid at the given moment
AccountBalance::current()->where('amount', '>', 0); // chain additional constraints
```

- `scopeCurrent()` filters `valid_to = config('temporal.infinity')`.
- `scopeAsOf(string|\DateTimeInterface $moment)` narrows the range to rows where `valid_from <= moment < valid_to`.

## Casting & Relationships

The base class casts the temporal columns to `immutable_datetime`. Extend it with domain-specific casts and relationships just like any other Eloquent model:

```php
protected $casts = [
    'amount' => 'decimal:4',
    'valid_from' => 'immutable_datetime',
    'valid_to' => 'immutable_datetime',
    'tx_at' => 'immutable_datetime',
];

public function account(): BelongsTo
{
    return $this->belongsTo(Account::class);
}
```

## Error Handling

- Forgetting to override `temporalForeignKey()` triggers a `LogicException`.
- Missing values in `$attributes` default to `null` during comparisons, forcing a new version. Always populate every column returned by `temporalColumns()`.

## Extending the Trait

- Add more scopes (`between`, `since`, `forSource`) on your concrete model; they inherit the same transactional guarantees.
- Override `newModelQuery()` if you need tenant or soft-delete constraints. The helper methods rely on the standard query builder chain.

## Extension points

- **Custom equality strategies** – register comparators with `TemporalComparatorRegistry` to short-circuit updates when values are effectively unchanged:

  ```php
  use ImBrett\LaravelTemporal\Support\TemporalComparatorRegistry;

  app(TemporalComparatorRegistry::class)->extend(AccountBalance::class, function (AccountBalance $current, array $attributes): bool {
      return isset($attributes['amount'])
          && abs((float) $current->amount - (float) $attributes['amount']) < 0.01;
  });
  ```

  Comparators inherit across the class tree, so registering on an abstract base applies to subclasses.

- **Replace the scaffolder** – `make:temporal` resolves a `TemporalScaffolder` from the container. Bind your own implementation to customise generated stubs, namespaces, or destinations:

  ```php
  use ImBrett\LaravelTemporal\Console\Support\TemporalScaffolder;

  app()->bind(TemporalScaffolder::class, CustomScaffolder::class);
  ```

  The interface remains minimal so you can drop in tenant-aware generators or alternative file layouts.

Continue with the [Temporal Attributes](attributes.md) guide to expose these history-backed values on your aggregate models.
