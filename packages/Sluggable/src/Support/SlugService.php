<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Support;

use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelSluggable\Contracts\SlugGenerator;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;

class SlugService
{
    public function __construct(
        private readonly SlugGenerator $generator,
        private readonly SlugUniqueResolver $uniqueResolver
    ) {
    }

    public function build(Model $model, string $value, string $column, string $separator): string
    {
        $generated = $this->generator->generate($model, $value);

        if ($generated === '') {
            return $generated;
        }

        return $this->uniqueResolver->resolve($model, $column, $generated, $separator);
    }
}
