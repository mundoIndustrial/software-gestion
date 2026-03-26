<?php

namespace App\Application\Insumos\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class PedidoAprobacionService
{
    public function __construct(
        private readonly ProcesoAutomaticoService $procesoAutomaticoService
    ) {
    }

    public function cambiarEstadoPedido($numeroPedido, $nuevoEstado): array
    {
        try {
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            if (!$orden) {
                return [
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ];
            }

            if ($orden->estado !== 'Pendiente' && $orden->estado !== 'PENDIENTE_INSUMOS') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden enviar a produccion pedidos en estado Pendiente o Pendiente Insumos',
                ];
            }

            $orden->update([
                'estado' => 'No iniciado',
                'area' => 'Corte',
            ]);

            $resultadoProcesos = $this->procesoAutomaticoService->crearProcesosParaPedido($numeroPedido);

            Log::info("Pedido #{$numeroPedido} enviado a produccion", [
                'estado_anterior' => 'Pendiente',
                'estado_nuevo' => 'No iniciado',
                'area' => 'Corte',
                'usuario' => auth()->user()->name ?? 'Sistema',
                'procesos_creados' => $resultadoProcesos['procesos_creados'] ?? 0,
            ]);

            $message = 'Pedido enviado a produccion correctamente';
            if (($resultadoProcesos['success'] ?? false) && ($resultadoProcesos['procesos_creados'] ?? 0) > 0) {
                $message .= ". Se crearon {$resultadoProcesos['procesos_creados']} procesos automaticamente";
            }

            return [
                'success' => true,
                'message' => $message,
                'estado' => 'No iniciado',
                'area' => 'Corte',
                'procesos_creados' => $resultadoProcesos['procesos_creados'] ?? 0,
                'detalles_procesos' => $resultadoProcesos['detalles'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al cambiar el estado',
            ];
        }
    }
}

