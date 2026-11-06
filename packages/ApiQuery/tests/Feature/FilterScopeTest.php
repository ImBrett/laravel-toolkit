<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Feature;

use ImBrett\LaravelApiQuery\Support\FilterOperatorManager;
use ImBrett\LaravelApiQuery\Tests\Support\Author;
use ImBrett\LaravelApiQuery\Tests\Support\Category;
use ImBrett\LaravelApiQuery\Tests\Support\Post;
use ImBrett\LaravelApiQuery\Tests\TestCase;

final class FilterScopeTest extends TestCase
{
    public function testFiltersCanTargetBaseColumns(): void
    {
        Post::insert([
            [
                'title' => 'First',
                'body' => null,
                'views' => 10,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Second',
                'body' => null,
                'views' => 5,
                'author_id' => 1,
                'category_id' => 1,
            ],
        ]);

        $results = Post::query()
            ->filter(['views>7'])
            ->pluck('title')
            ->all();

        $this->assertSame(['First'], $results);
    }

    public function testFiltersCanTargetRelatedColumns(): void
    {
        $categoryA = Category::create([
            'name' => 'Tech',
        ]);
        $categoryB = Category::create([
            'name' => 'Life',
        ]);
        Author::create([
            'name' => 'Alice',
        ]);

        Post::create([
            'title' => 'Laravel Tips',
            'body' => null,
            'views' => 25,
            'category_id' => $categoryA->id,
            'author_id' => 1,
        ]);
        Post::create([
            'title' => 'Morning Routine',
            'body' => null,
            'views' => 30,
            'category_id' => $categoryB->id,
            'author_id' => 1,
        ]);

        $results = Post::query()
            ->filter(['category.name=Tech'])
            ->pluck('title')
            ->all();

        $this->assertSame(['Laravel Tips'], $results);
    }

    public function testFiltersSupportWhereInOperator(): void
    {
        Post::insert([
            [
                'title' => 'First',
                'body' => null,
                'views' => 10,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Second',
                'body' => null,
                'views' => 5,
                'author_id' => 1,
                'category_id' => 1,
            ],
        ]);

        $results = Post::query()
            ->filter(['title()First,Second'])
            ->pluck('title')
            ->all();

        $this->assertSame(['First', 'Second'], $results);
    }

    public function testFiltersSupportLikeOperator(): void
    {
        Post::insert([
            [
                'title' => 'Laravel First',
                'body' => null,
                'views' => 10,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'World Second',
                'body' => null,
                'views' => 5,
                'author_id' => 1,
                'category_id' => 1,
            ],
        ]);

        $results = Post::query()
            ->filter(['title~Laravel'])
            ->pluck('title')
            ->all();

        $this->assertSame(['Laravel First'], $results);
    }

    public function testFiltersWithUnknownOperatorThrow(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Post::query()->filter(['title^value'])->get();
    }

    public function testFiltersCanUseCustomOperators(): void
    {
        app(FilterOperatorManager::class)->extend('between', function ($query, string $field, string $value) {
            [$start, $end] = array_pad(explode(',', $value), 2, null);

            return $query->whereBetween($field, [(int)$start, (int)$end]);
        });

        Post::insert([
            [
                'title' => 'Within Range',
                'body' => null,
                'views' => 12,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Outside Range',
                'body' => null,
                'views' => 50,
                'author_id' => 1,
                'category_id' => 1,
            ],
        ]);

        $results = Post::query()
            ->filter(['viewsbetween10,20'])
            ->pluck('title')
            ->all();

        $this->assertSame(['Within Range'], $results);
    }
}
