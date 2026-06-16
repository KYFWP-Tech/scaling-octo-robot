<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAuthenticatedUserRequest;
use App\Http\Resources\UserResource;
use App\Jobs\DeleteUserAccountJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Authenticated User
 *
 * APIs for managing the currently authenticated user's profile
 */
class AuthenticatedUserController extends Controller
{
    /**
     * Get Authenticated User
     *
     * Retrieve the profile information for the currently authenticated user.
     *
     * @response 200 {
     *  "data": {
     *      "id": "550e8400-e29b-41d4-a716-446655440000",
     *      "name": "John Doe",
     *      "email": "john@example.com",
     *      "created_at": "2024-03-24T12:00:00.000000Z",
     *      "updated_at": "2024-03-24T12:00:00.000000Z",
     *      "status": {
     *        "value": "Active",
     *        "label": "Active",
     *      }
     *  }
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @apiResource App\Http\Resources\UserResource
     */
    public function show()
    {
        $user = Auth::user();

        return new UserResource($user);
    }

    /**
     * Update Authenticated User
     *
     * Update the profile information for the currently authenticated user.
     *
     * @bodyParam name string optional The name of the user. Example: John
     * @bodyParam email string optional The email of the user. Must be unique. Example: john.smith@example.com
     *
     *
     * @response 200 {
     *  "data": {
     *      "id": "550e8400-e29b-41d4-a716-446655440000",
     *      "name": "John Doe",
     *      "email": "john@example.com",
     *      "created_at": "2024-03-24T12:00:00.000000Z",
     *      "updated_at": "2024-03-24T12:00:00.000000Z",
     *      "status": {
     *        "value": "Active",
     *        "label": "Active",
     *      }
     *  }
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": [
     *       "The email has already been taken."
     *     ]
     *   }
     * }
     *
     * @apiResource App\Http\Resources\UserResource
     */
    public function update(UpdateAuthenticatedUserRequest $request)
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
     *
     * @response 202 {
     *   "message": "Account deletion has started. Your account and related data will be permanently removed shortly."
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function destroy()
    {
        $user = Auth::user();
        DeleteUserAccountJob::dispatch($user);
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Account deletion has started. Your account and related data will be permanently removed shortly.',
        ], 202);
    }

}
