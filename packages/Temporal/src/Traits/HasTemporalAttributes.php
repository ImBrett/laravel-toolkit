<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Traits;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ImBrett\LaravelTemporal\Models\TemporalModel;
use InvalidArgumentException;

use function throw_unless;

trait HasTemporalAttributes
{
    /**
     * @var array<int, array{model: class-string<TemporalModel>, column: string, value: mixed}>
     */
    protected array $pendingTemporalWrites = [];

    protected static function bootHasTemporalAttributes(): void
    {
        /**
         * Registers a model event listener for the "saved" event.
         *
         * When a model is saved, this listener scans the model's attributes for keys
         * that start with "_pending_temporal_". For each such key, it extracts the fully
         * qualified class name (FQCN) and column name from the key, then calls the
         * `upsertTemporal` method on the resolved model instance, passing the model's
         * primary key, the pending temporal value, and the resolved user.
         *
         * After processing, the pending temporal attribute is removed from the model's attributes.
         *
         * Key format expected: _pending_temporal_{ModelFQCN}_{column}
         *
         * @param \Illuminate\Database\Eloquent\Model $model The model instance being saved.
         */
        static::saved(function ($model): void {
            /** @var Model&self $model */
            $model->flushLegacyPendingWrites();
            $model->flushQueuedWrites();
        });
    }

    /**
     * Defines a temporal attribute for the model, enabling seamless interaction with a related temporal model.
     * This provides a developer-friendly DX by allowing you to use standard Eloquent attribute accessors and mutators
     * for temporal data, while handling the underlying logic for retrieving and updating values in the temporal model.
     *
     * The getter automatically fetches the current value from the related temporal model, optionally transforming it
     * with a callback for custom output formatting. The setter transparently manages pending values for unsaved models,
     * and upserts values into the temporal model for existing records, with optional input transformation.
     *
     * This abstraction simplifies working with temporal attributes, allowing you to set and get values as if they were
     * regular model attributes
     *
     * @param class-string<TemporalModel> $temporalModel The related temporal model class name.
     * @param string $column The column name in the temporal model to get/set.
     * @param callable|null $in Optional callback to transform the value before saving.
     * @param callable|null $out Optional callback to transform the value when retrieving.
     * @return Attribute<mixed, mixed> The attribute definition.
     */
    protected function temporalAttribute(
        string $temporalModel,
        string $column,
        ?callable $in = null,
        ?callable $out = null
    ): Attribute {
        return Attribute::make(
            get: function () use ($temporalModel, $column, $out) {
                $relation = $this->hasOne($temporalModel);
                /** @var HasOne<TemporalModel, static> $relation */
                $query = $relation->getQuery();
                /** @var EloquentBuilder<TemporalModel> $query */
                $rel = $query->current()->first();
                $val = $rel?->{$column};

                return $out ? $out($val) : $val;
            },
            set: function ($value) use ($temporalModel, $column, $in) {
                $val = $in ? $in($value) : $value;

                if (!$this->exists) {
                    $this->queueTemporalWrite($temporalModel, $column, $val);

                    return;
                }

                $this->writeTemporalValue($temporalModel, $column, $val);
            }
        );
    }

    /**
     * Flushes any legacy pending temporal attribute writes.
     *
     * This method scans the model's attributes for keys that indicate pending temporal writes
     * (keys starting with '_pending_temporal_'), extracts the fully qualified class name (FQCN)
     * and column name from each key, ensures the temporal model class exists, writes the temporal
     * value to the appropriate model and column, and then removes the pending attribute from the model.
     */
    protected function flushLegacyPendingWrites(): void
    {
        /** @var array<int, string> $attributeKeys */
        $attributeKeys = array_keys($this->attributes);

        $keys = array_filter(
            $attributeKeys,
            static fn (string $key): bool => str_starts_with($key, '_pending_temporal_')
        );

        array_walk($keys, function (string $key): void {
            [, , , $fqcn, $column] = explode('_', $key, 5);

            $modelClass = $this->ensureTemporalModelClass($fqcn);

            $this->writeTemporalValue($modelClass, $column, $this->attributes[$key]);
            unset($this->attributes[$key]);
        });
    }

    /**
     * Flushes all queued temporal attribute writes.
     *
     * This method processes the pending temporal writes by iterating through each payload
     * in the `$pendingTemporalWrites` array and writing the temporal value using the
     * `writeTemporalValue` method. After all writes are processed, it clears the queue.
     */
    protected function flushQueuedWrites(): void
    {
        if ($this->pendingTemporalWrites === []) {
            return;
        }

        foreach ($this->pendingTemporalWrites as $payload) {
            $this->writeTemporalValue(
                $payload['model'],
                $payload['column'],
                $payload['value']
            );
        }

        $this->pendingTemporalWrites = [];
    }

    /**
     * Queues a temporal write operation for the specified model and column.
     *
     * This method ensures the provided model is a valid temporal model class,
     * then adds the write operation (model, column, value) to the pending temporal writes queue.
     *
     * @param string $model  The model class name or identifier.
     * @param string $column The column name to be written.
     * @param mixed  $value  The value to be written to the column.
     */
    protected function queueTemporalWrite(string $model, string $column, mixed $value): void
    {
        $modelClass = $this->ensureTemporalModelClass($model);

        $this->pendingTemporalWrites[] = [
            'model' => $modelClass,
            'column' => $column,
            'value' => $value,
        ];
    }

    /**
     * Writes a temporal value to the specified temporal model and column.
     *
     * This method ensures the provided temporal model class is valid and resolves to an instance
     * of TemporalModel. It then upserts the given value for the specified column, associated with
     * the current model's key, and uses the configured user resolver for auditing purposes.
     *
     * @param string $temporalModel The class name or identifier of the temporal model.
     * @param string $column The column name to write the value to.
     * @param mixed $value The value to be written to the temporal column.
     *
     * @throws \InvalidArgumentException If the resolved temporal model is not an instance of TemporalModel.
     */
    protected function writeTemporalValue(string $temporalModel, string $column, mixed $value): void
    {
        $temporalModelClass = $this->ensureTemporalModelClass($temporalModel);

        $instance = app($temporalModelClass);
        throw_unless(
            $instance instanceof TemporalModel,
            InvalidArgumentException::class,
            sprintf('%s must resolve to a TemporalModel instance.', $temporalModelClass)
        );

        $instance->upsertTemporal(
            $this->getKey(),
            [
                $column => $value,
            ],
            call_user_func(config('temporal.user_resolver'))
        );
    }

    /**
     * Ensures that the given model class name is a subclass of TemporalModel.
     *
     * Throws an InvalidArgumentException if the provided class does not extend TemporalModel.
     *
     * @param string $model The fully qualified class name of the model to check.
     * @return class-string<TemporalModel> The validated model class name.
     * @throws \InvalidArgumentException If the model does not extend TemporalModel.
     */
    protected function ensureTemporalModelClass(string $model): string
    {
        throw_unless(
            is_a($model, TemporalModel::class, true),
            InvalidArgumentException::class,
            sprintf(
                'Temporal model [%s] must extend %s.',
                $model,
                TemporalModel::class
            )
        );

        /** @var class-string<TemporalModel> $model */
        return $model;
    }
}
