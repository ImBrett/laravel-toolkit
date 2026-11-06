<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ImBrett\LaravelApiQuery\Data\SortData;
use ImBrett\LaravelApiQuery\Support\Contracts\RelationSortHandler;
use ImBrett\LaravelApiQuery\Support\RelationSortHandlerManager;
use ImBrett\LaravelApiQuery\Tests\Support\Author;
use ImBrett\LaravelApiQuery\Tests\Support\Category;
use ImBrett\LaravelApiQuery\Tests\Support\Post;
use ImBrett\LaravelApiQuery\Tests\Support\Profile;
use ImBrett\LaravelApiQuery\Tests\TestCase;
use ImBrett\LaravelApiQuery\Traits\HasApiQueryScopes;

final class SortScopeTest extends TestCase
{
    public function testSortingOnRootColumn(): void
    {
        $category = Category::create([
            'name' => 'General',
        ]);
        $author = Author::create([
            'name' => 'Alice',
        ]);

        Post::create([
            'title' => 'B entry',
            'views' => 10,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);
        Post::create([
            'title' => 'A entry',
            'views' => 20,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        $titles = Post::query()
            ->sort(new SortData('views', 'desc'))
            ->pluck('title')
            ->all();

        $this->assertSame(['A entry', 'B entry'], $titles);
    }

    public function testSortingOnBelongsToRelation(): void
    {
        $zoe = Author::create([
            'name' => 'Zoe',
        ]);
        $alice = Author::create([
            'name' => 'Alice',
        ]);
        $category = Category::create([
            'name' => 'General',
        ]);

        Post::create([
            'title' => 'First',
            'views' => 10,
            'category_id' => $category->id,
            'author_id' => $zoe->id,
        ]);
        Post::create([
            'title' => 'Second',
            'views' => 15,
            'category_id' => $category->id,
            'author_id' => $alice->id,
        ]);

        $titles = Post::query()
            ->sort(new SortData('author.name', 'asc'))
            ->pluck('title')
            ->all();

        $this->assertSame(['Second', 'First'], $titles);
    }

    public function testSortingOnUnsupportedRelationThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Author::create([
            'name' => 'Alice',
        ]);
        Category::create([
            'name' => 'General',
        ]);

        $model = new class() extends Post {
            protected $table = 'posts';

            public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
            {
                return $this->hasMany(Post::class, 'author_id');
            }
        };

        $model->newQuery()->setModel($model)->sort(new SortData('comments.title', 'asc'));
    }

    public function testSortingWithInvalidRelationFormatThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Post::query()->sort(new SortData('unknown', 'asc'));
    }

    public function testSortingWithMissingRelationThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Post::query()->sort(new SortData('missing.name', 'asc'));
    }

    public function testSortingOnHasOneRelation(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->string('bio');
            $table->timestamps();
        });

        try {
            $authorModel = new class() extends Author {
                use HasApiQueryScopes;

                protected $table = 'authors';

                public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
                {
                    return $this->hasOne(Profile::class, 'author_id');
                }
            };

            $authorClass = $authorModel::class;

            $beta = $authorClass::create([
                'name' => 'Beta',
            ]);
            $alpha = $authorClass::create([
                'name' => 'Alpha',
            ]);

            Profile::create([
                'author_id' => $beta->id,
                'bio' => 'Zeta',
            ]);
            Profile::create([
                'author_id' => $alpha->id,
                'bio' => 'Alpha',
            ]);

            $names = $authorClass::query()
                ->sort(new SortData('profile.bio', 'asc'))
                ->pluck('name')
                ->all();

            $this->assertSame(['Alpha', 'Beta'], $names);
        } finally {
            Schema::dropIfExists('profiles');
        }
    }

    public function testCustomRelationSortHandlerCanBeRegistered(): void
    {
        $manager = app(RelationSortHandlerManager::class);

        $manager->extend(new class() implements RelationSortHandler {
            public function supports(\Illuminate\Database\Eloquent\Relations\Relation $relation): bool
            {
                return $relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany;
            }

            public function apply(\Illuminate\Database\Eloquent\Builder $query, \Illuminate\Database\Eloquent\Relations\Relation $relation, string $column, string $direction): \Illuminate\Database\Eloquent\Builder
            {
                /** @var \Illuminate\Database\Eloquent\Relations\HasMany $relation */
                $alias = 'child_posts';
                $parentTable = $relation->getParent()->getTable();

                return $query->leftJoin(
                    $relation->getRelated()->getTable() . ' as ' . $alias,
                    $relation->getQualifiedParentKeyName(),
                    '=',
                    $alias . '.' . $relation->getForeignKeyName()
                )->select($parentTable . '.*')
                    ->orderBy("{$alias}.{$column}", $direction);
            }
        });

        $category = Category::create([
            'name' => 'General',
        ]);
        $author = Author::create([
            'name' => 'Alice',
        ]);

        $post = Post::create([
            'title' => 'Primary',
            'views' => 10,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        Post::create([
            'title' => 'Secondary',
            'views' => 5,
            'category_id' => $category->id,
            'author_id' => $author->id,
            'parent_id' => $post->id,
        ]);

        $model = new class() extends Post {
            use HasApiQueryScopes;

            protected $table = 'posts';

            public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
            {
                return $this->hasMany(self::class, 'parent_id');
            }
        };

        $result = $model->newQuery()->sort(new SortData('children.views', 'desc'))->value('title');

        $this->assertSame('Primary', $result);
    }
}
