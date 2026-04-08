<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Despacho\Services\DespachoEstadoServiceContract;

use App\Models\DesparChoParcialesModel;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para verificar y gestionar el estado completo de despacho de pedidos
 *
 * Responsabilidades:
 * - Verificar si todas las prendas y EPPs de un pedido están completamente despachadas
 * - Cambiar automáticamente el estado del pedido a "Entregado" cuando corresponda
 * - Proporcionar estadísticas de despacho
 */
class DespachoEstadoService implements DespachoEstadoServiceContract
{
    /**
     * Verificar si un pedido está completamente despachado
     */
    public function estaPedidoCompletamenteDespachado(int $pedidoId): bool
    {
        try {
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedidoId)
                ->activo()
                ->get();

            if ($despachos->isEmpty()) {
                Log::info('Pedido sin despachos registrados', [
                    'pedido_id' => $pedidoId,
                ]);
                return false;
            }

            foreach ($despachos as $despacho) {
                if (!$despacho->entregado) {
                    Log::debug('Despacho no entregado encontrado', [
                        'pedido_id' => $pedidoId,
                        'despacho_id' => $despacho->id,
                        'tipo_item' => $despacho->tipo_item,
                        'item_id' => $despacho->item_id,
                    ]);
                    return false;
                }
            }

            Log::info('Pedido completamente despachado verificado', [
                'pedido_id' => $pedidoId,
                'total_despachos' => $despachos->count(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al verificar estado completo de despacho', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Cambiar estado del pedido a "Entregado" si está completamente despachado
     */
    public function cambiarEstadoAEntregadoSiCorresponde(int $pedidoId): bool
    {
        try {
            if (!$this->estaPedidoCompletamenteDespachado($pedidoId)) {
                return false;
            }

            $pedido = PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                Log::warning('Pedido no encontrado para cambio de estado', [
                    'pedido_id' => $pedidoId,
                ]);
                return false;
            }

            if ($pedido->estado === 'Entregado') {
                Log::info('Pedido ya está en estado Entregado', [
                    'pedido_id' => $pedidoId,
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
                return false;
            }

            $estadoAnterior = $pedido->estado;
            $pedido->estado = 'Entregado';
            $pedido->save();

            Log::info('Estado del pedido cambiado a Entregado automáticamente', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'Entregado',
                'fecha_cambio' => now()->toDateTimeString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido a Entregado', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Obtener estadísticas de despacho para un pedido
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
                'detalle_por_tipo' => [],
            ];

            foreach ($despachos->groupBy('tipo_item') as $tipo => $items) {
                $entregadosPorTipo = $items->where('entregado', true)->count();
                $estadisticas['detalle_por_tipo'][$tipo] = [
                    'total' => $items->count(),
                    'entregados' => $entregadosPorTipo,
                    'pendientes' => $items->count() - $entregadosPorTipo,
                    'porcentaje_entregado' => round(($entregadosPorTipo / $items->count()) * 100, 2),
                ];
            }

            return $estadisticas;
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de despacho', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return [
                'pedido_id' => $pedidoId,
                'error' => 'No se pudieron obtener las estadísticas',
            ];
        }
    }

    /**
     * Verificar múltiples pedidos y cambiar estado de los que estén completos
     */
    public function procesarMultiplesPedidos(array $pedidoIds): array
    {
        $resultados = [
            'total_procesados' => 0,
            'cambiados_a_entregado' => 0,
            'ya_estaban_entregados' => 0,
            'no_completos' => 0,
            'errores' => 0,
            'detalles' => [],
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
                        'mensaje' => 'Pedido no encontrado',
                    ];
                    continue;
                }

                if ($pedido->estado === 'Entregado') {
                    $resultados['ya_estaban_entregados']++;
                    $resultados['detalles'][] = [
                        'pedido_id' => $pedidoId,
                        'numero_pedido' => $pedido->numero_pedido,
                        'estado' => 'ya_entregado',
                        'mensaje' => 'Ya estaba en estado Entregado',
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
                        'mensaje' => 'Cambiado a Entregado automáticamente',
                    ];
                } else {
                    $resultados['no_completos']++;
                    $resultados['detalles'][] = [
                        'pedido_id' => $pedidoId,
                        'numero_pedido' => $pedido->numero_pedido,
                        'estado' => 'no_completo',
                        'mensaje' => 'No está completamente despachado',
                    ];
                }
            } catch (\Exception $e) {
                $resultados['errores']++;
                $resultados['detalles'][] = [
                    'pedido_id' => $pedidoId,
                    'estado' => 'error',
                    'mensaje' => $e->getMessage(),
                ];
            }
        }

        Log::info('Proceso masivo de cambio de estado completado', $resultados);

        return $resultados;
    }

    /**
     * Cambiar estado del pedido a "En Ejecución" si ya no hay items entregados
     */
    public function cambiarEstadoAPendienteSiCorresponde(int $pedidoId): bool
    {
        try {
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedidoId)
                ->activo()
                ->get();

            if ($despachos->isEmpty()) {
                Log::info('Pedido sin despachos registrados, no se cambia estado', [
                    'pedido_id' => $pedidoId,
                ]);
                return false;
            }

            $despachosEntregados = $despachos->where('entregado', true);
            if ($despachosEntregados->count() > 0) {
                Log::info('Pedido todavía tiene items entregados, no se cambia estado', [
                    'pedido_id' => $pedidoId,
                    'items_entregados' => $despachosEntregados->count(),
                ]);
                return false;
            }

            $pedido = PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                Log::warning('Pedido no encontrado para cambio de estado', [
                    'pedido_id' => $pedidoId,
                ]);
                return false;
            }

            if ($pedido->estado === 'En Ejecución') {
                Log::info('Pedido ya está en estado En Ejecución', [
                    'pedido_id' => $pedidoId,
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
                return false;
            }

            $estadoAnterior = $pedido->estado;
            $pedido->estado = 'En Ejecución';
            $pedido->save();

            Log::info('Estado del pedido cambiado a En Ejecución automáticamente', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'En Ejecución',
                'fecha_cambio' => now()->toDateTimeString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido a En Ejecución', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Determinar el estado de entrega de un pedido
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
            }

            if ($entregados === $total) {
                return 'completo';
            }

            return 'parcial';
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de entrega', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return 'sin_entregar';
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {DespachoEstadoService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
