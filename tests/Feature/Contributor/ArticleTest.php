<?php

use App\Enums\Status;
use App\Models\Article;
use Laravel\Sanctum\Sanctum;

describe('Article', function () {
    describe('index', function () {
        it('returns only own articles', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $ownArticle = $this->createArticleForUser($user);
            ['user' => $otherUser] = $this->createContributorWithUser();
            $this->createArticleForUser($otherUser);

            $this->getJson(route('contributors.articles.index'))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $ownArticle->id);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('contributors.articles.index'))->assertUnauthorized();
        });
    });

    describe('store', function () {
        it('creates an article for the authenticated contributor', function () {
            $this->actingAsContributor();
            $payload = $this->validArticlePayload();

            $this->postJson(route('contributors.articles.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.title', $payload['title']);

            $this->assertDatabaseHas('articles', [
                'title' => $payload['title'],
                'status' => Status::INACTIVE->value,
            ]);
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsContributor();

            $this->postJson(route('contributors.articles.store'), [])
                ->assertUnprocessable();
        });

        it('returns 401 when unauthenticated', function () {
            $this->postJson(route('contributors.articles.store'), [])
                ->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows an article', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $article = $this->createArticleForUser($user);

            $this->getJson(route('contributors.articles.show', $article))
                ->assertOk()
                ->assertJsonPath('data.id', $article->id);
        });

        it('returns 401 when unauthenticated', function () {
            $article = Article::factory()->create();

            $this->getJson(route('contributors.articles.show', $article))
                ->assertUnauthorized();
        });
    });

    describe('update', function () {
        it('updates own article', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $article = $this->createArticleForUser($user);
            $payload = $this->validArticlePayload($article->category);

            $this->putJson(route('contributors.articles.update', $article), array_merge($payload, [
                'title' => 'Updated Title',
            ]))->assertOk()
                ->assertJsonPath('data.title', 'Updated Title');
        });

        it('returns 403 when updating another contributors article', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $article = $this->createArticleForUser($otherUser);

            $this->putJson(route('contributors.articles.update', $article), $this->validArticlePayload($article->category))
                ->assertForbidden();
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsContributor();
            $article = $this->createArticleForUser(auth()->user());

            $this->putJson(route('contributors.articles.update', $article), [])
                ->assertUnprocessable();
        });

        it('returns 401 when unauthenticated', function () {
            $article = Article::factory()->create();

            $this->putJson(route('contributors.articles.update', $article), [])
                ->assertUnauthorized();
        });
    });

    describe('destroy', function () {
        it('deletes own article', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $article = $this->createArticleForUser($user);

            $this->deleteJson(route('contributors.articles.destroy', $article))
                ->assertNoContent();

            expect(Article::find($article->id))->toBeNull();
        });

        it('returns 403 when deleting another contributors article', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $article = $this->createArticleForUser($otherUser);

            $this->deleteJson(route('contributors.articles.destroy', $article))
                ->assertForbidden();
        });

        it('returns 401 when unauthenticated', function () {
            $article = Article::factory()->create();

            $this->deleteJson(route('contributors.articles.destroy', $article))
                ->assertUnauthorized();
        });
    });
});
