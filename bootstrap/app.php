<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Force API routes to expect JSON
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // Enable CORS for API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Staff middleware alias and group (Sanctum + staff check)
        $middleware->alias([
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->group('staff', [
            'auth:sanctum',
            'staff',
        ]);
        $middleware->group('editor', [
            'auth:sanctum',
            'staff',
            'role:editor',
        ]);
        $middleware->group('admin', [
            'auth:sanctum',
            'staff',
            'role:admin',
        ]);
        $middleware->group('auth', [
            'auth:sanctum',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        // Handle validation exceptions
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle route not found exceptions
        $exceptions->render(function (\Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested route does not exist.',
                ], 404);
            }
        });
    })->create();
