<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\PedidoWorkflowRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class EloquentPedidoWorkflowRepository implements PedidoWorkflowRepository
{
    public function cambiarEstadoPorNumeroPedido(string $numeroPedido, string $nuevoEstado): array
    {
        try {
            $pedido = $this->resolverPedidoPorNumeroOId($numeroPedido);

            return $this->cambiarEstado((int) $pedido->id, $nuevoEstado);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado por numero de pedido: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al cambiar estado',
            ];
        }
    }

    public function cambiarEstado(int $pedidoId, string $nuevoEstado): array
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            $estadoActual = (string) $pedido->estado;

            if ($estadoActual === $nuevoEstado) {
                return [
                    'success' => false,
                    'message' => 'El pedido ya esta en ese estado',
                ];
            }

            if (!$this->esTransicionPermitida($estadoActual, $nuevoEstado)) {
                return [
                    'success' => false,
                    'message' => 'Transicion de estado no permitida',
                ];
            }

            $pedido->update([
                'estado' => $nuevoEstado,
                'area' => $this->determinarAreaPorEstado($nuevoEstado),
            ]);

            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado_anterior' => $estadoActual,
                'nuevo_estado' => $nuevoEstado,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al cambiar estado',
            ];
        }
    }

    private function resolverPedidoPorNumeroOId(string $numeroPedido): PedidoProduccion
    {
        if (ctype_digit($numeroPedido)) {
            $pedidoPorId = PedidoProduccion::find((int) $numeroPedido);
            if ($pedidoPorId) {
                return $pedidoPorId;
            }
        }

        return PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
    }

    private function esTransicionPermitida(string $estadoActual, string $nuevoEstado): bool
    {
        if ($estadoActual === 'PENDIENTE_INSUMOS') {
            return in_array($nuevoEstado, ['No iniciado', 'En Ejecucion', 'En Ejecución'], true);
        }

        if ($estadoActual === 'No iniciado') {
            return in_array($nuevoEstado, ['En Ejecucion', 'En Ejecución'], true);
        }

        if (in_array($estadoActual, ['En Ejecucion', 'En Ejecución'], true)) {
            return in_array($nuevoEstado, ['No iniciado', 'PENDIENTE_INSUMOS'], true);
        }

        return false;
    }

    private function determinarAreaPorEstado(string $estado): string
    {
        return match ($estado) {
            'No iniciado', 'En Ejecucion', 'En Ejecución' => 'Corte',
            'PENDIENTE_INSUMOS' => 'Insumos',
            default => 'Corte',
        };
    }
}

