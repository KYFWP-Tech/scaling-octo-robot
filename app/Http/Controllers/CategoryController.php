<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

/**
 * @tags Public Category Management
 *
 */
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::select('id', 'name', 'description', 'icon', 'status')
            ->where('status', Status::ACTIVE)
            ->get();

        return CategoryResource::collection($categories);
    }
}
