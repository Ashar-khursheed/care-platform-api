<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.admin' => \App\Http\Middleware\CheckAdmin::class,
            'auth.api' => \App\Http\Middleware\AuthenticateApi::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle authentication exceptions for API
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        // Force JSON response for all API exceptions
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Return generic 500 error for unexpected server errors
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                // For validation errors (status 422), keep the original structure if possible, or standardize
                if ($status === 422 && method_exists($e, 'errors')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Server Error',
                    'trace' => config('app.debug') ? $e->getTrace() : [], // Optional: add trace for debugging
                ], $status);
            }
        });
    })->create();