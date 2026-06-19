<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReadingsResource;
use App\Services\Bible\Bible;
use App\Services\Readings\Readings;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * @tags Public Readings
 */
class ReadingsController extends Controller
{
    /**
     * Get daily readings with liturgical context and Bible passage text.
     *
     * Liturgical data is fetched from the configured readings provider. Passage text is fetched from the configured Bible provider.
     * `celebration` may be `null` when liturgical calendar data is unavailable. Memorial celebrations may also include an `image` field.
     * Any reading without a reference for the day is returned as `null` (commonly `second_reading` on weekdays).
     *
     * @urlParam date string required The date in `Y-m-d` format. Example: 2026-06-17
     */
    public function show(string $date): ReadingsResource
    {
        $parsedDate = $this->parseDate($date);

        $data = Cache::remember(
            'readings:'.$parsedDate->toDateString(),
            now()->diffInSeconds(now()->endOfDay()),
            fn () => $this->fetchReadings($parsedDate),
        );

        return new ReadingsResource($data);
    }

    protected function parseDate(?string $date): Carbon
    {
        if ($date === null) {
            return Carbon::today();
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);
        } catch (InvalidFormatException) {
            abort(422, 'Invalid date format. Expected Y-m-d.');
        }

        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            abort(422, 'Invalid date format. Expected Y-m-d.');
        }

        return $parsed;
    }

    protected function fetchReadings(Carbon $date): array
    {
        $readingsService = new Readings(config('services.readings.provider'));
        $bibleService = new Bible(config('services.bible.provider'));

        try {
            $liturgical = $readingsService->getDailyReadings($date);
        } catch (RuntimeException $exception) {
            abort(404, $exception->getMessage());
        }

        $celebration = null;

        try {
            $celebrationData = $readingsService->getCelebration($date);
            $celebration = $celebrationData['celebration'] ?? null;
        } catch (RuntimeException) {
            //
        }

        $readingReferences = $liturgical['readings'] ?? [];

        return [
            'date' => $liturgical['date'] ?? $date->toDateString(),
            'season' => $liturgical['season'] ?? null,
            'celebration' => $celebration,
            'readings' => [
                'first_reading' => $this->fetchPassage($bibleService, $readingReferences['firstReading'] ?? null),
                'psalm' => $this->fetchPassage($bibleService, $readingReferences['psalm'] ?? null),
                'second_reading' => $this->fetchPassage($bibleService, $readingReferences['secondReading'] ?? null),
                'gospel' => $this->fetchPassage($bibleService, $readingReferences['gospel'] ?? null),
            ],
        ];
    }

    protected function fetchPassage(Bible $bibleService, ?string $reference): ?array
    {
        if ($reference === null || $reference === '') {
            return null;
        }

        return $bibleService->getPassage($reference);
    }
}
