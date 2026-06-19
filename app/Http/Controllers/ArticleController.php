<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Public Article Management
 */
class ArticleController extends Controller
{
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
     * Get the specified article.
     */
    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article->loadMissing(['category', 'author']));
    }
}
