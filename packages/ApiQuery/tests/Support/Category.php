<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}
