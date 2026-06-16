<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::select('id', 'name', 'description', 'icon')->where('status', Status::ACTIVE)->get();

        return CategoryResource::collection($categories);
    }
}
