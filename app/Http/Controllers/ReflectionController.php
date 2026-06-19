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
