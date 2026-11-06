<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Support;

use ImBrett\LaravelSluggable\Contracts\SlugGenerator;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;
use ImBrett\LaravelSluggable\Support\Generators\DefaultSlugGenerator;
use ImBrett\LaravelSluggable\Support\Uniqueness\DatabaseSlugUniqueResolver;

final class SlugManager
{
    private static ?self $instance = null;

    private ?SlugGenerator $generator = null;
    private ?SlugUniqueResolver $uniqueResolver = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setGenerator(?SlugGenerator $generator): void
    {
        $this->generator = $generator;
    }

    public function setUniqueResolver(?SlugUniqueResolver $resolver): void
    {
        $this->uniqueResolver = $resolver;
    }

    public function generator(): SlugGenerator
    {
        return $this->generator ?? new DefaultSlugGenerator();
    }

    public function uniqueResolver(): SlugUniqueResolver
    {
        return $this->uniqueResolver ?? new DatabaseSlugUniqueResolver();
    }

    public function build(
        \Illuminate\Database\Eloquent\Model $model,
        string $value,
        string $column,
        string $separator
    ): string {
        $slug = $this->generator()->generate($model, $value);

        return $this->uniqueResolver()->resolve($model, $column, $slug, $separator);
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
