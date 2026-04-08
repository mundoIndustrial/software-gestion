<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Services\ColorTelaService;
use App\Models\ColorPrenda;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoColorTela;
use App\Models\TelaPrenda;
use Illuminate\Support\Facades\Log;

class PedidoTelaBuilder
{
    public function __construct(
        private ColorTelaService $colorTelaService,
    ) {}

    public function crearColoresTelas(PrendaPedido $prenda, array $coloresTelas): void
    {
        foreach ($coloresTelas as $colorTela) {
            PrendaPedidoColorTela::create([
                'prenda_pedido_id' => $prenda->id,
                'color_id' => $colorTela['color_id'] ?? null,
                'tela_id' => $colorTela['tela_id'] ?? null,
                'referencia' => $colorTela['referencia'] ?? null,
                'observaciones' => $colorTela['observaciones'] ?? null,
            ]);
        }
    }

    public function crearDesdeFormulario(PrendaPedido $prenda, array $telas, array $fotosTelaRutas = []): void
    {
        Log::info('[PedidoTelaBuilder] crearDesdeFormulario INICIADA', [
            'prenda_id' => $prenda->id,
            'telas_count' => count($telas),
        ]);

        $telasCreadasCount = 0;

        foreach ($telas as $telaIdx => $telaData) {
            if (isset($telaData['tela_id'], $telaData['color_id']) &&
                $telaData['tela_id'] > 0 && $telaData['color_id'] > 0) {
                $colorTela = PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prenda->id,
                    'color_id' => $telaData['color_id'],
                    'tela_id' => $telaData['tela_id'],
                    'referencia' => $telaData['referencia'] ?? null,
                    'observaciones' => $telaData['observaciones'] ?? null,
                ]);
                $telasCreadasCount++;
                $this->guardarFotoTela($colorTela, $telaIdx, $fotosTelaRutas);
                continue;
            }

            [$telaId, $colorId] = $this->resolverIdsTelaYColor($telaData);

            $telaNombreGeneral = $telaData['nombre'] ?? $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
            if (!$telaId && !empty($telaNombreGeneral)) {
                $telaExistente = TelaPrenda::where('nombre', $telaNombreGeneral)
                    ->where('activo', true)
                    ->first();

                if ($telaExistente) {
                    $telaId = $telaExistente->id;
                } else {
                    $telaPorDefecto = TelaPrenda::create([
                        'nombre' => $telaNombreGeneral ?: 'Tela Generica',
                        'referencia' => 'GEN-' . time(),
                        'descripcion' => 'Tela creada automaticamente',
                        'activo' => true,
                    ]);
                    $telaId = $telaPorDefecto->id;
                }
            }

            if ($telaId || $colorId) {
                $colorTela = PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prenda->id,
                    'color_id' => $colorId ?? null,
                    'tela_id' => $telaId ?? null,
                    'referencia' => $telaData['referencia'] ?? null,
                    'observaciones' => $telaData['observaciones'] ?? null,
                ]);
                $telasCreadasCount++;
                $this->guardarFotoTela($colorTela, $telaIdx, $fotosTelaRutas);
            }
        }

        Log::info('[PedidoTelaBuilder] crearDesdeFormulario TERMINADA', [
            'prenda_id' => $prenda->id,
            'telas_creadas' => $telasCreadasCount,
        ]);
    }

    private function resolverIdsTelaYColor(array $telaData): array
    {
        $telaId = null;
        $colorId = null;

        try {
            $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
            if ($telaNombre) {
                $telaId = $this->colorTelaService->obtenerOCrearTela($telaNombre);
            }

            $colorNombre = $telaData['color'] ?? $telaData['color_nombre'] ?? null;
            if ($colorNombre && !empty($colorNombre)) {
                $colorId = $this->colorTelaService->obtenerOCrearColor($colorNombre);
            }
        } catch (\Exception $e) {
            Log::warning('[PedidoTelaBuilder] ColorTelaService fallo, usando fallback', [
                'error' => $e->getMessage(),
            ]);

            try {
                $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                if ($telaNombre && !$telaId) {
                    $tela = TelaPrenda::where('nombre', $telaNombre)->first();
                    if ($tela) {
                        $telaId = $tela->id;
                    }
                }

                $colorNombre = $telaData['color'] ?? $telaData['color_nombre'] ?? null;
                if ($colorNombre && !empty($colorNombre) && !$colorId) {
                    $color = ColorPrenda::where('nombre', $colorNombre)->first();
                    if ($color) {
                        $colorId = $color->id;
                    }
                }
            } catch (\Exception $fallbackError) {
                Log::error('[PedidoTelaBuilder] Error en fallback', [
                    'error' => $fallbackError->getMessage(),
                ]);
            }
        }

        return [$telaId, $colorId];
    }

    private function guardarFotoTela(PrendaPedidoColorTela $colorTela, int $telaIdx, array $fotosTelaRutas): void
    {
        if (!empty($fotosTelaRutas) && isset($fotosTelaRutas[$telaIdx])) {
            $rutasTela = $fotosTelaRutas[$telaIdx];
            PrendaFotoTelaPedido::create([
                'prenda_pedido_colores_telas_id' => $colorTela->id,
                'ruta_original' => $rutasTela['ruta_original'] ?? null,
                'ruta_webp' => $rutasTela['ruta_webp'] ?? $rutasTela['ruta_original'] ?? null,
                'orden' => 1,
            ]);
        }
    }
}
