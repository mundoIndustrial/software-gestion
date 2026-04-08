<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\ProcesoPedidoWriteRepository;
use App\Domain\Pedidos\UseCases\CrearProcesoUseCaseContract;

/**
 * CrearProcesoUseCase
 * Caso de uso para crear o actualizar un proceso de pedido
 * Responsabilidad: Crear nuevos procesos y guardar cambios en historial
 * Patrón: Use Case (Application Layer - DDD)
 * Lógica: Si el proceso ya existe, guardar el anterior en historial y actualizar
 */
class CrearProcesoUseCase implements CrearProcesoUseCaseContract
{
    public function __construct(
        private readonly ProcesoPedidoWriteRepository $procesoPedidoWriteRepository
    ) {
    }

    /**
     * Ejecutar caso de uso
     * @param array $data - Datos del proceso a crear
     * @return array - Respuesta con datos del proceso creado
     */
    public function ejecutar(array $data): array
    {
        $numeroPedido = (int) $data['numero_pedido'];
        $nombreProceso = (string) $data['proceso'];

        $procesoDuplicado = $this->procesoPedidoWriteRepository
            ->obtenerProcesoLegacyPorPedidoYNombre($numeroPedido, $nombreProceso);

        if ($procesoDuplicado) {
            return $this->actualizarProcesoExistente($procesoDuplicado, $data);
        }

        return $this->crearNuevoProceso($data, $numeroPedido, $nombreProceso);
    }

    /**
     * Actualizar un proceso que ya existe
     */
    private function actualizarProcesoExistente(array $procesoDuplicado, array $data): array
    {
        $this->procesoPedidoWriteRepository->actualizarProcesoLegacy(
            (int) $procesoDuplicado['id'],
            (string) $data['fecha_inicio'],
            $data['encargado'] ?? null,
            (string) $data['estado_proceso']
        );

        return [
            'success' => true,
            'message' => 'Proceso actualizado correctamente',
            'id' => (int) $procesoDuplicado['id'],
            'proceso' => $data['proceso'],
            'duplicado' => true
        ];
    }

    /**
     * Crear un nuevo proceso
     */
    private function crearNuevoProceso(array $data, int $numeroPedido, string $nombreProceso): array
    {
        $id = $this->procesoPedidoWriteRepository->crearProcesoLegacy(
            $numeroPedido,
            $nombreProceso,
            (string) $data['fecha_inicio'],
            $data['encargado'] ?? null,
            (string) $data['estado_proceso']
        );

        return [
            'success' => true,
            'message' => 'Proceso creado correctamente',
            'id' => $id,
            'proceso' => $nombreProceso
        ];
    }
}



