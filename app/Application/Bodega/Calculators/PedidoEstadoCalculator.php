<?php

namespace App\Application\Bodega\Calculators;

use App\Application\Bodega\Constants\WarehouseConstants;
use App\Models\BodegaDetallesTalla;

/**
 * Calculadora de estados de pedidos
 * 
 * Centraliza la lógica de cálculo de estados para evitar duplicación
 * de código en múltiples métodos del servicio.
 */
class PedidoEstadoCalculator
{
    /**
     * Calcular el estado de un pedido basado en sus items en bodega
     * 
     * @param string $numeroPedido
     * @return array
     */
    public function calcular(string $numeroPedido): array
    {
        $totalItemsReales = $this->obtenerTotalItemsReales($numeroPedido);
        $itemsPendientes = $this->obtenerItemsPendientes($numeroPedido);
        $itemsEntregados = $this->obtenerItemsEntregados($numeroPedido);
        
        return [
            'total_items' => $totalItemsReales,
            'items_pendientes' => $itemsPendientes,
            'items_entregados' => $itemsEntregados,
            'tiene_pendientes' => $itemsPendientes > 0,
            'todos_pendientes' => $this->todosPendientes($totalItemsReales, $itemsPendientes),
            'todos_entregados' => $this->todosEntregados($totalItemsReales, $itemsEntregados),
        ];
    }

    /**
     * Obtener total de items REALES del pedido original
     * Cuenta todos los items que deberían existir según el pedido original
     */
    private function obtenerTotalItemsReales(string $numeroPedido): int
    {
        try {
            // Método 1: Intentar obtener desde recibos usando el use case
            try {
                $recibos = \App\Models\ReciboPrenda::where('numero_pedido', $numeroPedido)->get();
                $totalItems = 0;
                
                foreach ($recibos as $recibo) {
                    try {
                        $obtenerPedidoUseCase = app(\App\Application\Bodega\UseCases\ObtenerPedidoUseCase::class);
                        $datosCompletos = $obtenerPedidoUseCase->ejecutar($recibo->id);
                        
                        // Contar prendas
                        if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                            foreach ($datosCompletos->prendas as $prenda) {
                                $variantes = $prenda['variantes'] ?? [];
                                foreach ($variantes as $variante) {
                                    $coloresDetalle = $variante['colores_detalle'] ?? [];
                                    foreach ($coloresDetalle as $color) {
                                        $cantidad = (int)($color['cantidad'] ?? 0);
                                        if ($cantidad > 0) {
                                            $totalItems += $cantidad;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Contar EPPs
                        if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                            foreach ($datosCompletos->epps as $epp) {
                                $cantidad = (int)($epp['cantidad'] ?? 0);
                                if ($cantidad > 0) {
                                    $totalItems += $cantidad;
                                }
                            }
                        }
                        
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                if ($totalItems > 0) {
                    return $totalItems;
                }
            } catch (\Exception $e) {
                // Continuar con método 2
            }
            
            // Método 2: Contar desde variantes de prendas (más directo)
            try {
                $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
                if ($pedido) {
                    $variantes = \App\Models\PrendaVariantePed::where('pedido_produccion_id', $pedido->id)->get();
                    $totalItems = 0;
                    
                    foreach ($variantes as $variante) {
                        // Obtener tallas desde la relación
                        $tallas = \App\Models\TallaPrendaCot::where('prenda_pedido_id', $variante->prenda_pedido_id)->get();
                        foreach ($tallas as $talla) {
                            $cantidades = json_decode($talla->cantidad, true);
                            if (is_array($cantidades)) {
                                foreach ($cantidades as $cantidad) {
                                    if ($cantidad > 0) {
                                        $totalItems += $cantidad;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($totalItems > 0) {
                        return $totalItems;
                    }
                }
            } catch (\Exception $e) {
                // Continuar con fallback
            }
            
        } catch (\Exception $e) {
            \Log::error('[PedidoEstadoCalculator] Error al obtener total de items reales', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback: usar el método original (solo items existentes en bodega_detalles_talla)
        return $this->obtenerTotalItems($numeroPedido);
    }

    /**
     * Obtener total de items del pedido (excluyendo anulados)
     * Solo cuenta items que NO están en estado "Anulada"
     */
    private function obtenerTotalItems(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', '!=', WarehouseConstants::STATE_CANCELLED)
            ->count();
    }

    /**
     * Obtener cantidad de items pendientes
     */
    private function obtenerItemsPendientes(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_PENDING)
            ->count();
    }

    /**
     * Obtener cantidad de items entregados
     */
    private function obtenerItemsEntregados(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_DELIVERED)
            ->count();
    }

    /**
     * Verificar si TODOS los items están pendientes
     * 
     * Retorna true SOLO si:
     * - Hay al menos 1 item real del pedido (total > 0)
     * - TODOS los items reales están en estado "Pendiente" en bodega_detalles_talla
     * - No hay items en otros estados (Entregado, En Progreso, etc.)
     */
    private function todosPendientes(int $totalItems, int $itemsPendientes): bool
    {
        return $totalItems > 0 && $totalItems === $itemsPendientes;
    }

    /**
     * Verificar si TODOS los items están entregados
     * 
     * Retorna true SOLO si:
     * - Hay al menos 1 item real del pedido (total > 0)
     * - TODOS los items reales están en estado "Entregado" en bodega_detalles_talla
     * - No hay items en otros estados (Pendiente, En Progreso, etc.)
     */
    private function todosEntregados(int $totalItems, int $itemsEntregados): bool
    {
        return $totalItems > 0 && $totalItems === $itemsEntregados;
    }

    /**
     * Verificar si existe al menos un item pendiente
     */
    public function existePendiente(string $numeroPedido): bool
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_PENDING)
            ->exists();
    }
}
