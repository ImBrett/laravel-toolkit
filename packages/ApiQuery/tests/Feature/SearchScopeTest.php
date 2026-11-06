<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelApiQuery\Scopes\SearchScope;
use ImBrett\LaravelApiQuery\Tests\Support\Author;
use ImBrett\LaravelApiQuery\Tests\Support\Category;
use ImBrett\LaravelApiQuery\Tests\Support\Post;
use ImBrett\LaravelApiQuery\Tests\TestCase;

final class SearchScopeTest extends TestCase
{
    public function testSearchMatchesFillableColumnsWhenNoneProvided(): void
    {
        $category = Category::create([
            'name' => 'General',
        ]);
        $author = Author::create([
            'name' => 'Alice',
        ]);

        Post::create([
            'title' => 'First Post',
            'body' => 'Laravel tips',
            'views' => 0,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);
        Post::create([
            'title' => 'Second',
            'body' => 'Other content',
            'views' => 0,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        $results = Post::query()
            ->search('tips')
            ->pluck('title')
            ->all();

        $this->assertSame(['First Post'], $results);
    }

    public function testSearchCanTargetRelatedColumns(): void
    {
        $tech = Category::create([
            'name' => 'Tech',
        ]);
        $life = Category::create([
            'name' => 'Life',
        ]);
        $author = Author::create([
            'name' => 'Alice',
        ]);

        Post::create([
            'title' => 'Laravel News',
            'body' => null,
            'views' => 0,
            'category_id' => $tech->id,
            'author_id' => $author->id,
        ]);
        Post::create([
            'title' => 'Morning Habits',
            'body' => null,
            'views' => 0,
            'category_id' => $life->id,
            'author_id' => $author->id,
        ]);

        $results = Post::query()
            ->search('Life', ['title'], [
                'category' => ['name'],
            ])
            ->pluck('title')
            ->all();

        $this->assertSame(['Morning Habits'], $results);
    }

    public function testSearchIgnoresEmptyKeyword(): void
    {
        Post::create([
            'title' => 'Some Title',
            'body' => 'Content',
            'views' => 1,
            'category_id' => Category::create([
                'name' => 'General',
            ])->id,
            'author_id' => Author::create([
                'name' => 'Author',
            ])->id,
        ]);

        $query = Post::query();

        $result = $query->search(null);

        $this->assertSame($query, $result);
        $this->assertSame(1, $result->count());
    }

    public function testResolveSearchableColumnsFallsBackToFillable(): void
    {
        $model = new class() extends Model {
            use SearchScope;

            protected $table = 'posts';
            protected $fillable = ['title'];

            /**
             * @param  array<int, string>|null  $columns
             * @return array<int, string>
             */
            public function invokeResolve(?array $columns = null): array
            {
                return $this->resolveSearchableColumns($this, $columns);
            }
        };

        $resolved = $model->invokeResolve(null);

        $this->assertSame(['posts.title'], $resolved);
    }
}
