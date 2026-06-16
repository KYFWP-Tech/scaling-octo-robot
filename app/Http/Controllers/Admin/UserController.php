<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class UserController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:index,'.User::class, only: ['index']),
            new Middleware('can:show,user', only: ['show']),
            new Middleware('can:update,user', only: ['update']),
            new Middleware('can:destroy,user', only: ['destroy']),
        ];
    }


    /**
     * List users
     *
     * Get a paginated list of all users.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer The number of items per page. Example: 15
     *
     * @response 200 {
     *  "data": [
     *    {
     *      "id": "550e8400-e29b-41d4-a716-446655440000",
     *      "name": "John Doe",
     *      "email": "john@example.com",
     *      "created_at": "2024-03-24T12:00:00.000000Z",
     *      "updated_at": "2024-03-24T12:00:00.000000Z",
     *      "status": {
     *        "value": "Active",
     *        "label": "Active",
     *      }
     *    }
     *  ],
     *  "links": {
     *    "first": "http://example.com/api/users?page=1",
     *    "last": "http://example.com/api/users?page=1",
     *    "prev": null,
     *    "next": null
     *  },
     *  "meta": {
     *    "current_page": 1,
     *    "from": 1,
     *    "last_page": 1,
     *    "path": "http://example.com/api/admins/users",
     *    "per_page": 15,
     *    "to": 1,
     *    "total": 1
     *  }
     * }
     * @response 403 {
     *  "message": "You are not authorized to view users."
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $users = User::latest()->paginate();

        return UserResource::collection($users);
    }

      /**
     * Get User
     *
     * Get detailed information about a specific user.
     *
     * @urlParam user string required The UUID of the user. Example: 550e8400-e29b-41d4-a716-446655440000
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
     * @response 404 {
     *  "message": "User not found."
     * }
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }



      /**
     * Update User Status
     *
     * Update specific user status
     *
     * @urlParam user string required The UUID of the user. Example: 550e8400-e29b-41d4-a716-446655440000
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
     * @response 404 {
     *  "message": "User not found."
     * }
     */
    public function update(User $user, ChangeStatusRequest $request): UserResource
    {
        $user->status = $request->enum('status', Status::class, $user->status);
        $user->save();

        return new UserResource($user);
    }


    /**
     * Delete User
     *
     * Remove a user from the system.
     *
     * @urlParam user string required The UUID of the user. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 204 {}
     * @response 404 {
     *  "message": "User not found."
     * }
     */
    public function destroy(User $user): JsonResponse
    {
        DB::transaction(function () use ($user) {
            $user->delete();
            $user->profile->delete();
        });

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
