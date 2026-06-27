<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Podcast', function () {
    describe('index', function () {
        it('returns paginated podcasts for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            Podcast::factory()->count(2)->create();

            $this->getJson(route('admins.podcasts.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('returns paginated podcasts for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.podcasts.index'))->assertOk();
        });

        it('filters podcasts by search', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $podcast = Podcast::factory()->create(['title' => 'Unique Podcast Title']);
            Podcast::factory()->create(['title' => 'Other Podcast']);

            $this->getJson(route('admins.podcasts.index', ['search' => 'Unique Podcast']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $podcast->id);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.podcasts.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows a podcast with author', function () {
            $this->actingAsAdmin(Role::Admin);

            $podcast = Podcast::factory()->create();

            $this->getJson(route('admins.podcasts.show', $podcast))
                ->assertOk()
                ->assertJsonPath('data.id', $podcast->id)
                ->assertJsonStructure(['data' => ['author']]);
        });

        it('returns 404 for unknown podcast', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->getJson(route('admins.podcasts.show', Str::uuid()))
                ->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates podcast status to active and sets published_at', function () {
            $this->actingAsAdmin(Role::Admin);

            $podcast = Podcast::factory()->create(['status' => Status::INACTIVE, 'published_at' => null]);

            $this->putJson(route('admins.podcasts.update', $podcast), [
                'status' => Status::ACTIVE->value,
            ])->assertOk()
                ->assertJsonPath('data.status.value', Status::ACTIVE->value);

            expect($podcast->fresh()->published_at)->not->toBeNull();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $podcast = Podcast::factory()->create();

            $this->putJson(route('admins.podcasts.update', $podcast), [
                'status' => Status::ACTIVE->value,
            ])->assertForbidden();
        });

        it('returns 422 for invalid status', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $podcast = Podcast::factory()->create();

            $this->putJson(route('admins.podcasts.update', $podcast), [
                'status' => 99,
            ])->assertUnprocessable();
        });
    });

    describe('destroy', function () {
        it('deletes a podcast for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $podcast = Podcast::factory()->create();

            $this->deleteJson(route('admins.podcasts.destroy', $podcast))
                ->assertNoContent();

            expect(Podcast::find($podcast->id))->toBeNull();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $podcast = Podcast::factory()->create();

            $this->deleteJson(route('admins.podcasts.destroy', $podcast))
                ->assertForbidden();
        });
    });

    it('does not allow creating podcasts via POST', function () {
        $this->actingAsAdmin(Role::SuperAdmin);

        $this->postJson(route('admins.podcasts.index'), [
            'title' => 'New Podcast',
        ])->assertMethodNotAllowed();
    });

    describe('episodes', function () {
        it('lists episodes for a podcast', function () {
            $this->actingAsAdmin(Role::Admin);

            $podcast = Podcast::factory()->create();
            Episode::factory()->count(2)->create(['podcast_id' => $podcast->id]);

            $this->getJson(route('admins.podcasts.episodes.index', $podcast))
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('updates episode status to active and sets published_at', function () {
            $this->actingAsAdmin(Role::Admin);

            $episode = Episode::factory()->create(['status' => Status::INACTIVE, 'published_at' => null]);

            $this->putJson(route('admins.episodes.update', $episode), [
                'status' => Status::ACTIVE->value,
            ])->assertOk()
                ->assertJsonPath('data.status.value', Status::ACTIVE->value);

            expect($episode->fresh()->published_at)->not->toBeNull();
        });

        it('returns 403 for editor updating episode status', function () {
            $this->actingAsAdmin(Role::Editor);

            $episode = Episode::factory()->create();

            $this->putJson(route('admins.episodes.update', $episode), [
                'status' => Status::ACTIVE->value,
            ])->assertForbidden();
        });

        it('deletes an episode for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $episode = Episode::factory()->create();

            $this->deleteJson(route('admins.episodes.destroy', $episode))
                ->assertNoContent();

            expect(Episode::find($episode->id))->toBeNull();
        });

        it('returns 403 for editor deleting an episode', function () {
            $this->actingAsAdmin(Role::Editor);

            $episode = Episode::factory()->create();

            $this->deleteJson(route('admins.episodes.destroy', $episode))
                ->assertForbidden();
        });
    });
});
