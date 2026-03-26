<?php

namespace App\Application\SupervisorPedidos\Services;

use App\Application\Pedidos\Services\PrendaPedidoDescriptionFormatter;
use App\Application\Pedidos\Services\PrendaPedidoQuantityCalculator;
use App\Application\Pedidos\Services\PedidoProduccionCalculatorService;
use App\Infrastructure\Repositories\PedidoProduccionTrackingRepository;
use App\Models\PedidoProduccion;
use App\Services\CalculadorDiasService;

/**
 * Servicio de lectura para supervisor sobre pedidos de produccion.
 *
 * Encapsula calculos y datos derivados usados por la vista/listado
 * sin asumir que esto pertenece al dominio puro.
 */
class PedidoProduccionReadService
{
    public function __construct(
        private PedidoProduccionTrackingRepository $repository,
        private PedidoProduccionCalculatorService $calculatorService,
        private PrendaPedidoDescriptionFormatter $prendaDescriptionFormatter,
        private PrendaPedidoQuantityCalculator $prendaQuantityCalculator
    ) {}

    public function calcularFechaEstimada(PedidoProduccion $pedido)
    {
        if (!$pedido->created_at || !$pedido->dia_de_entrega) {
            return null;
        }

        return $this->calculatorService->calcularFechaEstimada($pedido->created_at, $pedido->dia_de_entrega);
    }

    public function getAreaActual(PedidoProduccion $pedido): string
    {
        return $this->repository->getAreaActual($pedido);
    }

    public function procesoActualOptimizado(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas')) {
            return $this->getAreaActual($pedido);
        }

        $procesos = $pedido->prendas
            ->flatMap(fn($prenda) => $prenda->procesos ?? collect())
            ->unique('proceso');

        return $this->calculatorService->determinarProcesoActualOptimizado($procesos);
    }

    public function getTotalDias(PedidoProduccion $pedido): ?string
    {
        if (!$pedido->created_at) {
            return null;
        }

        $ultimaFecha = $this->repository->getUltimaFechaProcesoFin($pedido);

        if (!$ultimaFecha) {
            $ultimaFecha = now()->toDateString();
        }

        $dias = CalculadorDiasService::calcularDiasHabiles(
            $pedido->created_at,
            $ultimaFecha
        );

        return CalculadorDiasService::formatearDias($dias);
    }

    public function getTotalDiasNumero(PedidoProduccion $pedido): int
    {
        $totalDias = $this->getTotalDias($pedido);

        if (!$totalDias) {
            return 0;
        }

        preg_match('/(\d+)/', $totalDias, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    public function getDesgloseDiasPorProceso(PedidoProduccion $pedido): array
    {
        return $this->repository->getDesgloseDiasPorProceso($pedido);
    }

    public function estaEnRetraso(PedidoProduccion $pedido): bool
    {
        $areaActual = $this->getAreaActual($pedido);
        return CalculadorDiasService::estaEnRetraso($areaActual, $pedido->fecha_estimada_de_entrega);
    }

    public function getDiasDeRetraso(PedidoProduccion $pedido): int
    {
        if (!$this->estaEnRetraso($pedido)) {
            return 0;
        }

        return CalculadorDiasService::calcularDiasDeRetraso($pedido->fecha_estimada_de_entrega);
    }

    public function calcularDiasHabilesDesdeCreacion(PedidoProduccion $pedido): string
    {
        if (!$pedido->created_at) {
            return '-';
        }

        $diasCalculados = CalculadorDiasService::calcularDiasHabilesSinIncluirInicio($pedido->created_at);

        $pluralSuffix = $diasCalculados > 1 ? 's' : '';
        $diasFormateado = $diasCalculados . ' día' . $pluralSuffix;

        return $diasCalculados > 0 ? $diasFormateado : '-';
    }

    public function getDescripcionPrendas(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '';
        }

        $descripciones = $pedido->prendas->map(function ($prenda, $index) {
            return $this->prendaDescriptionFormatter->formatDetailed($prenda, $index + 1);
        })->toArray();

        return implode("\n\n", $descripciones);
    }

    public function getCantidadTotal(PedidoProduccion $pedido): int
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($pedido->prendas as $prenda) {
            $total += $this->prendaQuantityCalculator->calculate($prenda);
        }

        return $total;
    }

    public function esSoloEpp(PedidoProduccion $pedido): bool
    {
        $tienePrendas = $pedido->relationLoaded('prendas')
            ? $pedido->prendas->isNotEmpty()
            : $pedido->prendas()->exists();

        if ($tienePrendas) {
            return false;
        }

        return $pedido->relationLoaded('epps')
            ? $pedido->epps->isNotEmpty()
            : $pedido->epps()->exists();
    }

    public function getNovedadesCount(PedidoProduccion $pedido): int
    {
        if (empty($pedido->novedades)) {
            return 0;
        }

        return count(array_filter(explode("\n\n", $pedido->novedades)));
    }

    public function getNombresPrendas(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '-';
        }

        return $pedido->prendas
            ->pluck('nombre_prenda')
            ->unique()
            ->implode(', ') ?: '-';
    }
}
