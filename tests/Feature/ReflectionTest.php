<?php

use App\Models\Reflection;
use Illuminate\Support\Facades\Cache;

describe('Reflection', function () {
    beforeEach(function () {
        Cache::flush();
    });

    describe('index', function () {
        it('returns paginated reflections', function () {
            Reflection::factory()->count(2)->create();

            $this->getJson(route('reflections.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta'])
                ->assertJsonCount(2, 'data');
        });

        it('does not require authentication', function () {
            Reflection::factory()->create();

            $this->getJson(route('reflections.index'))->assertOk();
        });
    });

    describe('show', function () {
        it('returns a reflection by date with author', function () {
            $reflection = Reflection::factory()->create([
                'date' => '2026-06-17',
                'title' => 'Public Reflection Title',
            ]);

            $this->getJson(route('reflections.show', '2026-06-17'))
                ->assertOk()
                ->assertJsonPath('data.id', $reflection->id)
                ->assertJsonPath('data.date', '2026-06-17')
                ->assertJsonPath('data.title', 'Public Reflection Title')
                ->assertJsonStructure(['data' => ['author']]);
        });

        it('returns 422 for an invalid date format', function () {
            $this->getJson(route('reflections.show', 'bad-date'))
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Invalid date format. Expected Y-m-d.');
        });

        it('returns 404 when no reflection exists for the date', function () {
            $this->getJson(route('reflections.show', '2099-01-01'))
                ->assertNotFound();
        });

        it('caches the reflection by date', function () {
            Reflection::factory()->create(['date' => '2026-06-17']);

            $this->getJson(route('reflections.show', '2026-06-17'));

            expect(Cache::has('reflections:2026-06-17'))->toBeTrue();
        });

        it('does not require authentication', function () {
            Reflection::factory()->create(['date' => '2026-06-17']);

            $this->getJson(route('reflections.show', '2026-06-17'))->assertOk();
        });
    });
});
