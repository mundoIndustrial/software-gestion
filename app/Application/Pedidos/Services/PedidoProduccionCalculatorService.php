<?php

namespace App\Application\Pedidos\Services;

use Carbon\Carbon;

/**
 * Servicio para calculos y logica de procesos de pedidos
 * Este servicio maneja:
 * - Calculo de fecha estimada (con festivos)
 * - Determinacion del proceso actual con prioridades
 * - Toda la logica que NO es queries puras
 */
class PedidoProduccionCalculatorService
{
    /**
     * Calcular fecha estimada de entrega basada en dias habiles
     * @param Carbon $fechaCreacion
     * @param int $diasEntrega
     * @return Carbon|null
     */
    public function calcularFechaEstimada(Carbon $fechaCreacion, int $diasEntrega): ?Carbon
    {
        if (!$fechaCreacion || !$diasEntrega) {
            return null;
        }

        try {
            $fechaInicio = $fechaCreacion->copy();
            $diasAnadir = intval($diasEntrega);

            // Contar dias habiles usando cmixin/business-day (co-national)
            $fecha = $fechaInicio->copy();
            $diasContados = 0;

            while ($diasContados < $diasAnadir) {
                $fecha->addDay();

                if (!$fecha->isBusinessDay()) {
                    continue;
                }

                $diasContados++;
            }

            return $fecha;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determinar el proceso actual con prioridades
     * Asume que $procesos es una Collection ya cargada en memoria
     * (tipicamente desde eager loading)
     * @param \Illuminate\Support\Collection $procesos
     * @return string
     */
    public function determinarProcesoActualOptimizado($procesos): string
    {
        if ($procesos->isEmpty()) {
            return 'Pendiente';
        }

        // Orden de prioridad de procesos
        $procesosPrioritarios = [
            'Despacho',
            'Insumos y Telas',
            'Costura',
            'Corte',
            'Control Calidad',
            'Creacion de Orden',
            'tcc'
        ];

        // Prioridad 1: Buscar proceso "En Progreso" (que sea uno de los principales)
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('estado_proceso', 'En Progreso')
                ->where('proceso', $nombreProceso)
                ->first();

            if ($proceso) {
                return $proceso->proceso;
            }
        }

        // Prioridad 2: Buscar proceso "Pendiente" (que sea uno de los principales)
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('estado_proceso', 'Pendiente')
                ->where('proceso', $nombreProceso)
                ->first();

            if ($proceso) {
                return $proceso->proceso;
            }
        }

        // Prioridad 3: Buscar cualquier proceso que NO este completado
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('proceso', $nombreProceso)
                ->whereNotIn('estado_proceso', ['Completado', 'Pausado'])
                ->first();

            if ($proceso) {
                return $proceso->proceso;
            }
        }

        // Prioridad 4: El ultimo proceso creado
        $ultimoProceso = $procesos->sortByDesc('created_at')->first();

        return $ultimoProceso?->proceso ?? 'Pendiente';
    }
}