<?php

namespace App\Domain\PedidoProduccion\Services;

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
        
        \Log::info("🔵 [PRENDA #{$index}] ANTES DE CREATE - Creando nueva prenda", [
            'pedido_id' => $pedidoId,
            'nombre_producto' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'indice_prenda' => $index,
            'cantidad_prendas_actuales' => PrendaPedido::where('pedido_produccion_id', $pedidoId)->count(),
        ]);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedidoId,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'descripcion' => $descripcionFinal,
            'cantidad_talla' => !empty($cantidadTallaFinal) ? json_encode($cantidadTallaFinal) : '{}',
            'genero' => json_encode($generoProcesado),
            'de_bodega' => (int)($prendaData['de_bodega'] ?? 1),
        ]);

        \Log::info(" [PRENDA #{$index}] DESPUÉS DE CREATE - Prenda creada", [
            'prenda_id_nueva' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'pedido_id' => $prenda->pedido_produccion_id,
            'cantidad_prendas_ahora' => PrendaPedido::where('pedido_produccion_id', $pedidoId)->count(),
        ]);

        $prendaVerificacion = PrendaPedido::find($prenda->id);
        \Log::info(' VERIFICACIÓN POST-GUARDADO DE PRENDA (prenda #' . $index . '):', [
            'prenda_id_creada' => $prenda->id,
            'prenda_existe_en_bd' => $prendaVerificacion ? true : false,
            'prenda_id_verificado' => $prendaVerificacion->id ?? 'NO ENCONTRADA',
            'nombre_guardado' => $prendaVerificacion->nombre_prenda ?? 'NO ENCONTRADA',
            'cantidad_talla_guardado' => $prendaVerificacion->cantidad_talla ?? 'NO ENCONTRADA',
            'pedido_id_referencia' => $prendaVerificacion->pedido_produccion_id ?? 'NO ENCONTRADA',
        ]);

        return $prenda;
    }
}
