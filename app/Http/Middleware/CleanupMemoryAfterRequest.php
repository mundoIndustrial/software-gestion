<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanupMemoryAfterRequest
{
    /**
     * Handle an incoming request and clean memory after response.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // ✅ Forzar limpieza de memoria después de cada request
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        return $response;
    }
    
    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        // ✅ Limpieza adicional después de enviar respuesta
        gc_collect_cycles();
        
        // ✅ Limpiar variables grandes si existen
        if (isset($GLOBALS['largeData'])) {
            unset($GLOBALS['largeData']);
        }
    }
}
