<?php

use App\Enums\Status;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('Podcast', function () {
    beforeEach(function () {
        Storage::fake('s3');
    });

    describe('index', function () {
        it('returns only own podcasts', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $ownPodcast = Podcast::factory()->create(['user_id' => $user->id]);
            ['user' => $otherUser] = $this->createContributorWithUser();
            Podcast::factory()->create(['user_id' => $otherUser->id]);

            $this->getJson(route('contributors.podcasts.index'))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $ownPodcast->id);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('contributors.podcasts.index'))->assertUnauthorized();
        });
    });

    describe('store', function () {
        it('creates a podcast for the authenticated contributor', function () {
            $this->actingAsContributor();

            $payload = [
                'title' => 'Test Podcast',
                'content' => 'Podcast description.',
            ];

            $this->postJson(route('contributors.podcasts.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.title', $payload['title']);

            $this->assertDatabaseHas('podcasts', [
                'title' => $payload['title'],
                'user_id' => auth()->id(),
                'status' => Status::INACTIVE->value,
            ]);
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsContributor();

            $this->postJson(route('contributors.podcasts.store'), [])
                ->assertUnprocessable();
        });

        it('returns 401 when unauthenticated', function () {
            $this->postJson(route('contributors.podcasts.store'), [])
                ->assertUnauthorized();
        });
    });

    describe('update', function () {
        it('updates own podcast', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $podcast = Podcast::factory()->create(['user_id' => $user->id]);

            $this->putJson(route('contributors.podcasts.update', $podcast), [
                'title' => 'Updated Podcast',
                'content' => 'Updated content.',
            ])->assertOk()
                ->assertJsonPath('data.title', 'Updated Podcast');
        });

        it('returns 403 when updating another contributors podcast', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $podcast = Podcast::factory()->create(['user_id' => $otherUser->id]);

            $this->putJson(route('contributors.podcasts.update', $podcast), [
                'title' => 'Updated Podcast',
                'content' => 'Updated content.',
            ])->assertForbidden();
        });
    });

    describe('destroy', function () {
        it('deletes own podcast without episodes', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $podcast = Podcast::factory()->create(['user_id' => $user->id]);

            $this->deleteJson(route('contributors.podcasts.destroy', $podcast))
                ->assertNoContent();

            expect(Podcast::find($podcast->id))->toBeNull();
        });

        it('returns 403 when podcast has episodes', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $podcast = Podcast::factory()->create(['user_id' => $user->id]);
            Episode::factory()->create(['podcast_id' => $podcast->id]);

            $this->deleteJson(route('contributors.podcasts.destroy', $podcast))
                ->assertForbidden();
        });

        it('returns 403 when deleting another contributors podcast', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $podcast = Podcast::factory()->create(['user_id' => $otherUser->id]);

            $this->deleteJson(route('contributors.podcasts.destroy', $podcast))
                ->assertForbidden();
        });
    });

    describe('episodes', function () {
        it('creates an episode for own podcast', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $podcast = Podcast::factory()->create(['user_id' => $user->id]);

            $payload = [
                'title' => 'Episode One',
                'content' => 'Episode notes.',
                'episode_number' => 1,
            ];

            $this->postJson(route('contributors.podcasts.episodes.store', $podcast), $payload)
                ->assertCreated()
                ->assertJsonPath('data.title', $payload['title']);

            $this->assertDatabaseHas('episodes', [
                'podcast_id' => $podcast->id,
                'title' => $payload['title'],
                'status' => Status::INACTIVE->value,
            ]);
        });

        it('returns 403 when creating an episode for another contributors podcast', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $podcast = Podcast::factory()->create(['user_id' => $otherUser->id]);

            $this->postJson(route('contributors.podcasts.episodes.store', $podcast), [
                'title' => 'Episode One',
                'content' => 'Episode notes.',
            ])->assertForbidden();
        });

        it('creates an episode with a valid audio file path', function () {
            $filePath = $this->fakeReflectionFileOnStorage('podcasts/episode.mp3');
            $this->actingAsContributor();
            $podcast = Podcast::factory()->create(['user_id' => auth()->id()]);

            $this->postJson(route('contributors.podcasts.episodes.store', $podcast), [
                'title' => 'Episode With Audio',
                'content' => 'Episode notes.',
                'file' => $filePath,
            ])->assertCreated()
                ->assertJsonPath('data.file', fn ($url) => is_string($url) && $url !== '');

            $this->assertDatabaseHas('episodes', [
                'title' => 'Episode With Audio',
                'file' => $filePath,
            ]);
        });

        it('returns 422 when audio file path does not exist on storage', function () {
            Storage::fake('s3');
            $this->actingAsContributor();
            $podcast = Podcast::factory()->create(['user_id' => auth()->id()]);

            $this->postJson(route('contributors.podcasts.episodes.store', $podcast), [
                'title' => 'Episode One',
                'content' => 'Episode notes.',
                'file' => 'podcasts/missing.mp3',
            ])->assertUnprocessable()
                ->assertJsonPath('errors.file.0', 'The selected audio file does not exist.');
        });

        it('updates own episode', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            $podcast = Podcast::factory()->create(['user_id' => $user->id]);
            $episode = Episode::factory()->create(['podcast_id' => $podcast->id]);

            $this->putJson(route('contributors.episodes.update', $episode), [
                'title' => 'Updated Episode',
                'content' => 'Updated notes.',
            ])->assertOk()
                ->assertJsonPath('data.title', 'Updated Episode');
        });

        it('returns 403 when updating another contributors episode', function () {
            ['user' => $user] = $this->createContributorWithUser();
            Sanctum::actingAs($user);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $podcast = Podcast::factory()->create(['user_id' => $otherUser->id]);
            $episode = Episode::factory()->create(['podcast_id' => $podcast->id]);

            $this->putJson(route('contributors.episodes.update', $episode), [
                'title' => 'Updated Episode',
                'content' => 'Updated notes.',
            ])->assertForbidden();
        });
    });
});
