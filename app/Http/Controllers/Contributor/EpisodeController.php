<?php

namespace App\Http\Controllers\Contributor;

use App\Enums\Status;
use App\Http\Requests\EpisodeRequest;
use App\Http\Resources\EpisodeResource;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Contributor Episode Management
 */
class EpisodeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Episode::class)->only(['index']),
            new Middleware('can:store,'.Episode::class.',podcast')->only(['store']),
            new Middleware('can:show,episode')->only(['show']),
            new Middleware('can:update,episode')->only(['update']),
            new Middleware('can:destroy,episode')->only(['destroy']),
        ];
    }

    /**
     * List episodes for a podcast owned by the authenticated contributor.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     * @queryParam page integer The page number for pagination. Example: 1
     */
    public function index(Podcast $podcast): AnonymousResourceCollection
    {
        return EpisodeResource::collection($podcast->episodes()->paginate());
    }

    /**
     * Store a new episode for a podcast.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam title string required The episode title. Example: Episode 1 — Introduction
     * @bodyParam content string required The episode description or show notes. Example: In this episode we introduce the series.
     * @bodyParam episode_number integer optional The episode number within the podcast. Example: 1
     * @bodyParam file string optional Audio storage path (mp3, wav, m4a, ogg, aac, webm). Only validated when sent. Example: podcasts/episode-1.mp3
     */
    public function store(EpisodeRequest $request, Podcast $podcast): EpisodeResource|JsonResponse
    {
        $episode = new Episode();

        if ($error = $episode->validateAssets($request, $episode)) {
            return $error;
        }

        $episode->podcast_id = $podcast->id;
        $episode->title = $request->string('title')->trim();
        $episode->content = $request->string('content')->trim();
        $episode->file = $request->input('file');
        $episode->episode_number = $request->input('episode_number');
        $episode->status = Status::INACTIVE->value;
        $episode->save();

        /** @status 201 */
        return new EpisodeResource($episode);
    }

    /**
     * Get the specified episode.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Episode $episode): EpisodeResource
    {
        return new EpisodeResource($episode);
    }

    /**
     * Update the specified episode.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam title string required The episode title. Example: Updated Episode Title
     * @bodyParam content string required The episode description or show notes. Example: Updated episode notes.
     * @bodyParam episode_number integer optional The episode number within the podcast. Example: 2
     * @bodyParam file string optional Audio storage path (mp3, wav, m4a, ogg, aac, webm). Only validated when sent. Example: podcasts/episode-1.mp3
     */
    public function update(EpisodeRequest $request, Episode $episode): EpisodeResource|JsonResponse
    {
        if ($error = $episode->validateAssets($request, $episode)) {
            return $error;
        }

        $episode->title = $request->string('title')->trim();
        $episode->content = $request->string('content')->trim();
        $episode->file = $request->input('file', $episode->file);
        $episode->episode_number = $request->input('episode_number', $episode->episode_number);
        $episode->save();

        return new EpisodeResource($episode);
    }

    /**
     * Remove the specified episode.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function destroy(Episode $episode): JsonResponse
    {
        $episode->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
