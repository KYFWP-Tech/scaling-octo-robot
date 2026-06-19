<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReadingsResource;
use App\Services\Bible\Bible;
use App\Services\Readings\Readings;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
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
     *
     * @response 200 {
     *  "data": {
     *    "date": "2026-06-17",
     *    "season": "Ordinary Time",
     *    "celebration": {
     *      "name": "Wednesday of the 11th week of Ordinary Time",
     *      "type": "FERIA",
     *      "quote": "",
     *      "description": ""
     *    },
     *    "readings": {
     *      "first_reading": {
     *        "reference": "2 Kings 2:1,6-14",
     *        "text": "When Yahweh was about to take Elijah up by a whirlwind into heaven, Elijah went with Elisha from Gilgal.\n",
     *        "verses": [
     *          {
     *            "book_id": "2KI",
     *            "book_name": "2 Kings",
     *            "chapter": 2,
     *            "verse": 1,
     *            "text": "When Yahweh was about to take Elijah up by a whirlwind into heaven, Elijah went with Elisha from Gilgal.\n"
     *          }
     *        ]
     *      },
     *      "psalm": {
     *        "reference": "Psalms 31:20,21,24",
     *        "text": "In the shelter of your presence you will hide them from the plotting of man.\n",
     *        "verses": [
     *          {
     *            "book_id": "PSA",
     *            "book_name": "Psalms",
     *            "chapter": 31,
     *            "verse": 20,
     *            "text": "In the shelter of your presence you will hide them from the plotting of man.\n"
     *          }
     *        ]
     *      },
     *      "second_reading": null,
     *      "gospel": {
     *        "reference": "Matthew 6:1-6,16-18",
     *        "text": "Be careful that you don't do your charitable giving before men, to be seen by them, or else you have no reward from your Father who is in heaven.\n",
     *        "verses": [
     *          {
     *            "book_id": "MAT",
     *            "book_name": "Matthew",
     *            "chapter": 6,
     *            "verse": 1,
     *            "text": "Be careful that you don't do your charitable giving before men, to be seen by them, or else you have no reward from your Father who is in heaven.\n"
     *          }
     *        ]
     *      }
     *    }
     *  }
     * }
     * @response 422 {
     *  "message": "Invalid date format. Expected Y-m-d."
     * }
     * @response 404 {
     *  "message": "Readings data not found for date [2020-01-01]."
     * }
     */
    public function show(string $date): JsonResponse|ReadingsResource
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
