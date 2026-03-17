<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Models\ConsecutivoReciboPedido;

class ConsecutivoReciboPedidoRepositoryImpl implements ConsecutivoReciboPedidoRepository
{
    public function findActiveById(int $id): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::where('id', $id)
            ->where('activo', 1)
            ->first();
    }

    public function findActiveByPedidoPrendaTipo(int $pedidoProduccionId, int $prendaId, string $tipoRecibo): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoProduccionId)
            ->where('prenda_id', $prendaId)
            ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($tipoRecibo)])
            ->where('activo', 1)
            ->first();
    }

    public function findActiveByPedidoPrendaTipoAndArea(int $pedidoProduccionId, int $prendaId, string $tipoRecibo, string $area): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoProduccionId)
            ->where('prenda_id', $prendaId)
            ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($tipoRecibo)])
            ->whereRaw('LOWER(TRIM(area)) = ?', [strtolower(trim($area))])
            ->where('activo', 1)
            ->first();
    }

    public function findActiveByPedidoConsecutivoTipo(int $pedidoProduccionId, int $consecutivoActual, string $tipoRecibo): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoProduccionId)
            ->where('consecutivo_actual', $consecutivoActual)
            ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($tipoRecibo)])
            ->where('activo', 1)
            ->first();
    }

    public function save(ConsecutivoReciboPedido $recibo): void
    {
        $recibo->save();
    }
}
