<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ImBrett\LaravelApiQuery\Traits\HasApiQueryScopes;

/**
 * @property string $title
 * @property int $views
 */
class Post extends Model
{
    use HasApiQueryScopes;

    /**
     * @var array<int, string>
     */
    public array $searchable = ['title', 'body'];

    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
