<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Article;
use App\Models\Category;
use App\Models\Contributor;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Article', function () {
    describe('index', function () {
        it('returns paginated articles for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            Article::factory()->count(2)->create();

            $this->getJson(route('admins.articles.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('returns paginated articles for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.articles.index'))->assertOk();
        });

        it('filters articles by search', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $article = Article::factory()->create(['title' => 'Unique Search Title']);
            Article::factory()->create(['title' => 'Other Article']);

            $this->getJson(route('admins.articles.index', ['search' => 'Unique Search']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $article->id);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.articles.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows an article with category and author', function () {
            $this->actingAsAdmin(Role::Admin);

            $article = Article::factory()->create();

            $this->getJson(route('admins.articles.show', $article))
                ->assertOk()
                ->assertJsonPath('data.id', $article->id)
                ->assertJsonStructure(['data' => ['category', 'author']]);
        });

        it('returns 404 for unknown article', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->getJson(route('admins.articles.show', Str::uuid()))
                ->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates article status to active and sets published_at', function () {
            $this->actingAsAdmin(Role::Admin);

            $article = Article::factory()->create(['status' => Status::INACTIVE, 'published_at' => null]);

            $this->putJson(route('admins.articles.update', $article), [
                'status' => Status::ACTIVE->value,
            ])->assertOk()
                ->assertJsonPath('data.status.value', Status::ACTIVE->value);

            expect($article->fresh()->published_at)->not->toBeNull();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $article = Article::factory()->create();

            $this->putJson(route('admins.articles.update', $article), [
                'status' => Status::ACTIVE->value,
            ])->assertForbidden();
        });

        it('returns 422 for invalid status', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $article = Article::factory()->create();

            $this->putJson(route('admins.articles.update', $article), [
                'status' => 99,
            ])->assertUnprocessable();
        });
    });

    describe('destroy', function () {
        it('deletes an article for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $article = Article::factory()->create();

            $this->deleteJson(route('admins.articles.destroy', $article))
                ->assertNoContent();

            expect(Article::find($article->id))->toBeNull();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $article = Article::factory()->create();

            $this->deleteJson(route('admins.articles.destroy', $article))
                ->assertForbidden();
        });
    });

    it('does not allow creating articles via POST', function () {
        $this->actingAsAdmin(Role::SuperAdmin);

        $this->postJson(route('admins.articles.index'), [
            'title' => 'New Article',
        ])->assertMethodNotAllowed();
    });
});
