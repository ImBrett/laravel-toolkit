<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SlugGenerator
{
    public function generate(Model $model, string $value): string;
}
