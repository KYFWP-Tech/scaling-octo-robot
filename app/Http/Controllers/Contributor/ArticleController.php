<?php

namespace App\Http\Controllers\Contributor;

use App\Enums\Status;
use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Contributor Article Management
 */
class ArticleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Article::class)->only(['index']),
            new Middleware('can:store,'.Article::class)->only(['store']),
            new Middleware('can:show,article')->only(['show']),
            new Middleware('can:update,article')->only(['update']),
            new Middleware('can:destroy,article')->only(['destroy']),
        ];
    }

    /**
     * Get all articles for the contributor.
     */
    public function index(): AnonymousResourceCollection
    {
        $articles = Auth::user()->articles()->with('category')->paginate();

        return ArticleResource::collection($articles);
    }

    /**
     * Store a newly created article for the contributor.
     */
    public function store(ArticleRequest $request): ArticleResource|JsonResponse
    {
        $article = new Article();

        if ($error = $article->validateAssets($request, $article)) {
            return $error;
        }

        $article->title = $request->string('title')->trim();
        $article->content = $request->string('content')->trim();
        $article->cover_image = $request->input('cover_image');
        $article->media = $request->input('media');
        $article->category_id = $request->category_id;
        $article->is_featured = $request->boolean('is_featured');
        $article->status = Status::INACTIVE->value;
        $article->user_id = Auth::id();
        $article->save();

        /** @status 201 */
        return new ArticleResource($article);
    }

    /**
     * Get the specified article for the contributor.
     */
    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article->loadMissing(['category']));
    }

    /**
     * Update the specified article for the contributor.
     */
    public function update(ArticleRequest $request, Article $article): ArticleResource|JsonResponse
    {
        if ($error = $article->validateAssets($request, $article)) {
            return $error;
        }

        $article->title = $request->string('title')->trim();
        $article->content = $request->string('content')->trim();
        $article->cover_image = $request->input('cover_image', $article->cover_image);
        $article->media = $request->input('media', $article->media);
        $article->category_id = $request->category_id;
        $article->is_featured = $request->boolean('is_featured');
        $article->save();

        return new ArticleResource($article);
    }

    /**
     * Remove the specified article for the contributor.
     */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
