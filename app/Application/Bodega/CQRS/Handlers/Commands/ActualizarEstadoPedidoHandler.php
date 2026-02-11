<?php

namespace App\Application\Bodega\CQRS\Handlers\Commands;

use App\Application\Bodega\CQRS\Commands\CommandInterface;
use App\Application\Bodega\CQRS\Commands\ActualizarEstadoPedidoCommand;
use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use App\Domain\Bodega\Events\DomainEventDispatcher;

/**
 * Handler para el Command ActualizarEstadoPedido
 * Ejecuta la lógica de negocio para actualizar el estado de un pedido
 */
class ActualizarEstadoPedidoHandler
{
    private PedidoRepositoryInterface $pedidoRepository;

    public function __construct(PedidoRepositoryInterface $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Manejar el command de actualizar estado de pedido
     */
    public function handle(ActualizarEstadoPedidoCommand $command): array
    {
        try {
            // 1. Obtener el pedido
            $pedido = $this->pedidoRepository->findById($command->getPedidoId());
            
            if (!$pedido) {
                throw new \InvalidArgumentException("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // 2. Validar transición de estado
            $estadoAnterior = $pedido->getEstado();
            $nuevoEstado = $command->getNuevoEstado();

            if ($estadoAnterior->equals($nuevoEstado)) {
                throw new \LogicException("El pedido ya está en el estado {$nuevoEstado->getValor()}");
            }

            // 3. Ejecutar la lógica de negocio
            $pedido->actualizarEstado($nuevoEstado);

            // 4. Agregar motivo si se proporcionó
            if ($command->getMotivo()) {
                $novedadesActuales = $pedido->getNovedades();
                $nuevasNovedades = trim($novedadesActuales . "\n\n" . 
                    "Estado cambiado por usuario {$command->getUsuarioId()} el {$command->getEjecutadoEn()->format('d/m/Y H:i')}: " . 
                    "De {$estadoAnterior->getValor()} a {$nuevoEstado->getValor()}" . 
                    ($command->getMotivo() ? ". Motivo: {$command->getMotivo()}" : ""));
                $pedido->actualizarNovedades($nuevasNovedades);
            }

            // 5. Persistir los cambios
            $this->pedidoRepository->save($pedido);

            // 6. Procesar eventos de dominio
            DomainEventDispatcher::processQueue();

            // 7. Retornar resultado
            return [
                'success' => true,
                'message' => "Estado del pedido {$pedido->getNumeroPedido()} actualizado correctamente",
                'command_id' => $command->getCommandId(),
                'pedido' => $pedido->toArray(),
                'estado_anterior' => $estadoAnterior->getValor(),
                'estado_nuevo' => $pedido->getEstado()->getValor(),
                'ejecutado_por' => $command->getUsuarioId(),
                'motivo' => $command->getMotivo(),
                'es_transicion_importante' => $this->esTransicionImportante($estadoAnterior, $nuevoEstado)
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
            \Log::error("Error en ActualizarEstadoPedidoHandler: " . $e->getMessage(), [
                'command_id' => $command->getCommandId(),
                'pedido_id' => $command->getPedidoId(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno al actualizar el estado del pedido',
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
        return $command instanceof ActualizarEstadoPedidoCommand;
    }

    /**
     * Determinar si una transición es importante
     */
    private function esTransicionImportante($estadoAnterior, $nuevoEstado): bool
    {
        $transicionesImportantes = [
            'NO INICIADO' => 'EN EJECUCIÓN',
            'EN EJECUCIÓN' => 'ENTREGADO',
            'PENDIENTE_INSUMOS' => 'NO INICIADO',
            'PENDIENTE_SUPERVISOR' => 'NO INICIADO'
        ];

        $clave = $estadoAnterior->getValor() . ' -> ' . $nuevoEstado->getValor();
        return in_array($clave, $transicionesImportantes);
    }
}
