<?php

namespace App\Application\Services\Cotizacion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Http\Mappers\SyncLogoTecnicasRequestMapper;
use App\Infrastructure\Http\Mappers\ProcesarImagenesCotizacionRequestMapper;

final class ProcesarImagenesCotizacionOrchestratorService
{
    public function __construct(
        private readonly ProcesarImagenesCotizacionRequestService $procesarImagenesCotizacionRequestService,
        private readonly ProcesarLogoTecnicasCotizacionRequestService $procesarLogoTecnicasCotizacionRequestService,
        private readonly SyncLogoTecnicasRequestMapper $syncLogoTecnicasRequestMapper,
        private readonly ProcesarImagenesCotizacionRequestMapper $procesarImagenesCotizacionRequestMapper,
    ) {
    }

    public function ejecutar(Request $request, int $cotizacionId): void
    {
        $dtoPaso2 = $this->procesarImagenesCotizacionRequestMapper->map($request, $cotizacionId);
        $this->procesarImagenesCotizacionRequestService->ejecutar($dtoPaso2);

        $prendas = $dtoPaso2->prendas;

        // Fallback: copiar fotos existentes de telas cuando no se enviaron archivos nuevos
        try {
            foreach ($prendas as $index => $prenda) {
                $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->skip($index)
                    ->first();

                if (!$prendaModel) {
                    continue;
                }

                $todasLasTelas = DB::table('prenda_telas_cot')
                    ->where('prenda_cot_id', $prendaModel->id)
                    ->orderBy('created_at')
                    ->get();

                if ($todasLasTelas->isEmpty()) {
                    continue;
                }

                foreach ($todasLasTelas as $telaIndex => $telaCot) {
                    $yaTieneFotos = DB::table('prenda_tela_fotos_cot')
                        ->where('prenda_tela_cot_id', $telaCot->id)
                        ->exists();

                    if ($yaTieneFotos) {
                        continue;
                    }

                    $fotosAnteriores = DB::table('prenda_tela_fotos_cot as ptf')
                        ->join('prenda_telas_cot as ptc', 'ptf.prenda_tela_cot_id', '=', 'ptc.id')
                        ->where('ptc.color_id', $telaCot->color_id)
                        ->where('ptc.tela_id', $telaCot->tela_id)
                        ->whereNotNull('ptf.ruta_original')
                        ->select('ptf.*')
                        ->get();

                    if ($fotosAnteriores->isEmpty()) {
                        continue;
                    }

                    $orden = 1;
                    foreach ($fotosAnteriores as $fotoAnterior) {
                        $rutaAUsar = $fotoAnterior->ruta_original ?: $fotoAnterior->ruta_webp;

                        DB::table('prenda_tela_fotos_cot')->insert([
                            'prenda_cot_id' => $prendaModel->id,
                            'prenda_tela_cot_id' => $telaCot->id,
                            'tela_index' => $telaIndex,
                            'ruta_original' => $rutaAUsar,
                            'ruta_webp' => $rutaAUsar,
                            'ruta_miniatura' => $fotoAnterior->ruta_miniatura,
                            'orden' => $orden,
                            'ancho' => $fotoAnterior->ancho,
                            'alto' => $fotoAnterior->alto,
                            'tamaño' => $fotoAnterior->tamaño,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $orden++;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('ProcesarImagenesCotizacionOrchestratorService fallback error', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
            ]);
        }

        $dtoPaso3 = $this->syncLogoTecnicasRequestMapper->map($request, $cotizacionId);
        $this->procesarLogoTecnicasCotizacionRequestService->ejecutar($dtoPaso3);
    }
}
