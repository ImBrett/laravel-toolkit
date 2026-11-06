<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Support\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ImBrett\LaravelSluggable\Contracts\SlugGenerator;

class DefaultSlugGenerator implements SlugGenerator
{
    public function __construct(
        private string $separator = '-'
    ) {
    }

    public function generate(Model $model, string $value): string
    {
        unset($model);

        return Str::slug($value, $this->separator);
    }
}
