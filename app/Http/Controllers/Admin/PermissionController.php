<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @group Permission Management
 *
 * APIs for viewing and managing permissions
 */
class PermissionController implements HasMiddleware
{
    public static function middleware(): array
    {
        return[
            new Middleware('can:index,'.Permission::class)->only(['index']),
            new Middleware('can:show,permission')->only(['show']),
            new Middleware('can:update,permission')->only(['update']),
        ];
    }

    public function index(): AnonymousResourceCollection
    {
        $permissions = Permission::orderBy('name')->get();

        return PermissionResource::collection($permissions);
    }

    public function show(Permission $permission): PermissionResource
    {
        return new PermissionResource($permission);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $permission->update($request->validated());

        return new PermissionResource($permission);
    }
}
