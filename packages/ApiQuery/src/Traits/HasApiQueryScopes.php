<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Traits;

use ImBrett\LaravelApiQuery\Scopes\FilterScope;
use ImBrett\LaravelApiQuery\Scopes\SearchScope;
use ImBrett\LaravelApiQuery\Scopes\SortScope;

trait HasApiQueryScopes
{
    use SearchScope;
    use SortScope;
    use FilterScope;
}
