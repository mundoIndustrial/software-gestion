<?php

namespace App\Domain\PedidoProduccion\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\PedidoProduccion\Commands\ActualizarPedidoCommand;
use App\Domain\PedidoProduccion\Validators\PedidoValidator;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * ActualizarPedidoHandler
 * 
 * Maneja ActualizarPedidoCommand
 * Actualiza datos de un pedido existente
 */
class ActualizarPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
        private PedidoValidator $validator,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof ActualizarPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser ActualizarPedidoCommand');
        }

        try {
            Log::info('✏️ [ActualizarPedidoHandler] Actualizando pedido', [
                'pedido_id' => $command->getPedidoId(),
            ]);

            // Validar que exista el pedido
            $pedido = $this->pedidoModel->find($command->getPedidoId());

            if (!$pedido) {
                throw new \Exception("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // Validar que hay actualizaciones
            if (!$command->tieneActualizaciones()) {
                Log::warning('⚠️ [ActualizarPedidoHandler] Sin cambios para actualizar');
                return $pedido;
            }

            // Preparar datos a actualizar
            $datos = [];
            
            if ($command->getCliente()) {
                $datos['cliente'] = $command->getCliente();
            }
            
            if ($command->getFormaPago()) {
                $datos['forma_pago'] = $command->getFormaPago();
            }

            // Validar datos antes de actualizar
            $this->validator->validateUpdate($datos);
            
            Log::info('✅ [ActualizarPedidoHandler] Validaciones pasadas', []);
            if ($command->getCliente()) {
                $datos['cliente'] = $command->getCliente();
            }
            if ($command->getFormaPago()) {
                $datos['forma_pago'] = $command->getFormaPago();
            }

            // Actualizar
            $pedido->update($datos);

            Log::info('✅ [ActualizarPedidoHandler] Pedido actualizado', [
                'pedido_id' => $pedido->id,
                'campos_actualizados' => count($datos),
            ]);

            // Invalidar cachés
            cache()->forget("pedido_{$command->getPedidoId()}_completo");
            cache()->forget("pedido_numero_{$pedido->numero_pedido}");
            cache()->forget('pedidos_lista');

            return $pedido;

        } catch (\Exception $e) {
            Log::error('❌ [ActualizarPedidoHandler] Error actualizando pedido', [
                'pedido_id' => $command->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
