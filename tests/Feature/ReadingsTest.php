<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

describe('Readings', function () {
    beforeEach(function () {
        Cache::flush();
    });

    describe('show', function () {
        it('returns combined readings for a valid date', function () {
            $this->fakeReadingsApis('2026-06-17');

            $response = $this->getJson(route('readings.show', '2026-06-17'));

            $response->assertOk()
                ->assertJsonPath('data.date', '2026-06-17')
                ->assertJsonPath('data.season', 'Ordinary Time')
                ->assertJsonPath('data.readings.first_reading.text', 'Test passage text.')
                ->assertJsonPath('data.celebration.name', 'Test Celebration');

            expect($response->json('data'))->not->toHaveKey('usccb_link');
        });

        it('returns 422 for an invalid date format', function () {
            $this->getJson(route('readings.show', 'bad-date'))
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Invalid date format. Expected Y-m-d.');
        });

        it('returns 404 when cpbjr readings are not found', function () {
            $readingsBase = rtrim(config('services.readings.base_url'), '/');

            Http::fake([
                "{$readingsBase}/readings/2020/01-01.json" => Http::response([], 404),
            ]);

            $this->getJson(route('readings.show', '2020-01-01'))
                ->assertNotFound()
                ->assertJsonPath('message', 'Readings data not found for date [2020-01-01].');
        });

        it('returns null celebration when liturgical calendar is unavailable', function () {
            $this->fakeReadingsApis('2026-06-17', includeCelebration: false);

            $this->getJson(route('readings.show', '2026-06-17'))
                ->assertOk()
                ->assertJsonPath('data.celebration', null);
        });

        it('returns null second reading when absent from liturgical data', function () {
            $this->fakeReadingsApis('2026-06-17');

            $this->getJson(route('readings.show', '2026-06-17'))
                ->assertOk()
                ->assertJsonPath('data.readings.second_reading', null);
        });

        it('includes second reading when present in liturgical data', function () {
            $this->fakeReadingsApis('2026-06-17', includeSecondReading: true);

            $this->getJson(route('readings.show', '2026-06-17'))
                ->assertOk()
                ->assertJsonPath('data.readings.second_reading.text', 'Test passage text.');
        });

        it('caches the response for the same date', function () {
            $this->fakeReadingsApis('2026-06-17');

            $this->getJson(route('readings.show', '2026-06-17'));
            $recordedAfterFirst = count(Http::recorded());

            $this->getJson(route('readings.show', '2026-06-17'));
            $recordedAfterSecond = count(Http::recorded());

            expect($recordedAfterSecond)->toBe($recordedAfterFirst);
        });
    });
});
