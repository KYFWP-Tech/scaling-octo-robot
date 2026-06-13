<?php

namespace App\Http\Responses;

use App\Http\Resources\AuthenticatedResource;
use App\Http\Resources\TokenResource;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            $user = Auth::user();
            $token = $user->createToken($request->email);

            return response()->json([
                'token' => new TokenResource($token),
                'user' => new AuthenticatedResource($user),
            ], 200);
        } else {
            return redirect()->intended('/dashboard');
        }
    }
}
