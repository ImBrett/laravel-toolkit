<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ImBrett\LaravelApiQuery\Support\Contracts\RelationSortHandler;
use InvalidArgumentException;

class RelationSortHandlerManager
{
    /**
     * @var array<int, RelationSortHandler>
     */
    protected array $handlers = [];

    public function extend(RelationSortHandler $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, Relation $relation, string $column, string $direction): Builder
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($relation)) {
                return $handler->apply($query, $relation, $column, $direction);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No relation sort handler registered for relation type [%s].',
            $relation::class
        ));
    }
}
