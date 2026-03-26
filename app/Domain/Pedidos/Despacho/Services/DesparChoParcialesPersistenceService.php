<?php

namespace App\Domain\Pedidos\Despacho\Services;

use App\Domain\Pedidos\Despacho\Entities\DesparChoParcial;
use App\Domain\Pedidos\Despacho\Repositories\DesparChoParcialesRepository;

/**
 * DesparChoParcialesPersistenceService
 * 
 * Domain Service encargado de la persistencia de despachos
 * parciales. Coordina la lógica de negocio con la persistencia.
 * 
 * Responsabilidades:
 * - Crear y persistir entidades DesparChoParcial
 * - Mantener la coherencia del agregado
 * - Facilitar búsquedas de despachos
 * - Manejar transacciones complejas
 */
class DesparChoParcialesPersistenceService
{
    public function __construct(
        private DesparChoParcialesRepository $repository,
    ) {}

    /**
     * Crear y persistir un nuevo despacho parcial
     */
    public function crearYGuardar(
        int $pedidoId,
        string $tipoItem,
        int $itemId,
        int $parcial1 = 0,
        int $parcial2 = 0,
        int $parcial3 = 0,
        ?string $observaciones = null,
        ?int $usuarioId = null,
    ): DesparChoParcial {
        // Crear entidad en el dominio
        $despacho = DesparChoParcial::crear(
            pedidoId: $pedidoId,
            tipoItem: $tipoItem,
            itemId: $itemId,
            parcial1: $parcial1,
            parcial2: $parcial2,
            parcial3: $parcial3,
            observaciones: $observaciones,
            usuarioId: $usuarioId,
        );

        // Persistir
        $this->repository->guardar($despacho);

        return $despacho;
    }

    /**
     * Crear y persistir múltiples despachos (transacción)
     * 
     * @param array $despachos Array de arrays con la información de cada despacho
     * @return DesparChoParcial[]
     */
    public function crearYGuardarMultiples(array $despachos, ?int $usuarioId = null): array
    {
        $entidades = array_map(function ($despacho) use ($usuarioId) {
            return DesparChoParcial::crear(
                pedidoId: $despacho['pedido_id'],
                tipoItem: $despacho['tipo_item'],
                itemId: $despacho['item_id'],
                tallaId: $despacho['talla_id'] ?? null,
                observaciones: $despacho['observaciones'] ?? null,
                usuarioId: $usuarioId,
            );
        }, $despachos);

        // Guardar todos en transacción
        $this->repository->guardarMultiples($entidades);

        return $entidades;
    }

    /**
     * Obtener todos los despachos de un pedido
     */
    public function obtenerDespachosPedido(int $pedidoId): array
    {
        return $this->repository->obtenerPorPedidoId($pedidoId);
    }

    /**
     * Obtener despachos de un ítem específico
     */
    public function obtenerDespachoItem(string $tipoItem, int $itemId): array
    {
        return $this->repository->obtenerPorItem($tipoItem, $itemId);
    }

    /**
     * Obtener despachos de un pedido filtrados por tipo
     */
    public function obtenerDespachosPorTipo(int $pedidoId, string $tipoItem): array
    {
        return $this->repository->obtenerPorPedidoYTipo($pedidoId, $tipoItem);
    }

    /**
     * Actualizar un despacho existente
     */
    public function actualizar(DesparChoParcial $despacho): void
    {
        $this->repository->actualizar($despacho);
    }

    /**
     * Obtener información agregada de despachos de un pedido
     */
    public function obtenerResumenDespachosPedido(int $pedidoId): array
    {
        $despachos = $this->obtenerDespachosPedido($pedidoId);
        $ultimaActualizacion = null;

        foreach ($despachos as $despacho) {
            $fecha = $despacho->updatedAt() ?? $despacho->createdAt();
            if ($fecha !== null && ($ultimaActualizacion === null || $fecha > $ultimaActualizacion)) {
                $ultimaActualizacion = $fecha;
            }
        }

        return [
            'total_registros' => count($despachos),
            'total_prendas' => count(array_filter($despachos, fn(DesparChoParcial $d) => $d->tipoItem() === 'prenda')),
            'total_epp' => count(array_filter($despachos, fn(DesparChoParcial $d) => $d->tipoItem() === 'epp')),
            'ultima_actualizacion' => $ultimaActualizacion,
        ];
    }
}
