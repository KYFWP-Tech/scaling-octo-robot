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
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->syncPermissions($request->validated('permissions'));

        return new RoleResource($role->load('permissions'));
    }
}
