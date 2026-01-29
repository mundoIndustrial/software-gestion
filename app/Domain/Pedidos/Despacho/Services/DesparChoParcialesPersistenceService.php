<?php

namespace App\Domain\Pedidos\Despacho\Services;

use App\Domain\Pedidos\Despacho\Entities\DesparChoParcial;
use App\Domain\Pedidos\Despacho\Repositories\DesparChoParcialesRepository;
use Illuminate\Support\Collection;

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
     * @return Collection<DesparChoParcial>
     */
    public function crearYGuardarMultiples(array $despachos, ?int $usuarioId = null): Collection
    {
        $entidades = collect($despachos)
            ->map(function ($despacho) use ($usuarioId) {
                return DesparChoParcial::crear(
                    pedidoId: $despacho['pedido_id'],
                    tipoItem: $despacho['tipo_item'],
                    itemId: $despacho['item_id'],
                    tallaId: $despacho['talla_id'] ?? null,
                    pendienteInicial: $despacho['pendiente_inicial'] ?? 0,
                    parcial1: $despacho['parcial_1'] ?? 0,
                    pendiente1: $despacho['pendiente_1'] ?? 0,
                    parcial2: $despacho['parcial_2'] ?? 0,
                    pendiente2: $despacho['pendiente_2'] ?? 0,
                    parcial3: $despacho['parcial_3'] ?? 0,
                    pendiente3: $despacho['pendiente_3'] ?? 0,
                    observaciones: $despacho['observaciones'] ?? null,
                    usuarioId: $usuarioId,
                );
            })
            ->toArray();

        // Guardar todos en transacción
        $this->repository->guardarMultiples($entidades);

        return collect($entidades);
    }

    /**
     * Obtener todos los despachos de un pedido
     */
    public function obtenerDespachosPedido(int $pedidoId): Collection
    {
        return $this->repository->obtenerPorPedidoId($pedidoId);
    }

    /**
     * Obtener despachos de un ítem específico
     */
    public function obtenerDespachoItem(string $tipoItem, int $itemId): Collection
    {
        return $this->repository->obtenerPorItem($tipoItem, $itemId);
    }

    /**
     * Obtener despachos de un pedido filtrados por tipo
     */
    public function obtenerDespachosPorTipo(int $pedidoId, string $tipoItem): Collection
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

        return [
            'total_registros' => $despachos->count(),
            'total_prendas' => $despachos->where('tipoItem', 'prenda')->count(),
            'total_epp' => $despachos->where('tipoItem', 'epp')->count(),
            'total_despachado_p1' => $despachos->sum(fn($d) => $d->parcial1()),
            'total_despachado_p2' => $despachos->sum(fn($d) => $d->parcial2()),
            'total_despachado_p3' => $despachos->sum(fn($d) => $d->parcial3()),
            'ultima_actualizacion' => $despachos->max(fn($d) => $d->updatedAt()) ?? $despachos->max(fn($d) => $d->createdAt()),
        ];
    }
}
