<?php

namespace App\Application\Services;

use App\Models\LogoCotizacionTecnicaPrendaFoto;
use App\Models\PrendaFotoCot;
use App\Models\PrendaTelaFotoCot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EliminarImagenesCotizacionService
{
    /**
     * Eliminar imágenes de prenda que no están en la lista actual
     */
    public function eliminarImagenesPrendaNoIncluidas(int $prendaId, array $fotosActuales): void
    {
        try {
            // Obtener todas las fotos guardadas de la prenda
            $fotosGuardadas = PrendaFotoCot::where('prenda_cot_id', $prendaId)->get();

            foreach ($fotosGuardadas as $fotoGuardada) {
                // Verificar si esta foto está en la lista actual
                $estaEnLista = collect($fotosActuales)->contains(function ($fotoActual) use ($fotoGuardada) {
                    // Comparar por ruta o ID
                    return $fotoActual === $fotoGuardada->ruta_original || 
                           $fotoActual === $fotoGuardada->ruta_webp ||
                           (is_array($fotoActual) && ($fotoActual['ruta'] === $fotoGuardada->ruta_original || $fotoActual['id'] === $fotoGuardada->id));
                });

                // Si no está en la lista, eliminarla
                if (!$estaEnLista) {
                    $this->eliminarFoto($fotoGuardada->ruta_original);
                    $fotoGuardada->delete();
                    
                    Log::info('Foto de prenda eliminada', [
                        'prenda_id' => $prendaId,
                        'foto_id' => $fotoGuardada->id,
                        'ruta' => $fotoGuardada->ruta_original
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al eliminar imágenes de prenda', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar imágenes de tela que no están en la lista actual
     */
    public function eliminarImagenesTelaNoIncluidas(int $prendaId, array $telasActuales): void
    {
        try {
            // Obtener todas las fotos de tela guardadas
            $telasGuardadas = PrendaTelaFotoCot::where('prenda_cot_id', $prendaId)->get();

            foreach ($telasGuardadas as $telaGuardada) {
                // Verificar si esta foto está en la lista actual
                $estaEnLista = collect($telasActuales)->contains(function ($telaActual) use ($telaGuardada) {
                    return $telaActual === $telaGuardada->ruta_original || 
                           $telaActual === $telaGuardada->ruta_webp ||
                           (is_array($telaActual) && ($telaActual['ruta'] === $telaGuardada->ruta_original || $telaActual['id'] === $telaGuardada->id));
                });

                // Si no está en la lista, eliminarla
                if (!$estaEnLista) {
                    $this->eliminarFoto($telaGuardada->ruta_original);
                    $telaGuardada->delete();
                    
                    Log::info('Foto de tela eliminada', [
                        'prenda_id' => $prendaId,
                        'foto_id' => $telaGuardada->id,
                        'ruta' => $telaGuardada->ruta_original
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al eliminar imágenes de tela', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar fotos del logo técnico que no estén en la lista actual.
     *
     * Se usa al actualizar borradores combinados para sincronizar el paso 3.
     */
    public function eliminarImagenesLogoNoIncluidas(int $logoCotizacionId, array $fotosActuales): void
    {
        try {
            $normalizarRuta = function (?string $ruta): ?string {
                if (!is_string($ruta) || trim($ruta) === '') {
                    return null;
                }

                $ruta = trim($ruta);

                if (strpos($ruta, 'http') === 0 && preg_match('#/storage/(.+)$#', $ruta, $matches)) {
                    $ruta = $matches[1];
                }

                if (strpos($ruta, '/storage/') === 0) {
                    $ruta = substr($ruta, 9);
                }

                if (strpos($ruta, 'storage/') === 0) {
                    $ruta = substr($ruta, 8);
                }

                return ltrim($ruta, '/');
            };

            $fotosGuardadas = LogoCotizacionTecnicaPrendaFoto::whereHas('prenda', function ($query) use ($logoCotizacionId) {
                $query->where('logo_cotizacion_id', $logoCotizacionId);
            })->get();

            $fotosActualesNormalizadas = collect($fotosActuales)
                ->map(fn ($foto) => $normalizarRuta(is_string($foto) ? $foto : null))
                ->filter(fn ($foto) => is_string($foto) && trim($foto) !== '')
                ->values()
                ->all();

            foreach ($fotosGuardadas as $fotoGuardada) {
                $estaEnLista = collect($fotosActualesNormalizadas)->contains(function ($fotoActual) use ($fotoGuardada, $normalizarRuta) {
                    $rutasFotoGuardada = array_filter([
                        $fotoGuardada->ruta_original,
                        $fotoGuardada->ruta_webp,
                        $fotoGuardada->ruta_miniatura,
                    ], fn ($v) => is_string($v) && trim($v) !== '');

                    foreach ($rutasFotoGuardada as $rutaGuardada) {
                        $rutaGuardadaNormalizada = $normalizarRuta($rutaGuardada);

                        if ($fotoActual === $rutaGuardadaNormalizada) {
                            return true;
                        }
                    }

                    return false;
                });

                if ($estaEnLista) {
                    continue;
                }

                $this->eliminarFoto($fotoGuardada->ruta_original ?: ($fotoGuardada->ruta_webp ?: $fotoGuardada->ruta_miniatura));
                $fotoGuardada->delete();

                Log::info('Foto de logo técnico eliminada', [
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'foto_id' => $fotoGuardada->id,
                    'ruta' => $fotoGuardada->ruta_original,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al eliminar imágenes de logo técnico', [
                'logo_cotizacion_id' => $logoCotizacionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Eliminar archivo de foto del almacenamiento
     */
    private function eliminarFoto(string $ruta): void
    {
        try {
            // Extraer la ruta relativa sin el prefijo /storage/
            $rutaRelativa = str_replace('/storage/', '', $ruta);
            $rutaRelativa = str_replace('/storage-serve/', '', $rutaRelativa);

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
        } catch (\Exception $e) {
            Log::error('Error al eliminar archivo de foto', [
                'ruta' => $ruta,
                'error' => $e->getMessage()
            ]);
        }
    }
}
