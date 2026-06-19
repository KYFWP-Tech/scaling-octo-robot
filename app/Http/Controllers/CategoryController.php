<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

/**
 * @tags Public Category Management
 */
class CategoryController extends Controller
{
    /**
     * List active categories.
     *
     * @response 200 {
     *  "data": [
     *    {
     *      "id": "123e4567-e89b-12d3-a456-426614174000",
     *      "name": "Theology",
     *      "description": "The study of God and religious belief",
     *      "icon": "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\"></svg>",
     *      "status": {
     *        "value": 1,
     *        "label": "Active"
     *      },
     *      "articles_count": 5,
     *      "created_at": "2026-01-01T00:00:00.000000Z",
     *      "updated_at": "2026-01-01T00:00:00.000000Z"
     *    }
     *  ]
     * }
     */
    public function index()
    {
        $categories = Category::select('id', 'name', 'description', 'icon', 'status')
            ->where('status', Status::ACTIVE)
            ->get();

        return CategoryResource::collection($categories);
    }
}
