<?php

namespace App\Domain\Pedidos\Services;

use App\Infrastructure\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Services\CalculadorDiasService;
use App\Domain\Pedidos\Services\PedidoProduccionCalculatorService;

/**
 * Servicio de Dominio para Pedidos de Producción
 * 
 * Responsabilidad: Contener TODA la lógica de negocio relacionada con cálculos
 * y estados de un pedido de producción.
 * 
 * No contiene queries (eso está en el Repository)
 * No contiene HTTP logic (eso está en Controllers)
 * Solo orquesta el dominio.
 */
class PedidoProduccionDomainService
{
    private PedidoProduccionRepository $repository;
    private PedidoProduccionCalculatorService $calculatorService;

    public function __construct()
    {
        $this->repository = new PedidoProduccionRepository();
        $this->calculatorService = new PedidoProduccionCalculatorService();
    }

    /**
     * Calcular fecha estimada de entrega basada en día_de_entrega
     * 
     * @param PedidoProduccion $pedido
     * @return \Carbon\Carbon|null
     */
    public function calcularFechaEstimada(PedidoProduccion $pedido)
    {
        if (!$pedido->created_at || !$pedido->dia_de_entrega) {
            return null;
        }

        return $this->calculatorService->calcularFechaEstimada($pedido->created_at, $pedido->dia_de_entrega);
    }

    /**
     * Obtener el área actual del pedido (con queries optimizadas)
     * 
     * @param PedidoProduccion $pedido
     * @return string Nombre del área/proceso actual
     */
    public function getAreaActual(PedidoProduccion $pedido): string
    {
        return $this->repository->getAreaActual($pedido);
    }

    /**
     * Obtener el área actual de forma optimizada (cuando procesos ya están eager-loaded)
     * 
     * @param PedidoProduccion $pedido
     * @return string
     */
    public function procesoActualOptimizado(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas')) {
            // Fallback: usar el método normal si no están cargadas las relaciones
            return $this->getAreaActual($pedido);
        }
        
        $procesos = $pedido->prendas
            ->flatMap(fn($prenda) => $prenda->procesos ?? collect())
            ->unique('proceso');
        
        return $this->calculatorService->determinarProcesoActualOptimizado($procesos);
    }

    /**
     * Calcular el total de días del pedido desde creación hasta fin
     * 
     * @param PedidoProduccion $pedido
     * @return string|null Formato "5 días" o "1 día" o null si no hay datos
     */
    public function getTotalDias(PedidoProduccion $pedido): ?string
    {
        if (!$pedido->created_at) {
            return null;
        }

        $ultimaFecha = $this->repository->getUltimaFechaProcesoFin($pedido);

        if (!$ultimaFecha) {
            // Si no hay procesos completados, usar hoy como referencia
            $ultimaFecha = now()->toDateString();
        }

        $dias = CalculadorDiasService::calcularDiasHabiles(
            $pedido->created_at,
            $ultimaFecha
        );

        return CalculadorDiasService::formatearDias($dias);
    }

    /**
     * Obtener los días totales como número (int)
     * 
     * @param PedidoProduccion $pedido
     * @return int
     */
    public function getTotalDiasNumero(PedidoProduccion $pedido): int
    {
        $totalDias = $this->getTotalDias($pedido);
        
        if (!$totalDias) {
            return 0;
        }

        preg_match('/(\d+)/', $totalDias, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    /**
     * Obtener el desglose de días por proceso
     * 
     * @param PedidoProduccion $pedido
     * @return array Mapa [nombreProceso => "X días"]
     */
    public function getDesgloseDiasPorProceso(PedidoProduccion $pedido): array
    {
        return $this->repository->getDesgloseDiasPorProceso($pedido);
    }

    /**
     * Validar si el pedido está en retraso
     * 
     * @param PedidoProduccion $pedido
     * @return bool
     */
    public function estaEnRetraso(PedidoProduccion $pedido): bool
    {
        $areaActual = $this->getAreaActual($pedido);
        return CalculadorDiasService::estaEnRetraso($areaActual, $pedido->fecha_estimada_de_entrega);
    }

    /**
     * Obtener los días de retraso
     * 
     * @param PedidoProduccion $pedido
     * @return int Número de días retrasados (0 si no está retrasado)
     */
    public function getDiasDeRetraso(PedidoProduccion $pedido): int
    {
        if (!$this->estaEnRetraso($pedido)) {
            return 0;
        }

        return CalculadorDiasService::calcularDiasDeRetraso($pedido->fecha_estimada_de_entrega);
    }

    /**
     * Calcular días hábiles desde la creación del pedido hasta hoy
     * 
     * @param PedidoProduccion $pedido
     * @return string Formato "5 días" o "1 día" o "-" si no hay fechas
     */
    public function calcularDiasHabilesDesdeCreacion(PedidoProduccion $pedido): string
    {
        if (!$pedido->created_at) {
            return '-';
        }

        $fechaFin = now();

        // Si el estado es "Entregado", buscar la fecha del proceso "Despacho"
        $fechaDespacho = $this->repository->getFechaDespachoSiEntregado($pedido);
        if ($fechaDespacho) {
            $fechaFin = $fechaDespacho;
        }

        $diasCalculados = CalculadorDiasService::calcularDiasHabilesSinIncluirInicio($pedido->created_at);

        return $diasCalculados > 0 ? $diasCalculados . ' día' . ($diasCalculados > 1 ? 's' : '') : '-';
    }

    /**
     * Obtener descripción completa de prendas del pedido
     * 
     * @param PedidoProduccion $pedido
     * @return string
     */
    public function getDescripcionPrendas(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '';
        }

        // Generar descripción detallada para TODAS las prendas
        $descripciones = $pedido->prendas->map(function($prenda, $index) {
            return $prenda->generarDescripcionDetallada($index + 1);
        })->toArray();

        return implode("\n\n", $descripciones);
    }

    /**
     * Obtener cantidad total de prendas del pedido
     * 
     * @param PedidoProduccion $pedido
     * @return int
     */
    public function getCantidadTotal(PedidoProduccion $pedido): int
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($pedido->prendas as $prenda) {
            $total += $prenda->cantidad_total;
        }
        return $total;
    }

    /**
     * Determinar si el pedido tiene solo EPP (sin prendas regulares)
     * 
     * @param PedidoProduccion $pedido
     * @return bool
     */
    public function esSoloEpp(PedidoProduccion $pedido): bool
    {
        $tienePrendas = $pedido->relationLoaded('prendas')
            ? $pedido->prendas->isNotEmpty()
            : $pedido->prendas()->exists();

        if ($tienePrendas) {
            return false;
        }

        $tieneEpps = $pedido->relationLoaded('epps')
            ? $pedido->epps->isNotEmpty()
            : $pedido->epps()->exists();

        return $tieneEpps;
    }

    /**
     * Obtener cantidad de novedades registradas en el pedido
     * 
     * @param PedidoProduccion $pedido
     * @return int
     */
    public function getNovedadesCount(PedidoProduccion $pedido): int
    {
        if (empty($pedido->novedades)) {
            return 0;
        }

        return count(array_filter(explode("\n\n", $pedido->novedades)));
    }

    /**
     * Obtener nombres de prendas separados por coma
     * 
     * @param PedidoProduccion $pedido
     * @return string
     */
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
