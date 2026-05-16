<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Helpers js_asset() y css_asset() - Carga .min en producción, original en desarrollo
 */
if (!function_exists('js_asset')) {
    function js_asset(string $path): string {
        if (config('app.debug')) {
            return asset($path);
        }
        $minPath = preg_replace('/\.js$/', '.min.js', $path);
        $fullPath = public_path($path);
        $fullMinPath = public_path($minPath);
        if (file_exists($fullMinPath)) {
            if (file_exists($fullPath)) {
                $mtimeOriginal = @filemtime($fullPath) ?: 0;
                $mtimeMin = @filemtime($fullMinPath) ?: 0;
                if ($mtimeOriginal > $mtimeMin) {
                    return asset($path);
                }
            }
            return asset($minPath);
        }
        return asset($path);
    }
}

if (!function_exists('css_asset')) {
    function css_asset(string $path): string {
        if (config('app.debug')) {
            return asset($path);
        }
        $minPath = preg_replace('/\.css$/', '.min.css', $path);
        $fullPath = public_path($path);
        $fullMinPath = public_path($minPath);
        if (file_exists($fullMinPath)) {
            if (file_exists($fullPath)) {
                $mtimeOriginal = @filemtime($fullPath) ?: 0;
                $mtimeMin = @filemtime($fullMinPath) ?: 0;
                if ($mtimeOriginal > $mtimeMin) {
                    return asset($path);
                }
            }
            return asset($minPath);
        }
        return asset($path);
    }
}

if (!function_exists('asset_with_version')) {
    function asset_with_version(string $path): string {
        return \App\Helpers\AssetVersionHelper::asset_with_version($path);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withProviders([
        // Domain providers - SupervisorPedidos primero
        \App\Providers\SupervisorPedidosServiceProvider::class,
        \App\Infrastructure\Pedidos\Providers\PedidoServiceProvider::class,
        \App\Infrastructure\Procesos\Providers\ProcesosServiceProvider::class,
        \App\Providers\PrendaEditorServiceProvider::class,
        \App\Providers\TalleresServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Importante para túneles/proxies (Cloudflare, localtunnel, ngrok):
        // permite respetar X-Forwarded-* y detectar correctamente https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'bodega-access' => \App\Http\Middleware\CheckBodegaAccess::class,
            'supervisor-access' => \App\Http\Middleware\SupervisorAccessControl::class,
            'supervisor-readonly' => \App\Http\Middleware\SupervisorReadOnly::class,
            'restrict-visualizador-recibos-logo' => \App\Http\Middleware\RestrictVisualizadorRecibosLogo::class,
            'insumos-access' => \App\Http\Middleware\InsumosAccess::class,
            'redirect-to-login' => \App\Http\Middleware\RedirectToLoginIfUnauthenticated::class,
            'operario-access' => \App\Http\Middleware\OperarioAccess::class,
            'control-calidad-access' => \App\Http\Middleware\ControlCalidadAccess::class,
            'check.despacho.role' => \App\Http\Middleware\CheckDespachoRole::class,
            'restrict-bodega-roles' => \App\Http\Middleware\RestrictBodegaRoles::class,
            'restrict-gestion-bodega' => \App\Http\Middleware\RestrictGestionBodega::class,
            'restrict-gestor-epp' => \App\Http\Middleware\RestrictGestorEpp::class,
            'block-costura-reflectivo-dashboard' => \App\Http\Middleware\BlockCosturaReflectivoDashboard::class,
            'idempotency' => \App\Http\Middleware\IdempotencyKeyMiddleware::class,
        ]);
        
        //  TESTING: Deshabilitar CSRF para Postman
        $middleware->validateCsrfTokens(except: [
            'pedidos-produccion/crear-sin-cotizacion',
            'api/pedidos/*/epp/agregar',
        ]);
        
        // Add security headers middleware globally
        $middleware->append(\App\Http\Middleware\SetSecurityHeaders::class);

        // Restringir al rol visualizador_recibos_logo a su vista única de recibos
        $middleware->append(\App\Http\Middleware\RestrictVisualizadorRecibosLogo::class);
        
        // Restrict bodega roles to bodega routes only
        $middleware->append(\App\Http\Middleware\RestrictBodegaRoles::class);

        // Restrict gestion-bodega role to recibos-bodega module only
        $middleware->append(\App\Http\Middleware\RestrictGestionBodega::class);
        
        // Restrict gestor_epp role to /epp routes only
        $middleware->append(\App\Http\Middleware\RestrictGestorEpp::class);
        
        //  Add memory cleanup middleware to prevent memory exhaustion
        $middleware->append(\App\Http\Middleware\CleanupMemoryAfterRequest::class);
        
        //  Handle storage images conversion (PNG -> WebP fallback)
        $middleware->append(\App\Http\Middleware\HandleStorageImages::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
