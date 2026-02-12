<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use App\Application\Cotizacion\DTOs\ActualizarImagenesCotizacionDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ActualizarImagenesCotizacionService
{
    public function __construct(
        private readonly \App\Application\Services\EliminarImagenesCotizacionService $eliminarImagenesService,
    ) {
    }

    public function ejecutar(Cotizacion $cotizacion, ActualizarImagenesCotizacionDTO $dto): void
    {
        // Eliminar fotos específicamente marcadas para eliminar
        $fotosAEliminar = $dto->fotosAEliminar;
        if (!empty($fotosAEliminar)) {
            Log::info('Eliminando fotos marcadas', ['fotos_count' => count($fotosAEliminar)]);

            foreach ($fotosAEliminar as $rutaFoto) {
                // Eliminar del almacenamiento
                $rutaRelativa = str_replace('/storage/', '', $rutaFoto);

                // Eliminar de storage/app/public
                if (Storage::disk('public')->exists($rutaRelativa)) {
                    Storage::disk('public')->delete($rutaRelativa);
                    Log::info('Archivo eliminado de storage/app/public', ['ruta' => $rutaRelativa]);
                }

                // Eliminar de public/storage
                $rutaPublica = public_path("storage/{$rutaRelativa}");
                if (file_exists($rutaPublica)) {
                    @unlink($rutaPublica);
                    Log::info('Archivo eliminado de public/storage', ['ruta' => $rutaPublica]);
                }

                // Eliminar registro de la base de datos
                \App\Models\PrendaFotoCot::where('ruta_original', $rutaFoto)
                    ->orWhere('ruta_webp', $rutaFoto)
                    ->delete();

                \App\Models\PrendaTelaFotoCot::where('ruta_original', $rutaFoto)
                    ->orWhere('ruta_webp', $rutaFoto)
                    ->delete();

                Log::info('Registro de foto eliminado de la base de datos', ['ruta' => $rutaFoto]);
            }
        }

        // Procesar prendas y eliminar imágenes no incluidas SOLO si se envían nuevas imágenes
        foreach ($dto->prendasRecibidas as $index => $prendaData) {
            $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)
                ->skip($index)
                ->first();

            if ($prendaModel) {
                // Solo eliminar fotos antiguas si se enviaron nuevas fotos
                if (($dto->hayFotosPrendaNuevasPorIndex[(int) $index] ?? false) === true) {
                    $fotosActuales = $prendaData['fotos'] ?? [];
                    $this->eliminarImagenesService->eliminarImagenesPrendaNoIncluidas(
                        $prendaModel->id,
                        $fotosActuales
                    );
                }

                // Solo eliminar fotos de tela antiguas si se enviaron nuevas fotos de tela
                if (($dto->hayFotosTelaNuevasPorIndex[(int) $index] ?? false) === true) {
                    $telasActuales = $prendaData['telas'] ?? [];
                    $this->eliminarImagenesService->eliminarImagenesTelaNoIncluidas(
                        $prendaModel->id,
                        $telasActuales
                    );
                }
            }
        }

        // Procesar logo ANTES de procesar nuevas imágenes para que la eliminación funcione correctamente
        // NOTA: NO actualizamos aquí, lo hacemos en procesarImagenesCotizacion() para evitar conflictos
        $logoCotizacion = $cotizacion->logoCotizacion;
        if ($logoCotizacion) {
            // Obtener las fotos guardadas que se envían desde el frontend
            // Pueden venir como array: logo_fotos_guardadas[]
            $fotosLogoGuardadas = $dto->logoFotosGuardadas;

            // Limpiar rutas: remover /storage/ del principio si existe
            $fotosLogoGuardadas = array_map(function ($ruta) {
                // Si empieza con /storage/, dejarlo como está (comparar con ruta_webp/ruta_original en BD)
                // Si empieza con http, extraer la parte después de /storage/
                if (strpos($ruta, 'http') === 0) {
                    // Es una URL completa como http://localhost/storage/cotizaciones/1/logo/...
                    if (preg_match('#/storage/(.+)$#', $ruta, $matches)) {
                        return '/storage/' . $matches[1];
                    }
                }
                return $ruta;
            }, $fotosLogoGuardadas);

            Log::info('DEBUG - Fotos de logo a conservar (procesadas):', [
                'logo_id' => $logoCotizacion->id,
                'fotos_guardadas_count' => count($fotosLogoGuardadas),
                'fotos_guardadas' => $fotosLogoGuardadas,
                'raw_input' => $dto->logoFotosGuardadas,
            ]);

            Log::info('DEBUG - Archivos nuevos de logo:', [
                'logo_id' => $logoCotizacion->id,
                'archivos_nuevos_count' => $dto->logoArchivosNuevosCount,
            ]);

            // SIEMPRE ejecutar eliminación, pasando las fotos a conservar
            // El servicio decide cuáles eliminar basándose en la lista de fotos a conservar
            $this->eliminarImagenesService->eliminarImagenesLogoNoIncluidas(
                $logoCotizacion->id,
                $fotosLogoGuardadas
            );
        }
    }
}
