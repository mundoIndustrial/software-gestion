<?php

namespace App\Application\Services\Cotizacion;

use Illuminate\Http\Request;

final class ProcesarImagenesCotizacionAppService
{
    public function __construct(
        private readonly \App\Application\Services\ProcesarImagenesCotizacionService $procesarImagenesService,
    ) {
    }

    public function ejecutar(Request $request, int $cotizacionId): void
    {
        try {
            $allData = $request->all();
            $prendas = $allData['prendas'] ?? $request->input('prendas', []);
            $allFiles = $request->allFiles();

            $cotizacionExistente = \App\Models\Cotizacion::find($cotizacionId);
            $esUpdate = !!$cotizacionExistente;

            foreach ($prendas as $index => $prenda) {
                $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->skip($index)
                    ->first();

                if (!$prendaModel) {
                    continue;
                }

                $ordenFotosPrenda = ($prendaModel->fotos()->max('orden') ?? 0) + 1;

                $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];
                if (empty($fotosArchivos)) {
                    $fotosArchivos = $request->file("prendas.{$index}.fotos.0") ?? [];
                }
                if (empty($fotosArchivos)) {
                    $fotosArchivos = $allFiles["prendas.{$index}.fotos"] ?? [];
                }

                if ($fotosArchivos instanceof \Illuminate\Http\UploadedFile) {
                    $fotosArchivos = [$fotosArchivos];
                } elseif (!is_array($fotosArchivos)) {
                    $fotosArchivos = [];
                }

                if (!empty($fotosArchivos)) {
                    $orden = $ordenFotosPrenda;
                    foreach ($fotosArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenPrenda(
                                $foto,
                                $cotizacionId,
                                $prendaModel->id
                            );

                            $prendaModel->fotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => $orden,
                            ]);
                            $orden++;
                        }
                    }
                }

                $fotosGuardadas = $request->input("prendas.{$index}.fotos_guardadas") ?? [];
                if (!is_array($fotosGuardadas)) {
                    $fotosGuardadas = [];
                }

                if (!empty($fotosGuardadas)) {
                    $orden = max($ordenFotosPrenda, count($fotosArchivos) + $ordenFotosPrenda);
                    foreach ($fotosGuardadas as $rutaGuardada) {
                        if ($rutaGuardada && is_string($rutaGuardada)) {
                            $rutaLimpia = $rutaGuardada;
                            if (strpos($rutaLimpia, '/storage/') === 0) {
                                $rutaLimpia = substr($rutaLimpia, 9);
                            }

                            $prendaModel->fotos()->create([
                                'ruta_original' => $rutaLimpia,
                                'ruta_webp' => $rutaLimpia,
                                'orden' => $orden,
                            ]);
                            $orden++;
                        }
                    }
                }

                // Por ahora: solo movemos el procesamiento de fotos de prenda.
                // (El resto de telas/logo/técnicas permanece en el controller hasta la siguiente iteración.)
            }
        } catch (\Exception $e) {
            // Mantener el comportamiento actual (no lanzar), para no romper update/store.
            \Illuminate\Support\Facades\Log::error('ProcesarImagenesCotizacionAppService: Error', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
