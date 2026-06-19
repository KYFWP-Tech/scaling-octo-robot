<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Public Category Management
 */
class CategoryController extends Controller
{
    /**
     * List active categories.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::select('id', 'name', 'description', 'icon', 'status')
            ->where('status', Status::ACTIVE)
            ->get();

        return CategoryResource::collection($categories);
    }
}
