<?php

namespace App\Helpers;

/**
 * Helper para versionado de assets
 * Previene que el navegador use versiones cacheadas cuando hay cambios
 */
class AssetVersionHelper
{
    /**
     * Genera una URL de asset con versioning basado en modificación de archivo
     *
     * Uso en vistas:
     * <script src="{{ asset_with_version('js/app.js') }}"></script>
     * <link rel="stylesheet" href="{{ asset_with_version('css/app.css') }}">
     */
    public static function asset_with_version(string $path): string
    {
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            // Si el archivo no existe, usar timestamp actual
            $version = time();
        } else {
            // Usar timestamp de última modificación
            $version = filemtime($fullPath);
        }

        return asset($path) . '?v=' . $version;
    }
}
