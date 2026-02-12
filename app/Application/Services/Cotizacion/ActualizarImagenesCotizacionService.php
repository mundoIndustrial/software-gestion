<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ActualizarImagenesCotizacionService
{
    public function __construct(
        private readonly \App\Application\Services\EliminarImagenesCotizacionService $eliminarImagenesService,
    ) {
    }

    public function ejecutar(Cotizacion $cotizacion, Request $request, array $prendasRecibidas): void
    {
        // Eliminar fotos específicamente marcadas para eliminar
        $fotosAEliminar = $request->input('fotos_a_eliminar', []);
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
        $allFiles = $request->allFiles();

        foreach ($prendasRecibidas as $index => $prendaData) {
            $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)
                ->skip($index)
                ->first();

            if ($prendaModel) {
                // Verificar si se enviaron nuevas fotos de prenda para esta prenda
                $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];
                if (empty($fotosArchivos)) {
                    $fotosArchivos = $allFiles["prendas.{$index}.fotos"] ?? [];
                }

                // Solo eliminar fotos antiguas si se enviaron nuevas fotos
                if (!empty($fotosArchivos)) {
                    $fotosActuales = $prendaData['fotos'] ?? [];
                    $this->eliminarImagenesService->eliminarImagenesPrendaNoIncluidas(
                        $prendaModel->id,
                        $fotosActuales
                    );
                }

                // Verificar si se enviaron nuevas fotos de tela para esta prenda
                $telasArchivos = $request->file("prendas.{$index}.telas") ?? [];
                if (empty($telasArchivos)) {
                    $telasArchivos = $allFiles["prendas.{$index}.telas"] ?? [];
                }

                // Solo eliminar fotos de tela antiguas si se enviaron nuevas fotos de tela
                if (!empty($telasArchivos)) {
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
            $fotosLogoGuardadas = $request->input('logo_fotos_guardadas', []);
            if (!is_array($fotosLogoGuardadas)) {
                $fotosLogoGuardadas = $fotosLogoGuardadas ? [$fotosLogoGuardadas] : [];
            }

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
                'raw_input' => $request->input('logo_fotos_guardadas', []),
            ]);

            // Obtener archivos nuevos para saber si se están enviando archivos
            $archivosNuevos = $request->file('logo.imagenes') ?? [];
            $allFiles = $request->allFiles();
            if (empty($archivosNuevos) && isset($allFiles['logo']['imagenes'])) {
                $archivosNuevos = $allFiles['logo']['imagenes'];
            }
            if ($archivosNuevos instanceof \Illuminate\Http\UploadedFile) {
                $archivosNuevos = [$archivosNuevos];
            }

            Log::info('DEBUG - Archivos nuevos de logo:', [
                'logo_id' => $logoCotizacion->id,
                'archivos_nuevos_count' => count($archivosNuevos),
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
