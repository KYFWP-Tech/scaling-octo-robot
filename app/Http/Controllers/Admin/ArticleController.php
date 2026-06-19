<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Admin Article Management
 */
class ArticleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Article::class)->only(['index']),
            new Middleware('can:show,article')->only(['show']),
            new Middleware('can:update,article')->only(['update']),
            new Middleware('can:destroy,article')->only(['destroy']),
        ];
    }

    /**
     * List Articles
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $articles = Article::with(['category:id,name,icon,status', 'author:id,name,status'])
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
     */
    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article->loadMissing(['category', 'author']));
    }

    /**
     * Update the specified article status.
     */
    public function update(ChangeStatusRequest $request, Article $article): ArticleResource
    {
        $article->status = $request->enum('status', Status::class, $article->status);
        if ($request->status == Status::ACTIVE->value) {
            $article->published_at = now();
        }
        $article->save();

        return new ArticleResource($article);
    }

    /**
     * Remove the specified article for the admin.
     */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
