<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ConsecutivoReciboPedido;

interface ConsecutivoReciboPedidoRepository
{
    public function findActiveById(int $id): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoPrendaTipo(int $pedidoProduccionId, int $prendaId, string $tipoRecibo): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoPrendaTipoAndArea(int $pedidoProduccionId, int $prendaId, string $tipoRecibo, string $area): ?ConsecutivoReciboPedido;

    public function findActiveByPedidoConsecutivoTipo(int $pedidoProduccionId, int $consecutivoActual, string $tipoRecibo): ?ConsecutivoReciboPedido;

    /**
     * Buscar recibos por término de búsqueda
     * 
     * @param string $termino Término a buscar en consecutivo, número de pedido o nombre de cliente
     * @param int $limit Límite de resultados
     * @return array Colección de recibos encontrados
     */
    public function search(string $termino, int $limit = 10): array;

    public function save(ConsecutivoReciboPedido $recibo): void;
}
