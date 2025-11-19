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
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
        //
    })->create();
