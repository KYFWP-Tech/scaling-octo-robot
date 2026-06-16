<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * @group Role Management
 *
 * APIs for viewing and managing roles
 */
class RoleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return[
            new Middleware('can:index,'.Role::class)->only(['index']),
            new Middleware('can:show,role')->only(['show']),
            new Middleware('can:update,role')->only(['update']),
        ];
    }

    public function index(): AnonymousResourceCollection
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return RoleResource::collection($roles);
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->syncPermissions($request->validated('permissions'));

        return new RoleResource($role->load('permissions'));
    }
}
