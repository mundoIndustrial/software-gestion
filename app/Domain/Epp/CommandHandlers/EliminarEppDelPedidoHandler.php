<?php

namespace App\Domain\Epp\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Epp\Commands\EliminarEppDelPedidoCommand;
use App\Domain\Epp\Repositories\PedidoEppRepositoryInterface;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * EliminarEppDelPedidoHandler
 * 
 * Maneja EliminarEppDelPedidoCommand
 * Elimina un EPP de un pedido
 */
class EliminarEppDelPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoEppRepositoryInterface $pedidoEppRepository,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof EliminarEppDelPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser EliminarEppDelPedidoCommand');
        }

        try {
            Log::info('🔄 [EliminarEppDelPedidoHandler] Eliminando EPP del pedido', [
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ]);

            // Verificar que el pedido existe
            $pedido = PedidoProduccion::find($command->getPedidoId());
            if (!$pedido) {
                throw new \DomainException('Pedido no encontrado');
            }

            // Verificar que el EPP está en el pedido
            if (!$this->pedidoEppRepository->estaEppEnPedido($command->getPedidoId(), $command->getEppId())) {
                throw new \DomainException('EPP no encontrado en este pedido');
            }

            // Eliminar EPP
            $this->pedidoEppRepository->eliminarEppDelPedido(
                $command->getPedidoId(),
                $command->getEppId()
            );

            Log::info(' [EliminarEppDelPedidoHandler] EPP eliminado correctamente', [
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ]);

            return [
                'success' => true,
                'message' => 'EPP eliminado del pedido correctamente',
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ];

        } catch (\DomainException $e) {
            Log::warning('⚠️ [EliminarEppDelPedidoHandler] Error de dominio', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error(' [EliminarEppDelPedidoHandler] Error eliminando EPP', [
                'error' => $e->getMessage(),
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ]);

            throw $e;
        }
    }
}
