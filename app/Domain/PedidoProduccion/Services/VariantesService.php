<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Support\Facades\DB;

/**
 * Servicio para herencia de variantes de cotización a pedido
 * Responsabilidad: Mapear datos de variantes de cotización a prenda de pedido
 */
class VariantesService
{
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
                \Log::warning(' No se encontró prenda de cotización en índice', [
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
            
            $colorId = $this->obtenerOCrearColor($variante->color);
            $telaId = $this->obtenerOCrearTela($variante->telas_multiples ?? null);
            
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
     * Obtener o crear color
     */
    private function obtenerOCrearColor(?string $nombreColor): ?int
    {
        if (empty($nombreColor)) {
            return null;
        }

        $color = DB::table('colores_prenda')
            ->where('nombre', 'LIKE', '%' . $nombreColor . '%')
            ->first();
        
        if ($color) {
            return $color->id;
        }

        return DB::table('colores_prenda')->insertGetId([
            'nombre' => $nombreColor,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtener o crear tela desde JSON de telas múltiples
     */
    private function obtenerOCrearTela(?string $telasJson): ?int
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

        $tela = DB::table('telas_prenda')
            ->where('nombre', 'LIKE', '%' . $primeraTela['tela'] . '%')
            ->first();
        
        if ($tela) {
            return $tela->id;
        }

        return DB::table('telas_prenda')->insertGetId([
            'nombre' => $primeraTela['tela'],
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
