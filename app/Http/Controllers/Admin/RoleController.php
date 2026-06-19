<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Role Management
 */
class RoleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Role::class)->only(['index']),
            new Middleware('can:show,role')->only(['show']),
            new Middleware('can:update,role')->only(['update']),
        ];
    }

    /**
     * List roles with their permissions.
     *
     * @response 200 {
     *  "data": [
     *    {
     *      "id": 1,
     *      "name": "admin",
     *      "permissions": [
     *        {
     *          "id": 1,
     *          "name": "articles.index",
     *          "createdAt": "2026-01-01T00:00:00.000000Z",
     *          "updatedAt": "2026-01-01T00:00:00.000000Z"
     *        }
     *      ],
     *      "createdAt": "2026-01-01T00:00:00.000000Z",
     *      "updatedAt": "2026-01-01T00:00:00.000000Z"
     *    }
     *  ]
     * }
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return RoleResource::collection($roles);
    }

    /**
     * Get the specified role.
     *
     * @urlParam role integer required The ID of the role. Example: 1
     *
     * @response 200 {
     *  "data": {
     *    "id": 1,
     *    "name": "admin",
     *    "permissions": [
     *      {
     *        "id": 1,
     *        "name": "articles.index",
     *        "createdAt": "2026-01-01T00:00:00.000000Z",
     *        "updatedAt": "2026-01-01T00:00:00.000000Z"
     *      }
     *    ],
     *    "createdAt": "2026-01-01T00:00:00.000000Z",
     *    "updatedAt": "2026-01-01T00:00:00.000000Z"
     *  }
     * }
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     * @response 404 {
     *  "message": "No query results for model [App\\Models\\Role] 99"
     * }
     */
    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the permissions assigned to a role.
     *
     * @urlParam role integer required The ID of the role. Example: 1
     * @bodyParam permissions array required The permission names to assign. Example: ["articles.index", "articles.show"]
     * @bodyParam permissions.* string required A permission name that exists in the database. Example: articles.index
     *
     * @response 200 {
     *  "data": {
     *    "id": 1,
     *    "name": "editor",
     *    "permissions": [
     *      {
     *        "id": 1,
     *        "name": "articles.index",
     *        "createdAt": "2026-01-01T00:00:00.000000Z",
     *        "updatedAt": "2026-01-01T00:00:00.000000Z"
     *      },
     *      {
     *        "id": 2,
     *        "name": "articles.show",
     *        "createdAt": "2026-01-01T00:00:00.000000Z",
     *        "updatedAt": "2026-01-01T00:00:00.000000Z"
     *      }
     *    ],
     *    "createdAt": "2026-01-01T00:00:00.000000Z",
     *    "updatedAt": "2026-01-01T00:00:00.000000Z"
     *  }
     * }
     * @response 401 {
     *  "message": "Unauthenticated."
     * }
     * @response 403 {
     *  "message": "This action is unauthorized."
     * }
     * @response 422 {
     *  "message": "The permissions field is required.",
     *  "errors": {
     *    "permissions": ["The permissions field is required."]
     *  }
     * }
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->syncPermissions($request->validated('permissions'));

        return new RoleResource($role->load('permissions'));
    }
}
