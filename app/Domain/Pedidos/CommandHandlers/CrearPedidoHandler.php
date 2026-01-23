<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Aggregates\PedidoProduccionAggregate;
use App\Domain\Pedidos\Validators\PedidoValidator;
use App\Domain\Shared\DomainEventDispatcher;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * CrearPedidoHandler
 * 
 * Maneja el command CrearPedidoCommand
 * Crea un nuevo pedido de producciÃ³n con eventos
 * 
 * Responsabilidades:
 * - Validar datos del command
 * - Crear el agregado PedidoProduccion
 * - Persistir en la base de datos
 * - Emitir evento PedidoProduccionCreado
 * - Retornar el pedido creado
 */
class CrearPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
        private DomainEventDispatcher $eventDispatcher,
        private PedidoValidator $validator,
    ) {}

    /**
     * Ejecutar el command
     * 
     * @param CrearPedidoCommand $command
     * @return PedidoProduccion Pedido creado
     */
    public function handle(Command $command): mixed
    {
        if (!$command instanceof CrearPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser CrearPedidoCommand');
        }

        try {
            Log::info('âš¡ [CrearPedidoHandler] Iniciando creaciÃ³n de pedido', [
                'numero_pedido' => $command->getNumeroPedido(),
                'cliente' => $command->getCliente(),
                'asesor_id' => $command->getAsesorId(),
            ]);

            // Validar datos del command
            $this->validator->validate([
                'numero_pedido' => $command->getNumeroPedido(),
                'cliente' => $command->getCliente(),
                'forma_pago' => $command->getFormaPago(),
                'asesor_id' => $command->getAsesorId(),
                'cantidad_inicial' => $command->getCantidadInicial(),
            ]);

            Log::info(' [CrearPedidoHandler] Validaciones pasadas', []);



            // Crear el agregado (maneja invariantes)
            $agregado = PedidoProduccionAggregate::crear(
                id: null, // Se asignarÃ¡ en BD
                numeroPedido: $command->getNumeroPedido(),
                cliente: $command->getCliente(),
                formaPago: $command->getFormaPago(),
                asesorId: $command->getAsesorId(),
                estado: 'activo',
            );

            // Persistir en base de datos
            $pedido = $this->pedidoModel->create([
                'numero_pedido' => $command->getNumeroPedido(),
                'cliente' => $command->getCliente(),
                'forma_pago' => $command->getFormaPago(),
                'asesor_id' => $command->getAsesorId(),
                'cantidad_total' => $command->getCantidadInicial(),
                'estado' => 'activo',
            ]);

            Log::info(' [CrearPedidoHandler] Pedido creado en BD', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Emitir eventos del agregado
            foreach ($agregado->getUncommittedEvents() as $event) {
                $this->eventDispatcher->dispatch($event);
            }

            // Invalidar cachÃ©s
            cache()->forget('pedidos_lista');
            cache()->forget('pedidos_recientes');

            Log::info(' [CrearPedidoHandler] Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'eventos_emitidos' => count($agregado->getUncommittedEvents()),
            ]);

            return $pedido;

        } catch (\Exception $e) {
            Log::error(' [CrearPedidoHandler] Error creando pedido', [
                'numero_pedido' => $command->getNumeroPedido(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

