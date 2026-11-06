# Temporal Attributes

Temporal attributes let you expose history-backed data as if it were a native property on your aggregate model. The heavy lifting—querying the latest version, queuing writes, attributing sources—happens behind the scenes.

## Defining an Attribute

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use ImBrett\LaravelTemporal\Traits\HasTemporalAttributes;
use App\Models\AccountBalance; // extends TemporalModel

class Account extends Model
{
    use HasTemporalAttributes;

    protected function balance(): Attribute
    {
        return $this->temporalAttribute(AccountBalance::class, 'amount');
    }
}
```

`temporalAttribute()` accepts up to four arguments:

1. **Temporal model class** – must extend `TemporalModel`.
2. **Column name** – the column exposed on the parent model.
3. **Optional “in” closure** – transforms values before writing (normalise units, clamp precision).
4. **Optional “out” closure** – transforms values when reading (casting, formatting).

## Lifecycle Semantics

| Scenario | Behaviour |
| -------- | --------- |
| Reading (`$account->balance`) | Fetches the current temporal row via the `current()` scope. |
| Writing on a persisted model | Immediately calls `upsertTemporal`, stamping the source via `config('temporal.user_resolver')`. |
| Writing on an unsaved model | Queues the payload until the model has a primary key. |
| Legacy `_pending_temporal_*` attributes | Automatically flushed on save for backwards compatibility. |

## Handling Unsaved Models

Pending writes live in `protected array $pendingTemporalWrites`. During the `saved` event the trait:

1. Converts legacy `_pending_temporal_*` attributes into write payloads.
2. Flushes queued writes in FIFO order.
3. Clears the queue to prevent duplicate updates.

Want to extend the behaviour? Override your model’s `booted()` method, call `static::bootHasTemporalAttributes()`, and register additional listeners.

## Transformation Callbacks

```php
protected function balance(): Attribute
{
    return $this->temporalAttribute(
        AccountBalance::class,
        'amount',
        in: fn ($value) => (string) number_format($value, 4, '.', ''), // normalise precision
        out: fn ($value) => $value !== null ? (float) $value : null    // cast to float on read
    );
}
```

Use `in` to enforce a canonical representation and `out` to tailor the consumer experience.

## Testing Strategies

Leverage the shipped fixtures (`tests/Support/Account`, `tests/Support/AccountBalance`) or mirror the patterns in `HasTemporalAttributesTest`:

- Unsaved writes stay in-memory until the model is persisted.
- Saving the model flushes queued records and sets the `source`.
- Legacy `_pending_temporal_*` keys still trigger the pipeline.

Need to fake the user attribution? Override the `temporal.user_resolver` config in your test case.

Continue with the [Schema & CLI Toolkit](schema-and-cli.md) to see how migrations and stubs tie into the attribute workflow.
