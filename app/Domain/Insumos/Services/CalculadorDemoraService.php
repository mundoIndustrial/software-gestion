<?php

namespace App\Domain\Insumos\Services;

use App\Domain\Insumos\ValueObjects\DiasDemora;
use App\Services\CalculadorDiasService;

/**
 * Service de Dominio para cálculos relacionados a demora de materiales
 * Encapsula la lógica de negocio de demoras
 * 
 * DDD: Domain Service - Lógica que no pertenece a una Entity
 */
class CalculadorDemoraService
{
    protected CalculadorDiasService $calculadorDias;

    public function __construct(CalculadorDiasService $calculadorDias = null)
    {
        $this->calculadorDias = $calculadorDias ?? app(CalculadorDiasService::class);
    }

    /**
     * Calcular la demora entre dos fechas
     * Retorna un ValueObject con toda la información necesaria
     * 
     * @param string|\DateTime|null $fechaPedido
     * @param string|\DateTime|null $fechaLlegada
     * @return DiasDemora
     */
    public function calcularDemora($fechaPedido, $fechaLlegada): DiasDemora
    {
        // Si falta alguna fecha, retornar 0 días
        if (!$fechaPedido || !$fechaLlegada) {
            return new DiasDemora(0);
        }

        // Calcular días hábiles usando el service
        $dias = $this->calculadorDias->calcularDiasHabiles($fechaPedido, $fechaLlegada) ?? 0;

        return new DiasDemora($dias);
    }

    /**
     * Obtener datos de demora para múltiples materiales
     * 
     * @param array $materiales Array de materiales con fecha_pedido y fecha_llegada
     * @return array
     */
    public function calcularDemoraParaMateriales(array $materiales): array
    {
        return collect($materiales)->map(function ($material) {
            $demora = $this->calcularDemora(
                $material['fecha_pedido'] ?? null,
                $material['fecha_llegada'] ?? null
            );

            return [
                'material_id' => $material['id'] ?? null,
                'demora' => $demora->toArray(),
                'dias' => $demora->getDias(),
                'estado' => $demora->getEstado(),
                'clase_bg' => $demora->getClaseBg(),
                'clase_text' => $demora->getClaseText(),
            ];
        })->toArray();
    }

    /**
     * Obtener un resumen de demoras por estado
     * Útil para dashboards
     * 
     * @param array $materiales
     * @return array
     */
    public function resumirDemorasPorEstado(array $materiales): array
    {
        $demorasCalculadas = $this->calcularDemoraParaMateriales($materiales);

        return [
            'rapido' => collect($demorasCalculadas)->where('estado', DiasDemora::ESTADO_RAPIDO)->count(),
            'normal' => collect($demorasCalculadas)->where('estado', DiasDemora::ESTADO_NORMAL)->count(),
            'lento' => collect($demorasCalculadas)->where('estado', DiasDemora::ESTADO_LENTO)->count(),
            'critico' => collect($demorasCalculadas)->where('estado', DiasDemora::ESTADO_CRITICO)->count(),
            'total' => count($demorasCalculadas),
        ];
    }

    /**
     * Evaluar si una demora es crítica
     * 
     * @param int|DiasDemora $dias
     * @return bool
     */
    public function esCritica($dias): bool
    {
        if ($dias instanceof DiasDemora) {
            return $dias->getEstado() === DiasDemora::ESTADO_CRITICO;
        }

        return $dias > 20;
    }

    /**
     * Evaluar si una demora es normal
     * 
     * @param int|DiasDemora $dias
     * @return bool
     */
    public function esNormal($dias): bool
    {
        if ($dias instanceof DiasDemora) {
            $estado = $dias->getEstado();
            return $estado === DiasDemora::ESTADO_NORMAL || $estado === DiasDemora::ESTADO_RAPIDO;
        }

        return $dias <= 10;
    }
}
