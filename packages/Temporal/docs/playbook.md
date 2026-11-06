# Playbook & Troubleshooting

Real projects surface real-world wrinkles. Use this playbook as a grab bag of recipes, diagnostics, and FAQs when integrating temporal data.

## Common Recipes

### Seed the initial snapshot

```php
$account = Account::factory()->create();
AccountBalance::query()->upsertTemporal($account->getKey(), [
    'amount' => '100.0000',
]);
```

- Seeders can call `upsertTemporal` directly; no need to go through the attribute accessor.
- Pass a `$source` string when you want attribution (`'seed:2025-01'`).

### Override `temporal.infinity`

```php
config()->set('temporal.infinity', '9999-01-01 00:00:00');
```

Set this early in the application lifecycle (e.g. in a service provider) so all queries share the same sentinel.

### Query historical deltas

```php
$history = AccountBalance::query()
    ->where('account_id', $accountId)
    ->orderByDesc('valid_from')
    ->limit(10)
    ->get();
```

Use the standard query builder. `upsertTemporal` keeps ranges non-overlapping.

## Troubleshooting

| Symptom | Diagnosis | Fix |
| ------- | --------- | --- |
| Duplicate rows for identical values | Missing columns in `temporalColumns()` or write payload. | Ensure every tracked column is present on both the model and the update array. |
| `LogicException: temporalForeignKey()` | Concrete model forgot to implement `temporalForeignKey()`. | Add the method and return the FK column string. |
| Attribute write silently ignored | The parent model hasn’t been persisted yet. | Save the parent or rely on queued writes (assert with `$model->wasRecentlyCreated`). |
| Integrity constraint violations | Foreign key mismatch. | Confirm the anchor ID exists and your migration uses matching FK constraints. |

## Observability

- Record the `$source` value to trace who triggered a change (user email, job name, integration ID).
- Add Laravel events around `upsertTemporal` by overriding the method and emitting domain-specific events.

## When to create a new history table

- Multiple attributes logically change together? Keep them in the same temporal model.
- Cross-cutting concerns (e.g. addresses and phone numbers) deserve dedicated tables to avoid unnecessary churn.
- Always index the foreign key plus the temporal columns; stick with the provided blueprint macros for defaults.

Still stuck? Open an issue or ping the maintainers with a failing test case—temporal bugs are easiest to reason about with concrete timelines.
