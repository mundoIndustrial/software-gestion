<?php

namespace App\Domain\PedidoProduccion\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\PedidoProduccion\Commands\EliminarPedidoCommand;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * EliminarPedidoHandler
 * 
 * Maneja EliminarPedidoCommand
 * Elimina (soft delete) un pedido
 */
class EliminarPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof EliminarPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser EliminarPedidoCommand');
        }

        try {
            Log::info('🗑️ [EliminarPedidoHandler] Eliminando pedido', [
                'pedido_id' => $command->getPedidoId(),
                'razon' => $command->getRazon(),
            ]);

            // Obtener el pedido
            $pedido = $this->pedidoModel->find($command->getPedidoId());

            if (!$pedido) {
                throw new \Exception("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // Validar que no esté ya eliminado
            if ($pedido->trashed()) {
                throw new \Exception("El pedido ya ha sido eliminado");
            }

            // Realizar soft delete
            $pedido->delete();

            Log::info(' [EliminarPedidoHandler] Pedido eliminado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'razon' => $command->getRazon(),
            ]);

            // Invalidar cachés
            cache()->forget("pedido_{$command->getPedidoId()}_completo");
            cache()->forget("pedido_numero_{$pedido->numero_pedido}");
            cache()->forget('pedidos_lista');

            return $pedido;

        } catch (\Exception $e) {
            Log::error(' [EliminarPedidoHandler] Error eliminando pedido', [
                'pedido_id' => $command->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
