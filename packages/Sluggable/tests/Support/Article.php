<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelSluggable\Traits\Sluggable;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Article extends Model
{
    use Sluggable;

    protected $table = 'articles';

    protected $fillable = ['title', 'slug'];

    protected string $slugSourceColumn = 'title';
}
