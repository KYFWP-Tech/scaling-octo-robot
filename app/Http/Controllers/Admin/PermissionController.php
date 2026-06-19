<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @tags Permission Management
 */
class PermissionController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.Permission::class)->only(['index']),
            new Middleware('can:show,permission')->only(['show']),
            new Middleware('can:update,permission')->only(['update']),
        ];
    }

    /**
     * List permissions.
     */
    public function index(): AnonymousResourceCollection
    {
        $permissions = Permission::orderBy('name')->get();

        return PermissionResource::collection($permissions);
    }

    /**
     * Get the specified permission.
     *
     * @urlParam permission integer required The ID of the permission. Example: 1
     */
    public function show(Permission $permission): PermissionResource
    {
        return new PermissionResource($permission);
    }

    /**
     * Update the specified permission.
     *
     * @urlParam permission integer required The ID of the permission. Example: 1
     * @bodyParam name string required The permission name. Must be unique except for this permission. Example: articles.index
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $permission->update($request->validated());

        return new PermissionResource($permission);
    }
}
