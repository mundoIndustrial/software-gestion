<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ConsecutivoReciboPedido;

interface ConsecutivoReciboPedidoRepository
{
    public function findActiveById(int $id): ?ConsecutivoReciboPedido;

    public function findFirstActiveByPedidoTipo(int $pedidoProduccionId, string $tipoRecibo): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoPrendaTipo(int $pedidoProduccionId, int $prendaId, string $tipoRecibo, ?int $prendaBodegaId = null): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoPrendaTipoAndArea(int $pedidoProduccionId, int $prendaId, string $tipoRecibo, string $area, ?int $prendaBodegaId = null): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoConsecutivoTipo(int $pedidoProduccionId, int $consecutivoActual, string $tipoRecibo): ?ConsecutivoReciboPedido;

    public function save(ConsecutivoReciboPedido $recibo): void;
}
