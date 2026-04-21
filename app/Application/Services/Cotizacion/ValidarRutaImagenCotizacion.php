<?php

namespace App\Application\Services\Cotizacion;

use Illuminate\Support\Facades\Log;

/**
 * Fase 1: evita persistir data URLs (p. ej. base64) en columnas de ruta de imágenes de cotización.
 */
final class ValidarRutaImagenCotizacion
{
    /**
     * @param  array<string, mixed>  $contextoExtra
     */
    public static function puedePersistirRuta(?string $ruta, string $contexto = '', array $contextoExtra = []): bool
    {
        if ($ruta === null) {
            return false;
        }

        $r = trim($ruta);
        if ($r === '') {
            return false;
        }

        if (str_starts_with(strtolower($r), 'data:')) {
            Log::warning('Cotización: rechazada persistencia de data URL como ruta de imagen', array_merge([
                'contexto' => $contexto,
                'muestra' => substr($r, 0, 96),
            ], $contextoExtra));

            return false;
        }

        return true;
    }
}
