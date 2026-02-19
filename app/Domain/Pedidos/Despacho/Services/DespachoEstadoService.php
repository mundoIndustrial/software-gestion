<?php

namespace App\Domain\Pedidos\Despacho\Services;

use App\Models\PedidoProduccion;
use App\Models\DesparChoParcialesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para verificar y gestionar el estado completo de despacho de pedidos
 * 
 * Responsabilidades:
 * - Verificar si todas las prendas y EPPs de un pedido están completamente despachadas
 * - Cambiar automáticamente el estado del pedido a "Entregado" cuando corresponda
 * - Proporcionar estadísticas de despacho
 */
class DespachoEstadoService
{
    /**
     * Verificar si un pedido está completamente despachado
     * 
     * @param int $pedidoId
     * @return bool
     */
    public function estaPedidoCompletamenteDespachado(int $pedidoId): bool
    {
        try {
            // Obtener todos los despachos parciales del pedido
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedidoId)
                ->activo()
                ->get();

            if ($despachos->isEmpty()) {
                Log::info('Pedido sin despachos registrados', [
                    'pedido_id' => $pedidoId
                ]);
                return false;
            }

            // Verificar que cada despacho parcial esté completo
            foreach ($despachos as $despacho) {
                if (!$despacho->entregado) {
                    Log::debug('Despacho no entregado encontrado', [
                        'pedido_id' => $pedidoId,
                        'despacho_id' => $despacho->id,
                        'tipo_item' => $despacho->tipo_item,
                        'item_id' => $despacho->item_id,
                        'total_despachado' => $despacho->totalDespachado(),
                        'pendiente_inicial' => $despacho->pendiente_inicial
                    ]);
                    return false;
                }
            }

            Log::info('Pedido completamente despachado verificado', [
                'pedido_id' => $pedidoId,
                'total_despachos' => $despachos->count()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error al verificar estado completo de despacho', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Cambiar estado del pedido a "Entregado" si está completamente despachado
     * 
     * @param int $pedidoId
     * @return bool True si se cambió el estado, false si no
     */
    public function cambiarEstadoAEntregadoSiCorresponde(int $pedidoId): bool
    {
        try {
            // Verificar si el pedido está completamente despachado
            if (!$this->estaPedidoCompletamenteDespachado($pedidoId)) {
                return false;
            }

            // Obtener el pedido
            $pedido = PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                Log::warning('Pedido no encontrado para cambio de estado', [
                    'pedido_id' => $pedidoId
                ]);
                return false;
            }

            // Verificar que el estado actual no sea ya "Entregado"
            if ($pedido->estado === 'Entregado') {
                Log::info('Pedido ya está en estado Entregado', [
                    'pedido_id' => $pedidoId,
                    'numero_pedido' => $pedido->numero_pedido
                ]);
                return false;
            }

            // Cambiar estado a "Entregado"
            $estadoAnterior = $pedido->estado;
            $pedido->estado = 'Entregado';
            $pedido->save();

            Log::info('Estado del pedido cambiado a Entregado automáticamente', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'Entregado',
                'fecha_cambio' => now()->toDateTimeString()
            ]);

            // TODO: Opcional - Disparar evento o notificación
            // event(new PedidoMarcadoComoEntregado($pedido));

            return true;

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido a Entregado', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Obtener estadísticas de despacho para un pedido
     * 
     * @param int $pedidoId
     * @return array
     */
    public function obtenerEstadisticasDespacho(int $pedidoId): array
    {
        try {
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedidoId)
                ->activo()
                ->get();

            $totalDespachos = $despachos->count();
            $despachosEntregados = $despachos->where('entregado', true)->count();
            $despachosPendientes = $totalDespachos - $despachosEntregados;

            $estadisticas = [
                'pedido_id' => $pedidoId,
                'total_items' => $totalDespachos,
                'items_entregados' => $despachosEntregados,
                'items_pendientes' => $despachosPendientes,
                'porcentaje_entregado' => $totalDespachos > 0 
                    ? round(($despachosEntregados / $totalDespachos) * 100, 2) 
                    : 0,
                'esta_completamente_despachado' => $despachosPendientes === 0,
                'detalle_por_tipo' => []
            ];

            // Agrupar por tipo de ítem
            $agrupadoPorTipo = $despachos->groupBy('tipo_item');
            foreach ($agrupadoPorTipo as $tipo => $items) {
                $entregadosPorTipo = $items->where('entregado', true)->count();
                $estadisticas['detalle_por_tipo'][$tipo] = [
                    'total' => $items->count(),
                    'entregados' => $entregadosPorTipo,
                    'pendientes' => $items->count() - $entregadosPorTipo,
                    'porcentaje_entregado' => round(($entregadosPorTipo / $items->count()) * 100, 2)
                ];
            }

            return $estadisticas;

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de despacho', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'pedido_id' => $pedidoId,
                'error' => 'No se pudieron obtener las estadísticas'
            ];
        }
    }

    /**
     * Verificar múltiples pedidos y cambiar estado de los que estén completos
     * 
     * @param array $pedidoIds
     * @return array Resultados del proceso
     */
    public function procesarMultiplesPedidos(array $pedidoIds): array
    {
        $resultados = [
            'total_procesados' => 0,
            'cambiados_a_entregado' => 0,
            'ya_estaban_entregados' => 0,
            'no_completos' => 0,
            'errores' => 0,
            'detalles' => []
        ];

        foreach ($pedidoIds as $pedidoId) {
            $resultados['total_procesados']++;
            
            try {
                $pedido = PedidoProduccion::find($pedidoId);
                if (!$pedido) {
                    $resultados['errores']++;
                    $resultados['detalles'][] = [
                        'pedido_id' => $pedidoId,
                        'estado' => 'error',
                        'mensaje' => 'Pedido no encontrado'
                    ];
                    continue;
                }

                if ($pedido->estado === 'Entregado') {
                    $resultados['ya_estaban_entregados']++;
                    $resultados['detalles'][] = [
                        'pedido_id' => $pedidoId,
                        'numero_pedido' => $pedido->numero_pedido,
                        'estado' => 'ya_entregado',
                        'mensaje' => 'Ya estaba en estado Entregado'
                    ];
                    continue;
                }

                $cambiado = $this->cambiarEstadoAEntregadoSiCorresponde($pedidoId);
                
                if ($cambiado) {
                    $resultados['cambiados_a_entregado']++;
                    $resultados['detalles'][] = [
                        'pedido_id' => $pedidoId,
                        'numero_pedido' => $pedido->numero_pedido,
                        'estado' => 'cambiado',
                        'mensaje' => 'Cambiado a Entregado automáticamente'
                    ];
                } else {
                    $resultados['no_completos']++;
                    $resultados['detalles'][] = [
                        'pedido_id' => $pedidoId,
                        'numero_pedido' => $pedido->numero_pedido,
                        'estado' => 'no_completo',
                        'mensaje' => 'No está completamente despachado'
                    ];
                }

            } catch (\Exception $e) {
                $resultados['errores']++;
                $resultados['detalles'][] = [
                    'pedido_id' => $pedidoId,
                    'estado' => 'error',
                    'mensaje' => $e->getMessage()
                ];
            }
        }

        Log::info('Proceso masivo de cambio de estado completado', $resultados);

        return $resultados;
    }

    /**
     * Cambiar estado del pedido a "Pendiente" si ya no hay items entregados
     * 
     * @param int $pedidoId
     * @return bool True si se cambió el estado, false si no
     */
    public function cambiarEstadoAPendienteSiCorresponde(int $pedidoId): bool
    {
        try {
            // Verificar si el pedido tiene items entregados
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedidoId)
                ->activo()
                ->get();

            if ($despachos->isEmpty()) {
                Log::info('Pedido sin despachos registrados, no se cambia estado', [
                    'pedido_id' => $pedidoId
                ]);
                return false;
            }

            // Verificar si hay items entregados
            $despachosEntregados = $despachos->where('entregado', true);
            
            if ($despachosEntregados->count() > 0) {
                Log::info('Pedido todavía tiene items entregados, no se cambia estado', [
                    'pedido_id' => $pedidoId,
                    'items_entregados' => $despachosEntregados->count()
                ]);
                return false;
            }

            // Obtener el pedido
            $pedido = PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                Log::warning('Pedido no encontrado para cambio de estado', [
                    'pedido_id' => $pedidoId
                ]);
                return false;
            }

            // Verificar que el estado actual no sea ya "Pendiente"
            if ($pedido->estado === 'Pendiente') {
                Log::info('Pedido ya está en estado Pendiente', [
                    'pedido_id' => $pedidoId,
                    'numero_pedido' => $pedido->numero_pedido
                ]);
                return false;
            }

            // NOTA: Permitir cambiar de "Entregado" a "Pendiente" según requerimiento
            // El usuario quiere poder deshacer el estado "Entregado"

            // Cambiar estado a "Pendiente"
            $estadoAnterior = $pedido->estado;
            $pedido->estado = 'Pendiente';
            $pedido->save();

            Log::info('Estado del pedido cambiado a Pendiente automáticamente', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'Pendiente',
                'fecha_cambio' => now()->toDateTimeString()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido a Pendiente', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Determinar el estado de entrega de un pedido
     * 
     * @param int $pedidoId
     * @return string 'sin_entregar'|'parcial'|'completo'
     */
    public function obtenerEstadoEntrega(int $pedidoId): string
    {
        try {
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedidoId)
                ->activo()
                ->get();

            if ($despachos->isEmpty()) {
                return 'sin_entregar';
            }

            $entregados = $despachos->where('entregado', true)->count();
            $total = $despachos->count();

            if ($entregados === 0) {
                return 'sin_entregar';
            } elseif ($entregados === $total) {
                return 'completo';
            } else {
                return 'parcial';
            }

        } catch (\Exception $e) {
            Log::error('Error al obtener estado de entrega', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            
            return 'sin_entregar';
        }
    }
}
