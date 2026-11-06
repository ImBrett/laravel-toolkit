<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ImBrett\LaravelSluggable\Support\SlugManager;
use ImBrett\LaravelSluggable\Traits\Concerns\ResolvesSlugConfiguration;

/**
 * @mixin Model
 */
trait Sluggable
{
    use ResolvesSlugConfiguration;

    /**
     * @param Builder<static> $query
     */
    protected function performInsert(Builder $query): bool
    {
        $this->generateSlugWhen(fn (): bool => $this->shouldGenerateSlugOnCreate());

        $inserted = parent::performInsert($query);

        if ($inserted) {
            $this->syncModelKeySlugAfterInsert();
        }

        return $inserted;
    }

    /**
     * @param Builder<static> $query
     */
    protected function performUpdate(Builder $query): bool
    {
        $this->generateSlugWhen(fn (): bool => $this->shouldGenerateSlugOnUpdate());

        return parent::performUpdate($query);
    }

    /**
     * @param callable():bool $policy
     */
    protected function generateSlugWhen(callable $policy): void
    {
        if (!$policy()) {
            return;
        }

        $value = $this->slugSourceValue();

        if ($value === null || $value === '') {
            $this->prepareModelKeySlugPlaceholder();

            return;
        }

        $column = $this->slugColumn();

        $this->setAttribute(
            $column,
            SlugManager::instance()->build(
                $this,
                $value,
                $column,
                $this->slugSeparator()
            )
        );
    }

    protected function shouldGenerateSlugOnCreate(): bool
    {
        return !$this->slugColumnHasValue() || $this->shouldForceRegenerationOnCreate();
    }

    protected function shouldGenerateSlugOnUpdate(): bool
    {
        return $this->shouldRegenerateSlugOnUpdate()
            && !$this->slugColumnIsDirty()
            && $this->slugSourceHasChanged();
    }

    protected function prepareModelKeySlugPlaceholder(): void
    {
        if (!$this->slugSourceIsModelKey() || $this->slugColumnHasValue()) {
            return;
        }

        $this->setAttribute($this->slugColumn(), '');
    }

    protected function syncModelKeySlugAfterInsert(): void
    {
        if (!$this->slugSourceIsModelKey()) {
            return;
        }

        $slug = (string)$this->getKey();

        if ($this->getAttribute($this->slugColumn()) === $slug) {
            return;
        }

        DB::table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())
            ->update([
                $this->slugColumn() => $slug,
            ]);

        $this->setAttribute($this->slugColumn(), $slug);
        $this->syncOriginalAttribute($this->slugColumn());
    }

    protected function slugSourceIsModelKey(): bool
    {
        return $this->slugSourceColumn() === $this->getKeyName();
    }
}
