<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelCachedPaginator\Traits\UsesCachedPagination;

/**
 * @use \ImBrett\LaravelCachedPaginator\Traits\UsesCachedPagination<self>
 * @phpstan-use \ImBrett\LaravelCachedPaginator\Traits\UsesCachedPagination<self>
 */
class Post extends Model
{
    use UsesCachedPagination;

    protected $guarded = [];
}
