<?php

use App\Http\Middleware\EnforceJson;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\XSSProtection;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(HandleCors::class);
        $middleware->statefulApi();
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
        $middleware->alias([
            'json' => EnforceJson::class,
            'XSS' => XSSProtection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );


        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_FORBIDDEN);
            }
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        $exceptions->renderable(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = str_contains($e->getMessage(), 'No query results for model [App\\Models\\')
                    ? 'Record not found'
                    : ($e->getMessage() ?: 'Ooops!!! An error occured!');

                return response()->json([
                    'message' => $message,
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->renderable(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() == 'Route [login] not defined.' ? 'Uauthenticated user - login to continue' : "Route not found!",
                ], Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => "Action Method not allowed",
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }
        });


        $exceptions->renderable(function (\Exception $e, Request $request) {
            if ($request->is('api/*')
                && ! $e instanceof AuthenticationException
                && ! $e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });

        $exceptions->renderable(function (Error $e, Request $request) {
            // Log the error for debugging purposes
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    })->create();
