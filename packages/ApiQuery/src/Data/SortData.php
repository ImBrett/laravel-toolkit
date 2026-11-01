<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Data;

final readonly class SortData
{
    public function __construct(
        public string $column,
        public string $direction = 'asc'
    ) {
        $direction = strtolower($this->direction);
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException('Sort direction must be either asc or desc.');
        }
    }
}
