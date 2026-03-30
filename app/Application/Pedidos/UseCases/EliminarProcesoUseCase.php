<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\ProcesoPedidoWriteRepository;
use App\Domain\Pedidos\UseCases\EliminarProcesoUseCaseContract;

/**
 * EliminarProcesoUseCase
 * Caso de uso para eliminar un proceso de un pedido
 * Responsabilidad: Eliminar proceso y su historial asociado
 * Patron: Use Case (Application Layer - DDD)
 * Restriccion: No se puede eliminar el unico proceso de una orden
 */
class EliminarProcesoUseCase implements EliminarProcesoUseCaseContract
{
    use ManejaPedidosUseCase;

    public function __construct(
        private readonly ProcesoPedidoWriteRepository $procesoPedidoWriteRepository
    ) {
    }

    /**
     * Ejecutar caso de uso
     * @param int $id - ID del proceso a eliminar
     * @param int $numeroPedido - Numero de pedido para validacion
     * @return array - Respuesta del resultado
     * @throws \Exception
     */
    public function ejecutar(int $id, int $numeroPedido): array
    {
        $this->validarPositivo($id, 'ID del proceso');
        $this->validarPositivo($numeroPedido, 'Numero de pedido');

        if ($this->procesoPedidoWriteRepository->existeProcesoNuevoEnPedido($id, $numeroPedido)) {
            $this->procesoPedidoWriteRepository->eliminarProcesoNuevoConDependencias($id);

            return [
                'success' => true,
                'message' => 'Proceso eliminado correctamente',
            ];
        }

        $nombreProcesoLegacy = $this->procesoPedidoWriteRepository
            ->obtenerNombreProcesoLegacy($id, $numeroPedido);

        if (!$nombreProcesoLegacy) {
            throw new \DomainException('Proceso no encontrado');
        }

        $totalProcesosDistintos = $this->procesoPedidoWriteRepository
            ->contarProcesosLegacyDistintos($numeroPedido);

        if ($totalProcesosDistintos <= 1) {
            throw new \DomainException('No se puede eliminar el unico proceso de una orden');
        }

        $this->procesoPedidoWriteRepository
            ->eliminarProcesoLegacyPorNombre($numeroPedido, $nombreProcesoLegacy);

        return [
            'success' => true,
            'message' => 'Proceso eliminado correctamente',
        ];
    }
}
