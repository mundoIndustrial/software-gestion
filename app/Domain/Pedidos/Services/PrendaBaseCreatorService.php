<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

/**
 * PrendaBaseCreatorService
 * 
 * Responsabilidad: Crear prenda base en BD
 * - Crear registro en tabla prenda_pedido
 * - Mantener logs exactamente igual
 * - Verificar que la prenda se guardó correctamente
 * 
 * NO TOCAR - Mantener logs exactamente igual
 */
class PrendaBaseCreatorService
{
    /**
     * Crear prenda base en BD
     * 
     * Retorna la prenda creada
     */
    public function crearPrendaBase(
        int $pedidoId,
        array $prendaData,
        array $cantidadTallaFinal,
        array $generoProcesado,
        int $index
    ): PrendaPedido {
        $descripcionFinal = $prendaData['descripcion'] ?? '';
        
        \Log::info("ðŸ”µ [PRENDA #{$index}] ANTES DE CREATE - Creando nueva prenda", [
            'pedido_id' => $pedidoId,
            'nombre_producto' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'indice_prenda' => $index,
            'cantidad_prendas_actuales' => PrendaPedido::where('pedido_produccion_id', $pedidoId)->count(),
        ]);

        // Crear prenda SIN cantidad_talla y genero (se guardarÃ¡n en tabla relacional)
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedidoId,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'descripcion' => $descripcionFinal,
            'de_bodega' => (int)($prendaData['de_bodega'] ?? 1),
        ]);

        // Guardar tallas en tabla relacional prenda_pedido_tallas
        if (!empty($cantidadTallaFinal)) {
            try {
                $tallaRepository = new \App\Domain\Pedidos\Repositories\PedidoProduccionRepository();
                $tallaRepository->guardarTallas($prenda->id, $cantidadTallaFinal);
                
                \Log::info("âœ… [PRENDA #{$index}] Tallas guardadas en tabla relacional", [
                    'prenda_id' => $prenda->id,
                    'cantidad_tallas' => count($cantidadTallaFinal),
                    'generos' => array_keys($cantidadTallaFinal),
                ]);
            } catch (\Exception $e) {
                \Log::error("âŒ [PRENDA #{$index}] Error guardando tallas relacionales", [
                    'prenda_id' => $prenda->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        \Log::info(" [PRENDA #{$index}] DESPUÃ‰S DE CREATE - Prenda creada", [
            'prenda_id_nueva' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'pedido_id' => $prenda->pedido_produccion_id,
            'cantidad_prendas_ahora' => PrendaPedido::where('pedido_produccion_id', $pedidoId)->count(),
        ]);

        $prendaVerificacion = PrendaPedido::with('tallas')->find($prenda->id);
        \Log::info(' VERIFICACIÃ“N POST-GUARDADO DE PRENDA (prenda #' . $index . '):', [
            'prenda_id_creada' => $prenda->id,
            'prenda_existe_en_bd' => $prendaVerificacion ? true : false,
            'prenda_id_verificado' => $prendaVerificacion->id ?? 'NO ENCONTRADA',
            'nombre_guardado' => $prendaVerificacion->nombre_prenda ?? 'NO ENCONTRADA',
            'tallas_guardadas' => $prendaVerificacion->tallas ? $prendaVerificacion->tallas->count() : 0,
            'pedido_id_referencia' => $prendaVerificacion->pedido_produccion_id ?? 'NO ENCONTRADA',
        ]);

        return $prenda;
    }
}

