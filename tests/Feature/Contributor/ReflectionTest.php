<?php

use App\Models\Reflection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('Reflection', function () {
    beforeEach(function () {
        Storage::fake('s3');
        Cache::flush();
    });

    describe('index', function () {
        it('returns only own reflections', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $ownReflection = $this->createReflectionForUser($user, ['date' => '2026-06-17']);
            ['user' => $otherUser] = $this->createContributorWithUser();
            $this->createReflectionForUser($otherUser, ['date' => '2026-06-18']);

            $this->getJson(route('contributors.reflections.index'))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $ownReflection->id);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('contributors.reflections.index'))->assertUnauthorized();
        });
    });

    describe('store', function () {
        it('creates a reflection for the authenticated contributor', function () {
            $this->actingAsContributor();
            $payload = $this->validReflectionPayload();

            $this->postJson(route('contributors.reflections.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.title', $payload['title']);

            $this->assertDatabaseHas('reflections', [
                'title' => $payload['title'],
                'date' => $payload['date'],
                'author_id' => auth()->id(),
            ]);
        });

        it('clears the public cache for the reflection date', function () {
            Cache::put('reflections:2026-06-17', 'stale');

            $this->actingAsContributor();
            $this->postJson(route('contributors.reflections.store'), $this->validReflectionPayload())
                ->assertCreated();

            expect(Cache::has('reflections:2026-06-17'))->toBeFalse();
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsContributor();

            $this->postJson(route('contributors.reflections.store'), [])
                ->assertUnprocessable();
        });

        it('returns 422 for duplicate date', function () {
            $this->actingAsContributor();
            $payload = $this->validReflectionPayload();

            $this->postJson(route('contributors.reflections.store'), $payload)->assertCreated();

            $this->postJson(route('contributors.reflections.store'), $payload)
                ->assertUnprocessable();
        });

        it('returns 401 when unauthenticated', function () {
            $this->postJson(route('contributors.reflections.store'), [])
                ->assertUnauthorized();
        });

        it('creates a reflection with a valid audio file path', function () {
            $filePath = $this->fakeReflectionFileOnStorage();
            $this->actingAsContributor();
            $payload = $this->validReflectionPayload(['file' => $filePath]);

            $this->postJson(route('contributors.reflections.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.file', fn ($url) => is_string($url) && $url !== '');

            $this->assertDatabaseHas('reflections', [
                'title' => $payload['title'],
                'file' => $filePath,
            ]);
        });

        it('returns 422 when audio file path does not exist on storage', function () {
            Storage::fake('s3');
            $this->actingAsContributor();

            $this->postJson(route('contributors.reflections.store'), $this->validReflectionPayload([
                'file' => 'reflections/missing.mp3',
            ]))
                ->assertUnprocessable()
                ->assertJsonPath('errors.file.0', 'The selected audio file does not exist.');
        });
    });

    describe('show', function () {
        it('shows own reflection', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user);

            $this->getJson(route('contributors.reflections.show', $reflection))
                ->assertOk()
                ->assertJsonPath('data.id', $reflection->id);
        });

        it('returns 403 when viewing another contributors reflection', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $reflection = $this->createReflectionForUser($otherUser);

            $this->getJson(route('contributors.reflections.show', $reflection))
                ->assertForbidden();
        });

        it('returns 401 when unauthenticated', function () {
            $reflection = Reflection::factory()->create();

            $this->getJson(route('contributors.reflections.show', $reflection))
                ->assertUnauthorized();
        });
    });

    describe('update', function () {
        it('updates own reflection', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user, ['date' => '2026-06-17']);

            $this->putJson(route('contributors.reflections.update', $reflection), array_merge(
                $this->validReflectionPayload(['date' => '2026-06-17']),
                ['title' => 'Updated Title'],
            ))->assertOk()
                ->assertJsonPath('data.title', 'Updated Title');
        });

        it('clears public cache for old and new dates when date changes', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user, ['date' => '2026-06-01']);
            Cache::put('reflections:2026-06-01', 'stale');
            Cache::put('reflections:2026-06-17', 'stale');

            $this->putJson(route('contributors.reflections.update', $reflection), $this->validReflectionPayload([
                'date' => '2026-06-17',
            ]))->assertOk();

            expect(Cache::has('reflections:2026-06-01'))->toBeFalse();
            expect(Cache::has('reflections:2026-06-17'))->toBeFalse();
        });

        it('returns 403 when updating another contributors reflection', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $reflection = $this->createReflectionForUser($otherUser);

            $this->putJson(route('contributors.reflections.update', $reflection), $this->validReflectionPayload())
                ->assertForbidden();
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsContributor();
            $reflection = $this->createReflectionForUser(auth()->user());

            $this->putJson(route('contributors.reflections.update', $reflection), [])
                ->assertUnprocessable();
        });

        it('returns 401 when unauthenticated', function () {
            $reflection = Reflection::factory()->create();

            $this->putJson(route('contributors.reflections.update', $reflection), [])
                ->assertUnauthorized();
        });
    });

    describe('destroy', function () {
        it('deletes own reflection', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user);

            $this->deleteJson(route('contributors.reflections.destroy', $reflection))
                ->assertNoContent();

            expect(Reflection::find($reflection->id))->toBeNull();
        });

        it('clears the public cache for the reflection date', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user, ['date' => '2026-06-17']);
            Cache::put('reflections:2026-06-17', 'stale');

            $this->deleteJson(route('contributors.reflections.destroy', $reflection))
                ->assertNoContent();

            expect(Cache::has('reflections:2026-06-17'))->toBeFalse();
        });

        it('returns 403 when deleting another contributors reflection', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $reflection = $this->createReflectionForUser($otherUser);

            $this->deleteJson(route('contributors.reflections.destroy', $reflection))
                ->assertForbidden();
        });

        it('returns 401 when unauthenticated', function () {
            $reflection = Reflection::factory()->create();

            $this->deleteJson(route('contributors.reflections.destroy', $reflection))
                ->assertUnauthorized();
        });
    });
});
