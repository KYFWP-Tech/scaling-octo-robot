<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    /**
     * List Articles
     *
     * @apiResourceCollection App\Http\Resources\ArticleResource
     * @apiResourceModel App\Models\Article
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $articles = Article::with(['category:id,name,icon', 'author:id,name'])
                            ->when($request->has('search'), function ($query) use ($request) {
                                $query->where('title', 'like', '%'.$request->string('search')->trim().'%');
                            })
                            ->when($request->has('category_id'), function ($query) use ($request) {
                                $query->where('category_id', $request->category_id);
                            })
                            ->when($request->has('status'), function ($query) use ($request) {
                                $query->where('status', $request->status);
                            })
                            ->paginate();

        return ArticleResource::collection($articles);
    }

    /**
     * Get the specified article for the admin.
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
        return new ArticleResource($article->loadMissing(['category', 'author']));
    }
}
