<?php

namespace App\Domain\ProcesoSeguimiento\Repositories;

use App\Models\ConsecutivoReciboPedido;

/**
 * Repository Interface: ConsecutivoReciboPedidoRepository
 *
 * Contrato del dominio para leer y sincronizar los consecutivos de recibos
 * de un pedido/prenda. Solo expone las operaciones que los Use Cases necesitan.
 */
interface ConsecutivoReciboPedidoRepository
{
    /**
     * Buscar el consecutivo activo de una prenda dentro de un pedido.
     */
    public function encontrarPorPedidoYPrenda(int $pedidoProduccionId, int $prendaId): ?ConsecutivoReciboPedido;

    /**
     * Actualizar el área (y opcionalmente el estado) del consecutivo.
     */
    public function actualizarArea(ConsecutivoReciboPedido $consecutivo, string $area, ?string $estado = null): void;
}
