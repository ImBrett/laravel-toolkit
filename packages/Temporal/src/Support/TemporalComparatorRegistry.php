<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Support;

use ImBrett\LaravelTemporal\Models\TemporalModel;

/**
 * Allows applications to override how temporal models compare value equality.
 *
 * Consumers may register comparators for specific temporal model classes:
 *
 * TemporalComparatorRegistry::extend(AccountBalance::class, function (TemporalModel $current, array $attributes): bool {
 *     // return true when the incoming payload should be treated as identical.
 * });
 */
class TemporalComparatorRegistry
{
    /**
     * @var array<class-string<TemporalModel>, callable(TemporalModel, array<string, mixed>): bool>
     */
    protected array $comparators = [];

    /**
     * @param  class-string<TemporalModel>  $modelClass
     * @param  callable(TemporalModel, array<string, mixed>): bool  $comparator
     * @return $this
     */
    public function extend(string $modelClass, callable $comparator): self
    {
        $this->comparators[$modelClass] = $comparator;

        return $this;
    }

    /**
     * @return callable(TemporalModel, array<string, mixed>): bool|null
     */
    public function resolve(TemporalModel $model): ?callable
    {
        $class = $model::class;

        if (array_key_exists($class, $this->comparators)) {
            return $this->comparators[$class];
        }

        foreach (class_parents($class) ?: [] as $parent) {
            if (isset($this->comparators[$parent])) {
                return $this->comparators[$parent];
            }
        }

        return null;
    }
}
