<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $guarded = [];
    protected $table = 'profiles';
}
