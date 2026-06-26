<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceJson
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $contentType = $request->header('Content-Type');

        if (strpos($contentType, 'application/json') === false && strpos($contentType, 'multipart/form-data') === false) {
            return response()->json(['error' => 'Invalid Content-Type. Must be JSON or multipart/form-data.'], 415);
        }

        return $next($request);
    }
}
