<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Admin Category Management
 */
class CategoryController implements HasMiddleware
{
    public static function middleware(): array
    {
        return[
            new Middleware('can:index,'.Category::class)->only(['index']),
            new Middleware('can:store,'.Category::class)->only(['store']),
            new Middleware('can:show,category')->only(['show']),
            new Middleware('can:update,category')->only(['update']),
            new Middleware('can:destroy,category')->only(['destroy']),
        ];
    }

    /**
     * List Categories
     *
     * Get a list of all active categories.
     *
     * @queryParam search string Optional. Search in both name and description. Example: electronics
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $searchTerm = $request->search;
        $categories = Category::when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        })
            ->when($request->has('status') && ! empty($request->status), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate();

        return CategoryResource::collection($categories);
    }

    /**
     * Get Category
     *
     * Get detailed information about a specific category.
     *
     * @urlParam category integer required The ID of the category. Example: 1
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    /**
     * Create Category
     *
     * Create a new category.
     *
     * @bodyParam name string required The name of the category. Example: Electronics
     * @bodyParam description string required The description of the category. Example: Electronic devices and gadgets
     * @bodyParam icon string required The icon identifier for the category. Example: devices
     * @bodyParam status integer required The status of the category (0 for inactive, 1 for active). Example: 1
     */
    public function store(CategoryRequest $request): CategoryResource
    {
        $category = Category::create($request->validated());

        /** @status 201 */
        return new CategoryResource($category);
    }

    /**
     * Update Category
     *
     * Update an existing category's information.
     *
     * @urlParam category integer required The ID of the category. Example: 1
     * @bodyParam name string optional The name of the category. Example: Theology
     * @bodyParam description string optional The description of the category. Example: The study of God and the nature of religion
     * @bodyParam icon string optional The icon identifier for the category.
     * @bodyParam status integer optional The status of the category (0 for inactive, 1 for active). Example: 1
     */
    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Delete Category
     *
     * @urlParam category integer required The ID of the category. Example: 1
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
