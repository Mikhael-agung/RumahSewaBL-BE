<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->redirectGuestsTo(fn() => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (
        \Illuminate\Auth\AuthenticationException $e,
        \Illuminate\Http\Request $request
    ) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    });

    $exceptions->render(function (
        \Tymon\JWTAuth\Exceptions\TokenExpiredException $e,
        \Illuminate\Http\Request $request
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Token sudah expired, silakan refresh atau login ulang',
        ], 401);
    });

    $exceptions->render(function (
        \Tymon\JWTAuth\Exceptions\TokenInvalidException $e,
        \Illuminate\Http\Request $request
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Token tidak valid',
        ], 401);
    });

    $exceptions->render(function (
        \Tymon\JWTAuth\Exceptions\JWTException $e,
        \Illuminate\Http\Request $request
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Token tidak ditemukan',
        ], 401);
    });
    })
    ->create();