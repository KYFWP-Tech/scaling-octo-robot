<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Models\Permission;

/**
 * @group Permission Management
 *
 * APIs for viewing and managing permissions
 */
class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:index,'.Permission::class)->only(['index']);
        $this->middleware('can:show,permission')->only(['show']);
        $this->middleware('can:update,permission')->only(['update']);
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
