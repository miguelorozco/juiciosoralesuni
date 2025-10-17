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
        $middleware->alias([
            'user.type' => \App\Http\Middleware\CheckUserType::class,
            'login.rate.limit' => \App\Http\Middleware\LoginRateLimit::class,
            'prevent.user.enumeration' => \App\Http\Middleware\PreventUserEnumeration::class,
            'check.user.registration' => \App\Http\Middleware\CheckUserRegistration::class,
            'web.auth' => \App\Http\Middleware\WebAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
