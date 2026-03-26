<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\ReadModels\PedidoBorradorRef;
use App\Domain\Pedidos\ReadModels\PedidoEppRef;
use App\Domain\Pedidos\ReadModels\PedidoNumeroRef;
use App\Domain\Pedidos\ReadModels\PedidoPrendaRef;
use App\Models\PedidoProduccion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PedidoProduccionReadRepository
{
    public function findByNumeroPedido(string $numeroPedido): ?PedidoNumeroRef;

    public function obtenerPorId(int $id): ?PedidoProduccion;

    public function obtenerPedidosAsesor(array $filtros = []): LengthAwarePaginator;

    public function perteneceAlAsesor(int $pedidoId, int $asesorId): bool;

    public function actualizarCantidadTotal(string $numeroPedido): void;

    public function obtenerDatosFactura(int $pedidoId): array;

    public function obtenerDatosRecibos(int $pedidoId, bool $filtrarProcesosPendientes = false): array;

    public function obtenerPorIdYAsesor(int $pedidoId, int $asesorId): ?PedidoBorradorRef;

    public function actualizarDatosBasicos(int $pedidoId, array $datos): void;

    public function obtenerEppConImagenes(int $pedidoId, int $eppId): ?PedidoEppRef;

    public function actualizarDatosEpp(int $pedidoEppId, array $datos): void;

    public function obtenerPrendaDelPedido(int $pedidoId, int $prendaId): ?PedidoPrendaRef;
}
