<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\ReadModels\PedidoBorradorRef;
use App\Domain\Pedidos\ReadModels\PedidoEppRef;
use App\Domain\Pedidos\ReadModels\PaginatedPedidosResult;
use App\Domain\Pedidos\ReadModels\PedidoNumeroRef;
use App\Domain\Pedidos\ReadModels\PedidoPrendaRef;

interface PedidoProduccionReadRepository
{
    public function findByNumeroPedido(string $numeroPedido): ?PedidoNumeroRef;

    public function obtenerPedidosAsesor(array $filtros = []): PaginatedPedidosResult;

    public function perteneceAlAsesor(int $pedidoId, int $asesorId): bool;

    public function actualizarCantidadTotal(string $numeroPedido): void;

    public function obtenerDatosFactura(int $pedidoId): array;

    public function obtenerDatosRecibos(int $pedidoId, bool $filtrarProcesosPendientes = false): array;

    public function obtenerPorIdYAsesor(int $pedidoId, int $asesorId): ?PedidoBorradorRef;

    public function actualizarDatosBasicos(int $pedidoId, array $datos): void;

    public function obtenerEppConImagenes(int $pedidoId, int $eppId): ?PedidoEppRef;

    public function actualizarDatosEpp(int $pedidoEppId, array $datos): void;

    public function obtenerPrendaDelPedido(int $pedidoId, int $prendaId): ?PedidoPrendaRef;

    public function obtenerPedidoPorId(int $pedidoId): ?array;

    /**
     * Obtiene el detalle base de un pedido por número, incluyendo
     * relaciones necesarias para visualización.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPedidoDetallePorNumero(int $numeroPedido): ?array;

    /**
     * Obtiene los totales de cantidad y entregado para un pedido por número.
     *
     * @return array{total_cantidad:int,total_entregado:int}
     */
    public function obtenerTotalesPorNumeroPedido(int $numeroPedido): array;
}
