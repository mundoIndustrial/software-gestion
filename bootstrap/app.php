<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'supervisor-access' => \App\Http\Middleware\SupervisorAccessControl::class,
            'supervisor-readonly' => \App\Http\Middleware\SupervisorReadOnly::class,
            'insumos-access' => \App\Http\Middleware\InsumosAccess::class,
            'redirect-to-login' => \App\Http\Middleware\RedirectToLoginIfUnauthenticated::class,
            'operario-access' => \App\Http\Middleware\OperarioAccess::class,
        ]);
        
        // Add security headers middleware globally
        $middleware->append(\App\Http\Middleware\SetSecurityHeaders::class);
        
        // ✅ Add memory cleanup middleware to prevent memory exhaustion
        $middleware->append(\App\Http\Middleware\CleanupMemoryAfterRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
