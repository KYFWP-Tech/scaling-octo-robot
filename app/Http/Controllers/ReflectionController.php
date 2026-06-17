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
     *        "email": "john@example.com"
     *      },
     *      "created_at": "2026-01-01 00:00:00",
     *      "updated_at": "2026-01-01 00:00:00"
     *    }
     *  ]
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
     * @response 200 {
     *  "data": {
     *    "id": "123e4567-e89b-12d3-a456-426614174000",
     *    "date": "2026-06-17",
     *    "title": "Reflection title",
     *    "content": "Reflection content",
     *    "author": {
     *      "id": "123e4567-e89b-12d3-a456-426614174001",
     *      "name": "John Doe",
     *      "email": "john@example.com"
     *    },
     *    "created_at": "2026-01-01 00:00:00",
     *    "updated_at": "2026-01-01 00:00:00"
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
