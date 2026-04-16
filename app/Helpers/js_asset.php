<?php

/**
 * Helper para assets JS con minificacion automatica.
 *
 * En produccion (APP_DEBUG=false), carga el .min.js si existe.
 * En desarrollo (APP_DEBUG=true), carga el .js original.
 *
 * Uso en Blade:
 *   <script defer src="{{ js_asset('js/servicios/shared/event-bus.js') }}?v={{ config('app.asset_version') }}"></script>
 *
 * O con la directive:
 *   @jsDefer('js/servicios/shared/event-bus.js')
 */

if (!function_exists('js_asset')) {
    /**
     * Genera la URL del asset JS, usando .min.js en produccion si esta disponible.
     *
     * @param string $path Ruta relativa al public/ (ej: 'js/servicios/shared/event-bus.js')
     * @return string URL completa del asset
     */
    function js_asset(string $path): string
    {
        // En desarrollo, siempre el original
        if (config('app.debug')) {
            return asset($path);
        }

        // En produccion, intentar .min.js
        $minPath = preg_replace('/\.js$/', '.min.js', $path);
        $fullPath = public_path($path);
        $fullMinPath = public_path($minPath);

        if (file_exists($fullMinPath)) {
            // Evita servir minificados obsoletos si el .js original es mas reciente.
            if (file_exists($fullPath)) {
                $mtimeOriginal = @filemtime($fullPath) ?: 0;
                $mtimeMin = @filemtime($fullMinPath) ?: 0;
                if ($mtimeOriginal > $mtimeMin) {
                    return asset($path);
                }
            }

            return asset($minPath);
        }

        // Fallback al original si no existe .min.js
        return asset($path);
    }
}
