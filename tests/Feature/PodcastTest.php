<?php

use App\Enums\Status;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

describe('Podcast', function () {
    beforeEach(function () {
        Storage::fake('s3');
    });

    describe('index', function () {
        it('returns paginated podcasts', function () {
            Podcast::factory()->count(2)->active()->create();

            $this->getJson(route('podcasts.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('filters podcasts by search', function () {
            $podcast = Podcast::factory()->active()->create(['title' => 'Public Podcast Search']);
            Podcast::factory()->active()->create(['title' => 'Another Podcast']);

            $this->getJson(route('podcasts.index', ['search' => 'Public Podcast']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $podcast->id);
        });
    });

    describe('show', function () {
        it('shows a podcast with author', function () {
            $podcast = Podcast::factory()->active()->create();

            $this->getJson(route('podcasts.show', $podcast))
                ->assertOk()
                ->assertJsonPath('data.id', $podcast->id)
                ->assertJsonStructure(['data' => ['author']]);
        });

        it('returns 404 for unknown podcast', function () {
            $this->getJson(route('podcasts.show', Str::uuid()))
                ->assertNotFound();
        });
    });

    describe('episodes', function () {
        it('lists episodes for a podcast', function () {
            $podcast = Podcast::factory()->active()->create();
            Episode::factory()->count(2)->active()->create(['podcast_id' => $podcast->id]);

            $this->getJson(route('podcasts.episodes.index', $podcast))
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('shows an episode with a signed file url when present', function () {
            $filePath = test()->fakeReflectionFileOnStorage('podcasts/public-episode.mp3');
            $episode = Episode::factory()->active()->create(['file' => $filePath]);

            $this->getJson(route('episodes.show', $episode))
                ->assertOk()
                ->assertJsonPath('data.id', $episode->id)
                ->assertJsonPath('data.file', fn ($url) => is_string($url) && $url !== '');
        });

        it('returns 404 for unknown episode', function () {
            $this->getJson(route('episodes.show', Str::uuid()))
                ->assertNotFound();
        });
    });
});
