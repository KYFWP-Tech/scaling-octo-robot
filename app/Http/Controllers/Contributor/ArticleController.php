<?php

namespace App\Http\Controllers\Contributor;

use App\Enums\Status;
use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ArticleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:update,article')->only(['update']),
            new Middleware('can:destroy,article')->only(['destroy']),
        ];
    }
    /**
     * Get all articles for the contributor.
     *
     * @return \Illuminate\Http\JsonResponse
     * @response 200 {
     *  "data": [
     *    {
     *      "id": "123e4567-e89b-12d3-a456-426614174000",
     *      "title": "Article 1",
     *      "slug": "article-1",
     *      "content": "Article 1 content",
     *      "cover_image": "https://example.com/cover-image.jpg",
     *      "media": "https://example.com/media.mp4",
     *      "category": {
     *        "id": "123e4567-e89b-12d3-a456-426614174000",
     *        "name": "Category 1",
     *        "description": "Category 1 description",
     *        "icon": "https://example.com/icon.svg",
     *        "status": [
     *          "value": "active",
     *          "label": "Active"
     *        ]
     *      },
     *      "is_featured": true,
     *      "published_at": "2026-01-01 00:00:00",
     *      "created_at": "2026-01-01 00:00:00",
     *      "updated_at": "2026-01-01 00:00:00"
     *    }
     *  ]
     *  "links": {
     *    "first": "http://localhost/api/articles?page=1",
     *    "last": "http://localhost/api/articles?page=1",
     *    "prev": null,
     *    "next": null
     *  },
     *  "meta": {
     *    "current_page": 1,
     *    "from": 1,
     *    "last_page": 1,
     *    "path": "http://localhost/api/articles",
     *    "per_page": 10,
     *    "to": 10,
     *    "total": 10
     *  }
     * }
     */
    public function index()
    {
        $articles = Auth::user()->articles()->with('category')->paginate();

        return ArticleResource::collection($articles);
    }

    /**
     * Store a newly created article for the contributor.
     *
     * @param ArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @response 201 {
     *  "data": {
     *    "id": "123e4567-e89b-12d3-a456-426614174000",
     *    "title": "Article 1",
     *    "slug": "article-1",
     *    "content": "Article 1 content",
     *    "cover_image": "https://example.com/cover-image.jpg",
     *    "media": "https://example.com/media.mp4",
     *    "category": {
     *      "id": "123e4567-e89b-12d3-a456-426614174000",
     *      "name": "Category 1",
     *      "description": "Category 1 description",
     *      "icon": "https://example.com/icon.svg",
     *      "status": [
     *        "value": "active",
     *        "label": "Active"
     *      ]
     *    },
     *    "is_featured": true,
     *    "published_at": "2026-01-01 00:00:00",
     *    "created_at": "2026-01-01 00:00:00",
     *    "updated_at": "2026-01-01 00:00:00"
     *  }
     * }
     */
    public function store(ArticleRequest $request)
    {
        $article = new Article();
        $article->title = $request->string('title')->trim();
        $article->content = $request->string('content')->trim();
        $article->cover_image = $request->string('cover_image');
        $article->media = $request->string('media');
        $article->category_id = $request->category_id;
        $article->is_featured = $request->boolean('is_featured');
        $article->status = Status::INACTIVE->value;
        $article->author_id = Auth::user()->id;
        $article->author_type = Auth::user()->getMorphClass();
        $article->save();

        return new ArticleResource($article);
    }

    /**
         * Get the specified article for the contributor.
         *
         * @param Article $article
         * @return \Illuminate\Http\JsonResponse
         * @response 200 {
         *  "data": {
         *    "id": "123e4567-e89b-12d3-a456-426614174000",
         *    "title": "Article 1",
         *    "slug": "article-1",
         *    "content": "Article 1 content",
         *    "cover_image": "https://example.com/cover-image.jpg",
         *    "media": "https://example.com/media.mp4",
         *    "category": {
         *      "id": "123e4567-e89b-12d3-a456-426614174000",
         *      "name": "Category 1",
         *      "description": "Category 1 description",
         *      "icon": "https://example.com/icon.svg",
         *      "status": [
         *        "value": "active",
         *        "label": "Active"
         *      ]
         *    },
         *    "is_featured": true,
         *    "published_at": "2026-01-01 00:00:00",
         *    "created_at": "2026-01-01 00:00:00",
         *    "updated_at": "2026-01-01 00:00:00"
         *  }
         * }
     */
    public function show(Article $article)
    {
        return new ArticleResource($article->loadMissing(['category']));
    }

    /**
     * Update the specified article for the contributor.
     *
     * @param ArticleRequest $request
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     * @response 200 {
     *  "data": {
     *    "id": "123e4567-e89b-12d3-a456-426614174000",
     *    "title": "Article 1",
     *    "slug": "article-1",
     *    "content": "Article 1 content",
     *    "cover_image": "https://example.com/cover-image.jpg",
     *    "media": "https://example.com/media.mp4",
     *    "category": {
     *      "id": "123e4567-e89b-12d3-a456-426614174000",
     *      "name": "Category 1",
     *      "description": "Category 1 description",
     *      "icon": "https://example.com/icon.svg",
     *      "status": [
     *        "value": "active",
     *        "label": "Active"
     *      ]
     *    },
     *    "is_featured": true,
     *    "published_at": "2026-01-01 00:00:00",
     *    "created_at": "2026-01-01 00:00:00",
     *    "updated_at": "2026-01-01 00:00:00"
     *  }
     * }
     */
    public function update(ArticleRequest $request, Article $article)
    {
        $article->title = $request->string('title')->trim();
        $article->content = $request->string('content')->trim();
        $article->cover_image = $request->string('cover_image');
        $article->media = $request->string('media');
        $article->category_id = $request->category_id;
        $article->is_featured = $request->boolean('is_featured');
        $article->save();

        return new ArticleResource($article);
    }

    /**
     * Remove the specified article for the contributor.
     *
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     * @response 204 {
     * }
     */
    public function destroy(Article $article)
    {
        $article->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
