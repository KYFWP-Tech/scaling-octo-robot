<?php

namespace App\Http\Controllers\Contributor;

use App\Http\Requests\ReflectionRequest;
use App\Http\Resources\ReflectionResource;
use App\Models\Reflection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Contributor Reflection Management
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
     * Get all reflections for the authenticated contributor.
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
     *    "first": "http://localhost/api/contributors/reflections?page=1",
     *    "last": "http://localhost/api/contributors/reflections?page=1",
     *    "prev": null,
     *    "next": null
     *  },
     *  "meta": {
     *    "current_page": 1,
     *    "from": 1,
     *    "last_page": 1,
     *    "path": "http://localhost/api/contributors/reflections",
     *    "per_page": 15,
     *    "to": 1,
     *    "total": 1
     *  }
     * }
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     */
    public function index()
    {
        $reflections = Auth::user()
            ->reflections()
            ->with('author:id,name,email,status')
            ->orderByDesc('date')
            ->paginate();

        return ReflectionResource::collection($reflections);
    }

    /**
     * Store a newly created reflection.
     *
     * @bodyParam date string required The reflection date in `Y-m-d` format. Must be unique. Example: 2026-06-17
     * @bodyParam title string required The reflection title. Example: Daily Reflection
     * @bodyParam content string required The reflection body text. Example: Today we reflect on the gospel reading.
     *
     * @response 201 {
     *  "data": {
     *    "id": "123e4567-e89b-12d3-a456-426614174000",
     *    "date": "2026-06-17",
     *    "title": "Daily Reflection",
     *    "content": "Today we reflect on the gospel reading.",
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
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     * @response 422 {
     *  "message": "The date field is required. (and 2 more errors)",
     *  "errors": {
     *    "date": ["The date field is required."],
     *    "title": ["The title field is required."],
     *    "content": ["The content field is required."]
     *  }
     * }
     */
    public function store(ReflectionRequest $request)
    {
        $reflection = Reflection::create([
            'date' => $request->date,
            'title' => $request->string('title')->trim(),
            'content' => $request->string('content')->trim(),
            'author_id' => Auth::id(),
        ]);

        $this->forgetCache($reflection->date->toDateString());

        return new ReflectionResource($reflection->load('author'));
    }

    /**
     * Get the specified reflection.
     *
     * @urlParam reflection string required The UUID of the reflection. Example: 123e4567-e89b-12d3-a456-426614174000
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
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     * @response 403 {
     *  "message": "This action is unauthorized."
     * }
     */
    public function show(Reflection $reflection)
    {
        return new ReflectionResource($reflection->loadMissing('author'));
    }

    /**
     * Update the specified reflection.
     *
     * @urlParam reflection string required The UUID of the reflection. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam date string required The reflection date in `Y-m-d` format. Must be unique except for this reflection. Example: 2026-06-17
     * @bodyParam title string required The reflection title. Example: Updated Reflection
     * @bodyParam content string required The reflection body text. Example: Updated reflection content.
     *
     * @response 200 {
     *  "data": {
     *    "id": "123e4567-e89b-12d3-a456-426614174000",
     *    "date": "2026-06-17",
     *    "title": "Updated Reflection",
     *    "content": "Updated reflection content.",
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
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     * @response 403 {
     *  "message": "This action is unauthorized."
     * }
     * @response 422 {
     *  "message": "The title field is required.",
     *  "errors": {
     *    "title": ["The title field is required."]
     *  }
     * }
     */
    public function update(ReflectionRequest $request, Reflection $reflection)
    {
        $previousDate = $reflection->date->toDateString();

        $reflection->update([
            'date' => $request->date,
            'title' => $request->string('title')->trim(),
            'content' => $request->string('content')->trim(),
        ]);

        $this->forgetCache($previousDate);
        $this->forgetCache($reflection->date->toDateString());

        return new ReflectionResource($reflection->load('author'));
    }

    /**
     * Remove the specified reflection.
     *
     * @urlParam reflection string required The UUID of the reflection. Example: 123e4567-e89b-12d3-a456-426614174000
     *
     * @response 204 {}
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     * @response 403 {
     *  "message": "This action is unauthorized."
     * }
     */
    public function destroy(Reflection $reflection)
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
