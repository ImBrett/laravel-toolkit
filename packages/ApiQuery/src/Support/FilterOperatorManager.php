<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FilterOperatorManager
{
    /**
     * @var array<string, callable(Builder<Model>, string, string): Builder<Model>>
     */
    protected array $operators = [];

    public function __construct()
    {
        $this->registerDefaultOperators();
    }

    /**
     * @param callable(Builder<Model>, string, string): Builder<Model> $handler
     * @return $this
     */
    public function extend(string $operator, callable $handler): self
    {
        if ($operator === '') {
            throw new InvalidArgumentException('Filter operator key must be a non-empty string.');
        }

        $this->operators[$operator] = $handler;

        return $this;
    }

    public function has(string $operator): bool
    {
        return array_key_exists($operator, $this->operators);
    }

    /**
     * @return list<string>
     */
    public function operators(): array
    {
        return array_keys($this->operators);
    }

    /**
     * @return non-empty-string
     */
    public function detectOperator(string $filter): string
    {
        foreach ($this->operatorsSortedByLength() as $operator) {
            if ($operator !== '' && str_contains($filter, $operator)) {
                return $operator;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Filter operator was not found. Available operators: %s',
            implode(', ', $this->operators())
        ));
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, string $operator, string $field, string $value): Builder
    {
        if (!$this->has($operator)) {
            throw new InvalidArgumentException(sprintf('Unknown filter operator [%s].', $operator));
        }

        /** @var callable(Builder<Model>, string, string): Builder<Model> $handler */
        $handler = $this->operators[$operator];

        return $handler($query, $field, $value);
    }

    protected function registerDefaultOperators(): void
    {
        $this->extend('()', static function (Builder $query, string $field, string $value): Builder {
            $values = array_values(array_filter(
                explode(',', $value),
                static fn (string $item): bool => $item !== ''
            ));

            /** @var Builder<Model> $builder */
            $builder = $query->whereIn($field, $values);

            return $builder;
        });

        $this->extend('~', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            'LIKE',
            '%' . $value . '%'
        ));

        $this->extend('!=', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            '!=',
            $value
        ));

        $this->extend('>=', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            '>=',
            $value
        ));

        $this->extend('<=', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            '<=',
            $value
        ));

        $this->extend('>', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            '>',
            $value
        ));

        $this->extend('<', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            '<',
            $value
        ));

        $this->extend('=', static fn (Builder $query, string $field, string $value): Builder => $query->where(
            $field,
            '=',
            $value
        ));
    }

    /**
     * @return list<string>
     */
    protected function operatorsSortedByLength(): array
    {
        $operators = $this->operators();

        usort(
            $operators,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
        );

        return $operators;
    }
}
