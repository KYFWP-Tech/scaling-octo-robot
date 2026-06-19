<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAuthenticatedUserRequest;
use App\Http\Resources\UserResource;
use App\Jobs\DeleteUserAccountJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @tags Authenticated User Management
 */
class AuthenticatedUserController extends Controller
{
    /**
     * Get Authenticated User
     *
     * Retrieve the profile information for the currently authenticated user.
     */
    public function show(): UserResource
    {
        return new UserResource(Auth::user());
    }

    /**
     * Update Authenticated User
     *
     * Update the profile information for the currently authenticated user.
     *
     * @bodyParam name string optional The name of the user. Example: John
     * @bodyParam email string optional The email of the user. Must be unique. Example: john.smith@example.com
     */
    public function update(UpdateAuthenticatedUserRequest $request): UserResource
    {
        $validated = $request->validated();
        $user = Auth::user();

        DB::transaction(function () use ($user, $validated) {
            $user->update($validated);
            $user->profile->update($validated);
        });

        return new UserResource($user);
    }

    /**
     * Delete Authenticated User
     *
     * Permanently delete the authenticated user's account.
     */
    public function destroy(): JsonResponse
    {
        $user = Auth::user();
        DeleteUserAccountJob::dispatch($user);
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Account deletion has started. Your account and related data will be permanently removed shortly.',
        ], 202);
    }
}
