<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Role Management
 *
 * APIs for viewing and managing roles
 */
class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:index,'.Role::class)->only(['index']);
        $this->middleware('can:show,role')->only(['show']);
        $this->middleware('can:update,role')->only(['update']);
        $this->middleware('can:destroy,role')->only(['destroy']);
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

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
