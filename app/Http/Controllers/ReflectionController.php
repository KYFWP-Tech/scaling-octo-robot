<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReflectionResource;
use App\Models\Reflection;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Public Reflections
 */
class ReflectionController extends Controller
{
    /**
     * List reflections.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     *
     * @response 200 {
     *  "data": [
     *    {
     *      "id": "123e4567-e89b-12d3-a456-426614174000",
     *      "date": "2026-06-17",
     *      "title": "Reflection title",
     *      "content": "Reflection content",
     *      "author": {
     *        "id": "123e4567-e89b-12d3-a456-426614174001",
     *        "name": "John Doe",
     *        "email": "john@example.com",
     *        "status": {
     *          "value": 1,
     *          "label": "Active"
     *        },
     *        "created_at": "2026-01-01T00:00:00.000000Z",
     *        "updated_at": "2026-01-01T00:00:00.000000Z"
     *      },
     *      "created_at": "2026-01-01T00:00:00.000000Z",
     *      "updated_at": "2026-01-01T00:00:00.000000Z"
     *    }
     *  ],
     *  "links": {
     *    "first": "http://localhost/api/reflections?page=1",
     *    "last": "http://localhost/api/reflections?page=1",
     *    "prev": null,
     *    "next": null
     *  },
     *  "meta": {
     *    "current_page": 1,
     *    "from": 1,
     *    "last_page": 1,
     *    "path": "http://localhost/api/reflections",
     *    "per_page": 15,
     *    "to": 1,
     *    "total": 1
     *  }
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $reflections = Reflection::with('author:id,name,email,status')
            ->orderByDesc('date')
            ->paginate();

        return ReflectionResource::collection($reflections);
    }

    /**
     * Get reflection for a specific date.
     *
     * @urlParam date string required The date in `Y-m-d` format. Example: 2026-06-17
     *
     * @response 200 {
     *  "data": {
     *    "id": "123e4567-e89b-12d3-a456-426614174000",
     *    "date": "2026-06-17",
     *    "title": "Reflection title",
     *    "content": "Reflection content",
     *    "author": {
     *      "id": "123e4567-e89b-12d3-a456-426614174001",
     *      "name": "John Doe",
     *      "email": "john@example.com",
     *      "status": {
     *        "value": 1,
     *        "label": "Active"
     *      },
     *      "created_at": "2026-01-01T00:00:00.000000Z",
     *      "updated_at": "2026-01-01T00:00:00.000000Z"
     *    },
     *    "created_at": "2026-01-01T00:00:00.000000Z",
     *    "updated_at": "2026-01-01T00:00:00.000000Z"
     *  }
     * }
     * @response 422 {
     *  "message": "Invalid date format. Expected Y-m-d."
     * }
     * @response 404 {
     *  "message": "No query results for model [App\\Models\\Reflection]."
     * }
     */
    public function show(string $date): ReflectionResource
    {
        $parsedDate = $this->parseDate($date);

        $reflection = Cache::remember(
            'reflections:'.$parsedDate->toDateString(),
            now()->diffInSeconds(now()->endOfDay()),
            fn () => Reflection::with('author:id,name,email,status')
                ->whereDate('date', $parsedDate)
                ->firstOrFail(),
        );

        return new ReflectionResource($reflection);
    }

    protected function parseDate(string $date): Carbon
    {
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
}
