<?php

use App\Enums\Status;
use App\Models\Article;
use Illuminate\Support\Str;

describe('Article', function () {
    describe('index', function () {
        it('returns paginated articles', function () {
            Article::factory()->count(2)->active()->create();

            $this->getJson(route('articles.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('filters articles by search', function () {
            $article = Article::factory()->active()->create(['title' => 'Public Search Title']);
            Article::factory()->active()->create(['title' => 'Another Title']);

            $this->getJson(route('articles.index', ['search' => 'Public Search']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $article->id);
        });
    });

    describe('show', function () {
        it('shows an article with category and author', function () {
            $article = Article::factory()->active()->create();

            $this->getJson(route('articles.show', $article))
                ->assertOk()
                ->assertJsonPath('data.id', $article->id)
                ->assertJsonStructure(['data' => ['category', 'author']]);
        });

        it('returns 404 for unknown article', function () {
            $this->getJson(route('articles.show', Str::uuid()))
                ->assertNotFound();
        });
    });
});
