<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Resources\EpisodeResource;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Admin Episode Management
 */
class EpisodeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Episode::class)->only(['index']),
            new Middleware('can:show,episode')->only(['show']),
            new Middleware('can:update,episode')->only(['update']),
            new Middleware('can:destroy,episode')->only(['destroy']),
        ];
    }

    /**
     * List episodes for a podcast.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam search string Optional. Filter by title. Example: Episode One
     * @queryParam status integer Optional. Filter by status (`1` = Active, `2` = Inactive, `3` = Banned). Example: 1
     */
    public function index(Request $request, Podcast $podcast): AnonymousResourceCollection
    {
        $episodes = $podcast->episodes()
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->paginate();

        return EpisodeResource::collection($episodes);
    }

    /**
     * Get the specified episode for the admin.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Episode $episode): EpisodeResource
    {
        return new EpisodeResource($episode->loadMissing(['podcast']));
    }

    /**
     * Update the specified episode status.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam status integer required The new status (`1` = Active, `2` = Inactive, `3` = Banned). Setting Active also sets `published_at`. Example: 1
     */
    public function update(ChangeStatusRequest $request, Episode $episode): EpisodeResource
    {
        $episode->status = $request->enum('status', Status::class, $episode->status);
        if ($request->status == Status::ACTIVE->value) {
            $episode->published_at = now();
        }
        $episode->save();

        return new EpisodeResource($episode);
    }

    /**
     * Remove the specified episode for the admin.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function destroy(Episode $episode): JsonResponse
    {
        $episode->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
