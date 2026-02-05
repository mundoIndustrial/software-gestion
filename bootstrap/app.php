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
    ->withProviders([
        // Domain providers
        \App\Infrastructure\Pedidos\Providers\PedidoServiceProvider::class,
        \App\Infrastructure\Procesos\Providers\ProcesosServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'bodega-access' => \App\Http\Middleware\CheckBodegaAccess::class,
            'supervisor-access' => \App\Http\Middleware\SupervisorAccessControl::class,
            'supervisor-readonly' => \App\Http\Middleware\SupervisorReadOnly::class,
            'insumos-access' => \App\Http\Middleware\InsumosAccess::class,
            'redirect-to-login' => \App\Http\Middleware\RedirectToLoginIfUnauthenticated::class,
            'operario-access' => \App\Http\Middleware\OperarioAccess::class,
            'check.despacho.role' => \App\Http\Middleware\CheckDespachoRole::class,
            'restrict-bodega-roles' => \App\Http\Middleware\RestrictBodegaRoles::class,
        ]);
        
        // ⚡ TESTING: Deshabilitar CSRF para Postman
        $middleware->validateCsrfTokens(except: [
            'pedidos-produccion/crear-sin-cotizacion',
        ]);
        
        // Add security headers middleware globally
        $middleware->append(\App\Http\Middleware\SetSecurityHeaders::class);
        
        // Restrict bodega roles to bodega routes only
        $middleware->append(\App\Http\Middleware\RestrictBodegaRoles::class);
        
        //  Add memory cleanup middleware to prevent memory exhaustion
        $middleware->append(\App\Http\Middleware\CleanupMemoryAfterRequest::class);
        
        //  Handle storage images conversion (PNG -> WebP fallback)
        $middleware->append(\App\Http\Middleware\HandleStorageImages::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
