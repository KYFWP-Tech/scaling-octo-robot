<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Resources\PodcastResource;
use App\Models\Podcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Admin Podcast Management
 */
class PodcastController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Podcast::class)->only(['index']),
            new Middleware('can:show,podcast')->only(['show']),
            new Middleware('can:update,podcast')->only(['update']),
            new Middleware('can:destroy,podcast')->only(['destroy']),
        ];
    }

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
     * Get the specified podcast for the admin.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function show(Podcast $podcast): PodcastResource
    {
        return new PodcastResource($podcast->loadMissing(['author']));
    }

    /**
     * Update the specified podcast status.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam status integer required The new status (`1` = Active, `2` = Inactive, `3` = Banned). Setting Active also sets `published_at`. Example: 1
     */
    public function update(ChangeStatusRequest $request, Podcast $podcast): PodcastResource
    {
        $podcast->status = $request->enum('status', Status::class, $podcast->status);
        if ($request->status == Status::ACTIVE->value) {
            $podcast->published_at = now();
        }
        $podcast->save();

        return new PodcastResource($podcast);
    }

    /**
     * Remove the specified podcast for the admin.
     *
     * @urlParam podcast string required The UUID of the podcast. Example: 123e4567-e89b-12d3-a456-426614174000
     */
    public function destroy(Podcast $podcast): JsonResponse
    {
        $podcast->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
