<?php

namespace App\Http\Controllers;

use App\Http\Resources\PodcastResource;
use App\Models\Podcast;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Public Podcast Management
 */
class PodcastController extends Controller
{
    /**
     * List podcasts.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam search string Optional. Filter by title. Example: Faith
     * @queryParam status integer Optional. Filter by status (`1` = Active, `2` = Inactive, `3` = Banned). Example: 1
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $podcasts = Podcast::with(['author:id,name,status'])
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->paginate();

        return PodcastResource::collection($podcasts);
    }

    /**
     * Get the specified podcast.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Podcast $podcast): PodcastResource
    {
        return new PodcastResource($podcast->loadMissing(['author']));
    }
}
