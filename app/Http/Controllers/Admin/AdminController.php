<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdminRequest;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\UpdateAuthenticatedUserRequest;
use App\Http\Requests\Admin\VerifyAdminRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Models\Verification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Admin Management
 */
class AdminController implements HasMiddleware
{
    public static function middleware(): array
    {
        return[
            new Middleware('can:index,'.Admin::class)->only(['index']),
            new Middleware('can:store,'.Admin::class)->only(['store']),
            new Middleware('can:show,admin')->only(['show']),
            new Middleware('can:update,admin')->only(['update']),
            new Middleware('can:destroy,admin')->only(['destroy']),
            new Middleware('can:assignRole,admin')->only(['assignRole']),
        ];
    }

    /**
     * List Admins
     *
     * Get a paginated list of all admin users.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer The number of items per page. Example: 15
     */
    public function index(): AnonymousResourceCollection
    {
        $admins = Admin::latest()->paginate();

        return AdminResource::collection($admins);
    }

    /**
     * Get Admin
     *
     * Get detailed information about a specific admin user.
     *
     * @urlParam admin string required The UUID of the admin. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function show(Admin $admin): AdminResource
    {
        return new AdminResource($admin->load(['user.roles']));
    }

    /**
     * Create Admin
     *
     * Create a new admin user and send an invitation email.
     *
     * @bodyParam name string required The name of the admin. Example: John Doe
     * @bodyParam email string required The email address of the admin. Must be unique. Example: john@example.com
     */
    public function store(AdminRequest $request): AdminResource
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $admin = Admin::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
            $admin->makeUser(['status' => $validated['status']]);

            /** @status 201 */
            return new AdminResource($admin->load('user'));
        });
    }

    /**
     * Update Admin
     *
     * Update an existing admin user's information.
     *
     * @urlParam admin string required The UUID of the admin. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam name string optional The name of the admin. Example: John Doe
     * @bodyParam email string optional The email address of the admin. Must be unique. Example: john@example.com
     */
    public function update(UpdateAuthenticatedUserRequest $request, Admin $admin): AdminResource
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($admin, $validated) {
            $admin->update($validated);

            if (isset($validated['email'])) {
                $admin->user->forceFill([
                    'email' => $validated['email'],
                    'email_verified_at' => null,
                ])->save();

                $admin->user->sendVerificationEmail();
            } else {
                $admin->user->update($validated);
            }

            return new AdminResource($admin->load('user'));
        });
    }

    /**
     * Assign Role
     *
     * Assign a role to an admin user.
     *
     * @urlParam admin string required The UUID of the admin. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam role string required The role to assign. Example: admin
     */
    public function assignRole(AssignRoleRequest $request, Admin $admin): AdminResource
    {
        $role = $request->validated('role');

        $admin->user->syncRoles([$role]);

        return new AdminResource($admin->load(['user.roles']));
    }

    /**
     * Delete Admin
     *
     * Remove an admin user from the system.
     *
     * @urlParam admin string required The UUID of the admin. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function destroy(Admin $admin): JsonResponse
    {
        DB::transaction(function () use ($admin) {
            $admin->user->delete();
            $admin->delete();
        });

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Accept Invitation
     *
     * Complete the admin account setup process by accepting the invitation.
     *
     * @bodyParam code string required The verification code from the invitation email. Example: 550e8400-e29b-41d4-a716-446655440000
     * @bodyParam password string required The new password for the account. Must be at least 8 characters. Example: password123
     * @bodyParam password_confirmation string required The password confirmation. Must match the password. Example: password123
     */
    public function acceptInvitation(VerifyAdminRequest $request): AdminResource|JsonResponse
    {
        $validated = $request->validated();
        $verification = Verification::where('code', $validated['token'])->firstOrFail();

        if ($verification->hasExpired()) {
            return response()->json([
                'message' => 'This invitation has expired',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($verification, $validated) {
            $user = $verification->user;
            $user->verify($validated['password']);

            $admin = $user->profile;
            $verification->delete();

            return new AdminResource($admin->load(['user.roles']));
        });
    }
}
