<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Traits\Concerns;

use InvalidArgumentException;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait ResolvesSlugConfiguration
{
    protected function slugColumn(): string
    {
        if (method_exists($this, 'getSlugColumn')) {
            return (string)$this->getSlugColumn();
        }

        if (property_exists($this, 'slugColumn')) { // @phpstan-ignore-line
            /** @var string */
            return $this->slugColumn;
        }

        return (string)config('sluggable.column', 'slug');
    }

    protected function slugSourceColumn(): ?string
    {
        if (method_exists($this, 'getSlugSourceColumn')) {
            return (string)$this->getSlugSourceColumn();
        }

        if (property_exists($this, 'slugSourceColumn')) { // @phpstan-ignore-line
            /** @var string */
            return $this->slugSourceColumn;
        }

        $configured = config('sluggable.source_column');

        return $configured === null ? null : (string)$configured;
    }

    protected function slugSourceValue(): ?string
    {
        if (method_exists($this, 'getSlugSourceValue')) {
            /** @var string|null $value */
            $value = $this->getSlugSourceValue();
        } else {
            $source = $this->slugSourceColumn();

            if ($source === null) {
                throw new InvalidArgumentException(sprintf(
                    'Model [%s] must define a slug source column or override slugSourceValue().',
                    static::class
                ));
            }

            $value = $this->getAttribute($source);
        }

        return $value === null ? null : (string)$value;
    }

    protected function slugSeparator(): string
    {
        return (string)config('sluggable.separator', '-');
    }

    protected function shouldRegenerateSlugOnUpdate(): bool
    {
        if (property_exists($this, 'regenerateSlugOnUpdate')) { // @phpstan-ignore-line
            /** @var bool */
            return $this->regenerateSlugOnUpdate;
        }

        return (bool)config('sluggable.regenerate_on_update', true);
    }

    protected function shouldForceRegenerationOnCreate(): bool
    {
        return false;
    }

    protected function slugSourceHasChanged(): bool
    {
        $source = $this->slugSourceColumn();

        return $source !== null && $this->isDirty($source);
    }

    protected function slugColumnHasValue(): bool
    {
        return (string)$this->getAttribute($this->slugColumn()) !== '';
    }

    protected function slugColumnIsDirty(): bool
    {
        return $this->isDirty($this->slugColumn());
    }
}
