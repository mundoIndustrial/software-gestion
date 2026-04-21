<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Collection;

interface PedidoDetalleReadService
{
    public function findPedidoByIdOrNumero(int $idONumero): ?PedidoProduccion;

    public function findPedidoById(int $pedidoId): ?PedidoProduccion;

    public function findPedidoByIdConRelaciones(int $pedidoId, bool $filtrarPrendasBodega): ?PedidoProduccion;

    public function getProcesoTallasDetalle(int $procesoDetalleId): Collection;

    public function getProcesoTallasConObservaciones(int $procesoDetalleId): Collection;

    public function getAnchoPrenda(int $pedidoId, int $prendaId): ?object;

    public function getMetrajesPrenda(int $pedidoId, int $prendaId): Collection;

    public function getConsecutivosPrenda(int $pedidoId, int $prendaId): Collection;

    public function getParcialesPrenda(int $pedidoId, int $prendaId): Collection;

    public function findReciboCosturaByPedidoId(int $pedidoId): ?object;

    public function getFechaEstimadaMasLejanaByPedidoId(int $pedidoId): ?string;

    public function getTallasProceso(int $procesoDetalleId): Collection;

    public function getColoresByProcesoTalla(int $procesoTallaId): Collection;

    public function getTallasColoresPrenda(int $prendaId): Collection;

    public function getColoresPorTallaPrenda(int $prendaId): Collection;

    public function getImagenRutasTallaColorPrenda(int $prendaId): Collection;

    public function getTallaColoresDetallePrenda(int $prendaId): Collection;

    public function findPrendaEntrega(int $prendaId): ?object;

    public function getPrendaEntregaEstado(int $prendaId): array;

    public function getRecibosParcialesPrenda(int $pedidoId, int $prendaId): Collection;

    public function getReciboParcialTallas(int $pedidoParcialId): Collection;

    public function getPedidoEppImagenes(int $pedidoEppId): Collection;
}
