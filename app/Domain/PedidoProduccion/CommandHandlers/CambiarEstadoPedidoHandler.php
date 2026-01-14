<?php

namespace App\Domain\PedidoProduccion\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\PedidoProduccion\Commands\CambiarEstadoPedidoCommand;
use App\Domain\PedidoProduccion\Validators\EstadoValidator;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * CambiarEstadoPedidoHandler
 * 
 * Maneja CambiarEstadoPedidoCommand
 * Cambia el estado de un pedido
 */
class CambiarEstadoPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
        private EstadoValidator $validator,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof CambiarEstadoPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser CambiarEstadoPedidoCommand');
        }

        try {
            Log::info('🔄 [CambiarEstadoPedidoHandler] Cambiando estado de pedido', [
                'pedido_id' => $command->getPedidoId(),
                'nuevo_estado' => $command->getNuevoEstado(),
                'razon' => $command->getRazon(),
            ]);

            // Obtener el pedido
            $pedido = $this->pedidoModel->find($command->getPedidoId());

            if (!$pedido) {
                throw new \Exception("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // Validar transición de estado usando EstadoValidator
            $estadoActual = strtolower($pedido->estado);
            $nuevoEstado = $command->getNuevoEstado();

            $this->validator->validateTransicion($estadoActual, $nuevoEstado);
            
            Log::info('✅ [CambiarEstadoPedidoHandler] Validación de transición pasada', []);

            // Cambiar estado
            $pedido->update([
                'estado' => $nuevoEstado,
            ]);

            // Registrar en historial de cambios
            if ($command->getRazon()) {
                Log::info('📝 Razón del cambio de estado', [
                    'razon' => $command->getRazon(),
                ]);
            }

            Log::info('✅ [CambiarEstadoPedidoHandler] Estado actualizado', [
                'pedido_id' => $pedido->id,
                'estado_anterior' => $estadoActual,
                'estado_nuevo' => $nuevoEstado,
            ]);

            // Invalidar cachés
            cache()->forget("pedido_{$command->getPedidoId()}_completo");
            cache()->forget("pedido_numero_{$pedido->numero_pedido}");
            cache()->forget('pedidos_lista');

            return $pedido;

        } catch (\Exception $e) {
            Log::error('❌ [CambiarEstadoPedidoHandler] Error cambiando estado', [
                'pedido_id' => $command->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
