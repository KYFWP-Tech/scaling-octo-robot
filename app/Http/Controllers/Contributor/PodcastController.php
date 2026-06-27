<?php

namespace App\Http\Controllers\Contributor;

use App\Enums\Status;
use App\Http\Requests\PodcastRequest;
use App\Http\Resources\PodcastResource;
use App\Models\Podcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Contributor Podcast Management
 */
class PodcastController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Podcast::class)->only(['index']),
            new Middleware('can:store,'.Podcast::class)->only(['store']),
            new Middleware('can:show,podcast')->only(['show']),
            new Middleware('can:update,podcast')->only(['update']),
            new Middleware('can:destroy,podcast')->only(['destroy']),
        ];
    }

    /**
     * Get all podcasts for the authenticated contributor.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     */
    public function index(): AnonymousResourceCollection
    {
        $podcasts = Auth::user()->podcasts()->paginate();

        return PodcastResource::collection($podcasts);
    }

    /**
     * Store a newly created podcast for the contributor.
     *
     * @bodyParam title string required The podcast title. Example: Weekly Gospel Podcast
     * @bodyParam content string required The podcast description or show notes. Example: A weekly discussion of the Sunday gospel readings.
     * @bodyParam cover_image string optional Cover image storage path under `podcasts/`. Only validated when sent. Example: podcasts/cover.jpg
     */
    public function store(PodcastRequest $request): PodcastResource|JsonResponse
    {
        $podcast = new Podcast();

        if ($error = $podcast->validateAssets($request, $podcast)) {
            return $error;
        }

        $podcast->title = $request->string('title')->trim();
        $podcast->content = $request->string('content')->trim();
        $podcast->cover_image = $request->input('cover_image');
        $podcast->status = Status::INACTIVE->value;
        $podcast->user_id = Auth::id();
        $podcast->save();

        /** @status 201 */
        return new PodcastResource($podcast);
    }

    /**
     * Get the specified podcast for the contributor.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Podcast $podcast): PodcastResource
    {
        return new PodcastResource($podcast);
    }

    /**
     * Update the specified podcast for the contributor.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam title string required The podcast title. Example: Updated Podcast Title
     * @bodyParam content string required The podcast description or show notes. Example: Updated show notes.
     * @bodyParam cover_image string optional Cover image storage path under `podcasts/`. Only validated when sent. Example: podcasts/cover.jpg
     */
    public function update(PodcastRequest $request, Podcast $podcast): PodcastResource|JsonResponse
    {
        if ($error = $podcast->validateAssets($request, $podcast)) {
            return $error;
        }

        $podcast->title = $request->string('title')->trim();
        $podcast->content = $request->string('content')->trim();
        $podcast->cover_image = $request->input('cover_image', $podcast->cover_image);
        $podcast->save();

        return new PodcastResource($podcast);
    }

    /**
     * Remove the specified podcast for the contributor.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function destroy(Podcast $podcast): JsonResponse
    {
        $podcast->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
