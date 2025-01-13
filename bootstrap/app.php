<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',  // This handles the root ("/") route.
            commands: __DIR__.'/../routes/console.php',
            health: '/up'
        )
        ->withRouting(
            api: __DIR__.'/../routes/api.php',  // This handles API routes.
            apiPrefix: 'api/'                   // Ensure the prefix is used only for API routes.
        )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
