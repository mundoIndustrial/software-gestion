<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ConsecutivoReciboPedido;

interface ConsecutivoReciboPedidoRepository
{
    public function findActiveById(int $id): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoPrendaTipo(int $pedidoProduccionId, int $prendaId, string $tipoRecibo): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoPrendaTipoAndArea(int $pedidoProduccionId, int $prendaId, string $tipoRecibo, string $area): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoConsecutivoTipo(int $pedidoProduccionId, int $consecutivoActual, string $tipoRecibo): ?ConsecutivoReciboPedido;

    public function save(ConsecutivoReciboPedido $recibo): void;
}
