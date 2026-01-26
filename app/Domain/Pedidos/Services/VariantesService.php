<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\DB;
use App\Application\Services\ColorTelaService;

/**
 * Servicio para herencia de variantes de cotización a pedido
 * Responsabilidad: Mapear datos de variantes de cotización a prenda de pedido
 */
class VariantesService
{
    private ColorTelaService $colorTelaService;

    public function __construct(ColorTelaService $colorTelaService)
    {
        $this->colorTelaService = $colorTelaService;
    }

    /**
     * Heredar variantes de una prenda de cotización a pedido
     */
    public function heredarVariantesDePrenda($cotizacion, $prendaPedido, int $index): void
    {
        try {
            \Log::info(' [heredarVariantes] Iniciando herencia de variantes', [
                'cotizacion_id' => $cotizacion->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'index' => $index,
            ]);

            // Obtener prendas de cotización
            $prendasCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)
                ->orderBy('id')
                ->get();
            
            if (!isset($prendasCot[$index])) {
                \Log::warning(' No se encontró prenda de cotización en Ã­ndice', [
                    'index' => $index,
                    'total_prendas_cot' => $prendasCot->count()
                ]);
                return;
            }
            
            $prendaCot = $prendasCot[$index];
            
            // Obtener variantes
            $variantes = DB::table('prenda_variantes_cot')
                ->where('prenda_cot_id', $prendaCot->id)
                ->get();
            
            if ($variantes->isEmpty()) {
                // Usar datos de la prenda directamente
                $prendaPedido->update([
                    'color_id' => $prendaCot->color_id,
                    'tela_id' => $prendaCot->tela_id,
                    'tipo_manga_id' => $prendaCot->tipo_manga_id,
                    'tipo_broche_id' => $prendaCot->tipo_broche_id,
                    'tiene_bolsillos' => $prendaCot->tiene_bolsillos ?? 0,
                    'tiene_reflectivo' => $prendaCot->tiene_reflectivo ?? 0,
                ]);
                return;
            }
            
            // Copiar primera variante
            $variante = $variantes->first();
            
            $colorId = $this->colorTelaService->obtenerOCrearColor($variante->color);
            $telaId = $this->obtenerTelaDeVariante($variante->telas_multiples ?? null);
            
            $prendaPedido->update([
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
                'tiene_bolsillos' => $variante->tiene_bolsillos ?? 0,
                'tiene_reflectivo' => $variante->tiene_reflectivo ?? 0,
                'descripcion_variaciones' => $variante->descripcion_adicional ?? null,
            ]);
            
            \Log::info(' Variantes heredadas exitosamente', [
                'prenda_pedido_id' => $prendaPedido->id,
                'color_id' => $colorId,
                'tela_id' => $telaId,
            ]);
            
        } catch (\Exception $e) {
            \Log::error(' Error heredando variantes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtener tela desde JSON de telas mÃºltiples
     * Extrae la primera tela del JSON y obtiene/crea la combinación
     */
    private function obtenerTelaDeVariante(?string $telasJson): ?int
    {
        if (empty($telasJson)) {
            return null;
        }

        $telasMultiples = json_decode($telasJson, true);
        if (!is_array($telasMultiples) || empty($telasMultiples)) {
            return null;
        }

        $primeraTela = $telasMultiples[0];
        if (empty($primeraTela['tela'] ?? null)) {
            return null;
        }

        return $this->colorTelaService->obtenerOCrearTela($primeraTela['tela']);
    }
}

