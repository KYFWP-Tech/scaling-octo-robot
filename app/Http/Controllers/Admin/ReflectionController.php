<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ReflectionRequest;
use App\Http\Resources\ReflectionResource;
use App\Models\Reflection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Admin Reflection Management
 */
class ReflectionController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Reflection::class)->only(['index']),
            new Middleware('can:show,reflection')->only(['show']),
            new Middleware('can:store,'.Reflection::class)->only(['store']),
            new Middleware('can:update,reflection')->only(['update']),
            new Middleware('can:destroy,reflection')->only(['destroy']),
        ];
    }

    /**
     * List reflections.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam search string Optional. Filter by title. Example: Daily
     * @queryParam date string Optional. Filter by date in `Y-m-d` format. Example: 2026-06-17
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reflections = Reflection::with('author:id,name,email,status')
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->when($request->has('date'), function ($query) use ($request) {
                $query->whereDate('date', $request->date);
            })
            ->orderByDesc('date')
            ->paginate();

        return ReflectionResource::collection($reflections);
    }

    /**
     * Get the specified reflection.
     *
     * @urlParam reflection string required The UUID of the reflection. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Reflection $reflection): ReflectionResource
    {
        return new ReflectionResource($reflection->loadMissing('author'));
    }

    /**
     * Store a newly created reflection.
     *
     * @bodyParam date string required The reflection date in `Y-m-d` format. Must be unique. Example: 2026-06-17
     * @bodyParam title string required The reflection title. Example: Daily Reflection
     * @bodyParam content string required The reflection body text. Example: Today we reflect on the gospel reading.
     * @bodyParam file string optional Audio storage path (mp3, wav, m4a, ogg, aac, webm). Only validated when sent. Example: reflections/audio.mp3
     */
    public function store(ReflectionRequest $request): ReflectionResource|JsonResponse
    {
        $reflection = new Reflection();

        if ($error = $reflection->validateAssets($request, $reflection)) {
            return $error;
        }

        $reflection->date = $request->date;
        $reflection->title = $request->string('title')->trim();
        $reflection->content = $request->string('content')->trim();
        $reflection->author_id = Auth::id();
        $reflection->file = $request->input('file');

        $reflection->save();

        $this->forgetCache($reflection->date->toDateString());

        /** @status 201 */
        return new ReflectionResource($reflection->load('author'));
    }

    /**
     * Update the specified reflection.
     *
     * @urlParam reflection string required The UUID of the reflection. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam date string required The reflection date in `Y-m-d` format. Must be unique except for this reflection. Example: 2026-06-17
     * @bodyParam title string required The reflection title. Example: Updated Reflection
     * @bodyParam content string required The reflection body text. Example: Updated reflection content.
     * @bodyParam file string optional Audio storage path (mp3, wav, m4a, ogg, aac, webm). Only validated when sent. Example: reflections/audio.mp3
     */
    public function update(ReflectionRequest $request, Reflection $reflection): ReflectionResource|JsonResponse
    {
        if ($error = $reflection->validateAssets($request, $reflection)) {
            return $error;
        }

        $previousDate = $reflection->date->toDateString();

        $reflection->date = $request->date;
        $reflection->title = $request->string('title')->trim();
        $reflection->content = $request->string('content')->trim();
        $reflection->file = $request->input('file', $reflection->file);
        $reflection->save();

        $this->forgetCache($previousDate);
        $this->forgetCache($reflection->date->toDateString());

        return new ReflectionResource($reflection->load('author'));
    }

    /**
     * Remove the specified reflection.
     *
     * @urlParam reflection string required The UUID of the reflection. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function destroy(Reflection $reflection): JsonResponse
    {
        $date = $reflection->date->toDateString();

        $reflection->delete();

        $this->forgetCache($date);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function forgetCache(string $date): void
    {
        Cache::forget('reflections:'.$date);
    }
}
