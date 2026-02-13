<?php

namespace App\Application\Services\Cotizacion;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class EliminarFotoInmediatamenteService
{
    public function ejecutar(string $rutaFoto, ?int $fotoId = null): int
    {
        $rutaFoto = urldecode($rutaFoto);
        $rutaFoto = is_string($rutaFoto) ? trim($rutaFoto) : $rutaFoto;
        $rutaFoto = str_replace('\\', '/', $rutaFoto);

        // Si viene como URL completa, extraer la parte posterior a /storage/
        if (is_string($rutaFoto) && str_starts_with($rutaFoto, 'http') && preg_match('#/storage/(.+)$#', $rutaFoto, $m)) {
            $rutaFoto = '/storage/' . $m[1];
        }

        $rutaRelativa = $rutaFoto;
        if (strpos($rutaFoto, '/storage/') !== false) {
            $rutaRelativa = substr($rutaFoto, strpos($rutaFoto, '/storage/') + 9);
        } elseif (strpos($rutaFoto, 'storage/') !== false) {
            $rutaRelativa = substr($rutaFoto, strpos($rutaFoto, 'storage/') + 8);
        }

        if (is_string($rutaRelativa)) {
            $rutaRelativa = trim($rutaRelativa);
            $rutaRelativa = str_replace('\\', '/', $rutaRelativa);
            $rutaRelativa = ltrim($rutaRelativa, '/');
            if (strpos($rutaRelativa, 'storage/') === 0) {
                $rutaRelativa = substr($rutaRelativa, 8);
            }
        }

        $rutaConStorage = 'storage/' . $rutaRelativa;
        $rutaConSlash = '/' . $rutaConStorage;

        if (is_string($rutaRelativa) && $rutaRelativa !== '' && Storage::disk('public')->exists($rutaRelativa)) {
            Storage::disk('public')->delete($rutaRelativa);
        }

        $rutaPublica = public_path("storage/{$rutaRelativa}");
        if (file_exists($rutaPublica)) {
            @unlink($rutaPublica);
        }

        $rutasABuscar = [$rutaFoto, $rutaRelativa, $rutaConStorage, $rutaConSlash];

        $fotosEliminadas = 0;

        foreach ($rutasABuscar as $ruta) {
            $fotosEliminadas += \App\Models\PrendaFotoCot::where('ruta_original', $ruta)
                ->orWhere('ruta_webp', $ruta)
                ->delete();
        }

        foreach ($rutasABuscar as $ruta) {
            $fotosEliminadas += \App\Models\PrendaTelaFotoCot::where('ruta_original', $ruta)
                ->orWhere('ruta_webp', $ruta)
                ->delete();
        }

        foreach ($rutasABuscar as $ruta) {
            $fotosEliminadas += \App\Models\ReflectivoCotizacionFoto::where('ruta_original', $ruta)
                ->orWhere('ruta_webp', $ruta)
                ->delete();
        }

        foreach ($rutasABuscar as $ruta) {
            $fotosEliminadas += \App\Models\LogoCotizacionTecnicaPrendaFoto::where('ruta_original', $ruta)
                ->orWhere('ruta_webp', $ruta)
                ->orWhere('ruta_miniatura', $ruta)
                ->delete();
        }

        if ($fotoId) {
            $fotoEliminada = \App\Models\ReflectivoCotizacionFoto::where('id', $fotoId)->delete();
            if ($fotoEliminada) {
                $fotosEliminadas += $fotoEliminada;
            }
        }

        Log::info('EliminarFotoInmediatamenteService ejecutado', [
            'ruta_original' => $rutaFoto,
            'ruta_relativa' => $rutaRelativa,
            'registros_eliminados' => $fotosEliminadas,
        ]);

        return $fotosEliminadas;
    }
}
