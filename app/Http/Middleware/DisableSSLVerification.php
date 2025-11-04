<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableSSLVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo en desarrollo y si está configurado para deshabilitar SSL
        if (config('app.env') === 'local' && !config('firebase.verify_ssl', true)) {
            // Deshabilitar verificación SSL para cURL
            curl_setopt_array(curl_init(), [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
        }

        return $next($request);
    }
}
