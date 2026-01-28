<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // En desarrollo, no aplicar CSP restrictiva
        if (app()->environment('local', 'development')) {
            return $response;
        }

        //  IMPORTANTE: Remover cualquier CSP header previo para evitar conflictos
        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');

        // Detectar si estamos en desarrollo
        $isDevelopment = app()->environment('local', 'development');
        
        // En desarrollo, usar el hostname actual para Vite y Reverb
        $serverHost = $request->getHost();
        $serverIp = $request->ip();
        $serverPort = $request->getPort();
        
        // Para desarrollo local, permitir localhost, 127.0.0.1, hostname, la IP actual, y todas las IPs locales comunes
        $viteSources = $isDevelopment ? " http://localhost:5173 ws://localhost:5173 http://127.0.0.1:5173 ws://127.0.0.1:5173 http://{$serverHost}:5173 ws://{$serverHost}:5173 http://{$serverIp}:5173 ws://{$serverIp}:5173 http://192.168.20.23:5173 ws://192.168.20.23:5173" : "";
        $connectSources = $isDevelopment ? " ws://localhost:8080 ws://{$serverHost}:8080 wss://{$serverHost}:8080 ws://{$serverIp}:8080 wss://{$serverIp}:8080 ws://192.168.20.23:8080 wss://192.168.20.23:8080" : "";

        // Content Security Policy (CSP) - More permissive for Laravel Echo and CDNs
        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com{$viteSources}; "
            . "worker-src 'self' blob:; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.bunny.net{$viteSources}; "
            . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://fonts.bunny.net; "
            . "img-src 'self' data: https: blob:; "
            . "connect-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com ws: wss: https:{$connectSources}; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "media-src 'self' https: blob:;";

        $response->headers->set('Content-Security-Policy', $csp);
        
        // X-Frame-Options - Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // X-Content-Type-Options - Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // X-XSS-Protection - Enable XSS filter in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Referrer-Policy - Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions-Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Expect-CT - Certificate Transparency
        $response->headers->set('Expect-CT', 'max-age=86400, enforce');

        return $response;
    }
}
