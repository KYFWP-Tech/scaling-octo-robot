<?php

namespace App\Http\Controllers;

use App\Http\Resources\EpisodeResource;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Public Episode Management
 */
class EpisodeController extends Controller
{
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
     * Get the specified episode.
     *
     * @urlParam episode string required The UUID of the episode. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Episode $episode): EpisodeResource
    {
        return new EpisodeResource($episode->loadMissing(['podcast']));
    }
}
