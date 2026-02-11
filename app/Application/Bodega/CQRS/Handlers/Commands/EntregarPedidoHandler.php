<?php

namespace App\Application\Bodega\CQRS\Handlers\Commands;

use App\Application\Bodega\CQRS\Commands\CommandInterface;
use App\Application\Bodega\CQRS\Commands\EntregarPedidoCommand;
use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use App\Domain\Bodega\Events\DomainEventDispatcher;
use App\Domain\Bodega\Events\PedidoEntregado;

/**
 * Handler para el Command EntregarPedido
 * Ejecuta la lógica de negocio para entregar un pedido
 */
class EntregarPedidoHandler
{
    private PedidoRepositoryInterface $pedidoRepository;

    public function __construct(PedidoRepositoryInterface $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Manejar el command de entregar pedido
     */
    public function handle(EntregarPedidoCommand $command): array
    {
        try {
            // 1. Obtener el pedido
            $pedido = $this->pedidoRepository->findById($command->getPedidoId());
            
            if (!$pedido) {
                throw new \InvalidArgumentException("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // 2. Validar que puede ser entregado
            if (!$pedido->puedeSerEntregado()) {
                throw new \LogicException("El pedido {$pedido->getNumeroPedido()} no puede ser entregado en su estado actual");
            }

            // 3. Ejecutar la lógica de negocio
            $estadoAnterior = $pedido->getEstado();
            $pedido->entregar();

            // 4. Agregar observaciones si se proporcionaron
            if ($command->getObservaciones()) {
                $novedadesActuales = $pedido->getNovedades();
                $nuevasNovedades = trim($novedadesActuales . "\n\n" . 
                    "Entregado por usuario {$command->getUsuarioId()} el {$command->getEjecutadoEn()->format('d/m/Y H:i')}: " . 
                    $command->getObservaciones());
                $pedido->actualizarNovedades($nuevasNovedades);
            }

            // 5. Persistir los cambios
            $this->pedidoRepository->save($pedido);

            // 6. Procesar eventos de dominio
            DomainEventDispatcher::processQueue();

            // 7. Retornar resultado
            return [
                'success' => true,
                'message' => "Pedido {$pedido->getNumeroPedido()} entregado correctamente",
                'command_id' => $command->getCommandId(),
                'pedido' => $pedido->toArray(),
                'estado_anterior' => $estadoAnterior->getValor(),
                'estado_nuevo' => $pedido->getEstado()->getValor(),
                'fecha_entrega' => $pedido->getFechaEntregaReal()->format('d/m/Y H:i:s'),
                'ejecutado_por' => $command->getUsuarioId(),
                'observaciones' => $command->getObservaciones()
            ];

        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'command_id' => $command->getCommandId(),
                'error_type' => 'validation',
                'error_code' => 400
            ];

        } catch (\LogicException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'command_id' => $command->getCommandId(),
                'error_type' => 'business_logic',
                'error_code' => 422
            ];

        } catch (\Exception $e) {
            \Log::error("Error en EntregarPedidoHandler: " . $e->getMessage(), [
                'command_id' => $command->getCommandId(),
                'pedido_id' => $command->getPedidoId(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno al entregar el pedido',
                'command_id' => $command->getCommandId(),
                'error_type' => 'system_error',
                'error_code' => 500
            ];
        }
    }

    /**
     * Verificar si puede manejar este tipo de command
     */
    public function canHandle(CommandInterface $command): bool
    {
        return $command instanceof EntregarPedidoCommand;
    }
}
