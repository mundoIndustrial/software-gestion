<?php

namespace App\Application\Cotizacion\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Aplicación: Borrar archivos de almacenamiento
 * Abstrae la lógica de borrado de archivos del disco público
 */
class BorrarArchivoService
{
    /**
     * Borrar archivo si existe
     */
    public function borrar(?string $ruta): bool
    {
        if (!$ruta) {
            return false;
        }

        try {
            $path = $this->normalizarRuta($ruta);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::debug('Archivo borrado', ['ruta' => $ruta]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('No se pudo borrar archivo', [
                'ruta' => $ruta,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Normalizar ruta removiendo prefijo /storage/ si existe
     */
    private function normalizarRuta(string $ruta): string
    {
        if (str_starts_with($ruta, '/storage/')) {
            return substr($ruta, strlen('/storage/'));
        }

        return ltrim($ruta, '/');
    }
}
