<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SlugUniqueResolver
{
    public function resolve(Model $model, string $column, string $slug, string $separator): string;
}
