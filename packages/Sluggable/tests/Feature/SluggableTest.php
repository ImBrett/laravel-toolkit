<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use ImBrett\LaravelSluggable\Contracts\SlugGenerator;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;
use ImBrett\LaravelSluggable\Support\SlugManager;
use ImBrett\LaravelSluggable\Tests\Support\Article;
use ImBrett\LaravelSluggable\Tests\TestCase;

final class SluggableTest extends TestCase
{
    public function testSlugIsGeneratedFromSourceColumn(): void
    {
        $article = Article::create([
            'title' => 'Hello World',
        ]);

        $this->assertSame('hello-world', $article->slug);
    }

    public function testSlugIsUniqueBySuffix(): void
    {
        Article::create([
            'title' => 'Duplicate Title',
        ]);

        $article = Article::create([
            'title' => 'Duplicate Title',
        ]);

        $this->assertSame('duplicate-title-1', $article->slug);
    }

    public function testSlugUpdatesWhenSourceChanges(): void
    {
        $article = Article::create([
            'title' => 'Original',
        ]);

        $article->update([
            'title' => 'Updated Title',
        ]);

        $article->refresh();

        $this->assertSame('updated-title', $article->slug);
    }

    public function testSlugIsGeneratedWhenEventsAreFaked(): void
    {
        Event::fake();

        $article = Article::create([
            'title' => 'Event Faked',
        ]);

        $this->assertSame('event-faked', $article->slug);
    }

    public function testSlugCanBeSyncedFromModelKeyAfterInsert(): void
    {
        $article = new class() extends Article {
            protected string $slugSourceColumn = 'id';
        };

        $article->fill([
            'title' => 'Uses Id',
        ]);

        $article->save();

        $this->assertSame((string)$article->id, $article->slug);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'slug' => (string)$article->id,
        ]);
    }

    public function testCustomGeneratorCanBeOverridden(): void
    {
        $generator = new class() implements SlugGenerator {
            public function generate(\Illuminate\Database\Eloquent\Model $model, string $value): string
            {
                return Str::of($value)->swap([
                    ' ' => '_',
                ])->upper()->value();
            }
        };

        SlugManager::instance()->setGenerator($generator);

        $article = Article::create([
            'title' => 'Custom Slug',
        ]);

        $this->assertSame('CUSTOM_SLUG', $article->slug);
    }

    public function testCustomUniquenessResolverCanBeOverridden(): void
    {
        $resolver = new class() implements SlugUniqueResolver {
            public function resolve(\Illuminate\Database\Eloquent\Model $model, string $column, string $slug, string $separator): string
            {
                return $slug . $separator . 'custom';
            }
        };

        SlugManager::instance()->setUniqueResolver($resolver);

        $article = Article::create([
            'title' => 'Hook',
        ]);

        $this->assertSame('hook-custom', $article->slug);
    }

    public function testSlugRespectsManuallySetValue(): void
    {
        $article = Article::create([
            'title' => 'Manual',
            'slug' => 'pre-set',
        ]);

        $this->assertSame('pre-set', $article->slug);

        $article->update([
            'title' => 'Changed',
            'slug' => 'custom',
        ]);

        $article->refresh();

        $this->assertSame('custom', $article->slug);
    }
}
