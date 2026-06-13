<?php

namespace App\Http\Responses;

use App\Models\User;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            $user = User::where('email', $request->email)->first();
            $user?->tokens()?->delete();

            return response()->json([], 204);
        } else {
            return redirect()->intended('/login');
        }
    }
}
