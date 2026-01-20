<?php

namespace App\Domain\Epp\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Epp\Commands\AgregarEppAlPedidoCommand;
use App\Domain\Epp\Repositories\PedidoEppRepositoryInterface;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * AgregarEppAlPedidoHandler
 * 
 * Maneja AgregarEppAlPedidoCommand
 * Agrrega un EPP a un pedido
 */
class AgregarEppAlPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoEppRepositoryInterface $pedidoEppRepository,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof AgregarEppAlPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser AgregarEppAlPedidoCommand');
        }

        try {
            Log::info('🔄 [AgregarEppAlPedidoHandler] Agregando EPP al pedido', [
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
                'cantidad' => $command->getCantidad(),
            ]);

            // Verificar que el pedido existe
            $pedido = PedidoProduccion::find($command->getPedidoId());
            if (!$pedido) {
                throw new \DomainException('Pedido no encontrado');
            }

            // Agregar EPP al pedido
            $this->pedidoEppRepository->agregarEppAlPedido(
                $command->getPedidoId(),
                $command->getEppId(),
                $command->getTalla(),
                $command->getCantidad(),
                $command->getObservaciones()
            );

            Log::info(' [AgregarEppAlPedidoHandler] EPP agregado correctamente', [
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ]);

            return [
                'success' => true,
                'message' => 'EPP agregado al pedido correctamente',
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ];

        } catch (\InvalidArgumentException $e) {
            Log::warning('⚠️ [AgregarEppAlPedidoHandler] Validación fallida', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\DomainException $e) {
            Log::warning('⚠️ [AgregarEppAlPedidoHandler] Error de dominio', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error(' [AgregarEppAlPedidoHandler] Error agregando EPP', [
                'error' => $e->getMessage(),
                'pedido_id' => $command->getPedidoId(),
                'epp_id' => $command->getEppId(),
            ]);

            throw $e;
        }
    }
}
