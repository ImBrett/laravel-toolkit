<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Support\Uniqueness;

use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;

class DatabaseSlugUniqueResolver implements SlugUniqueResolver
{
    public function resolve(Model $model, string $column, string $slug, string $separator): string
    {
        if (!config('sluggable.unique', true)) {
            return $slug;
        }

        $base = $slug;
        $suffix = 1;

        while ($this->slugExists($model, $column, $slug)) {
            $slug = $base . $separator . $suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(Model $model, string $column, string $slug): bool
    {
        $query = $model->newQuery()->where($column, $slug);

        if ($model->getKey()) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        return $query->exists();
    }
}
