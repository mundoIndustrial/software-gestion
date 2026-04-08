<?php

namespace App\Application\Pedidos\Services;

use App\Models\Festivo;
use Carbon\Carbon;

/**
 * Servicio para cálculos y lógica de procesos de pedidos
 * Este servicio maneja:
 * - Cálculo de fecha estimada (con festivos)
 * - Determinación del proceso actual con prioridades
 * - Toda la lógica que NO es queries puras
 */
class PedidoProduccionCalculatorService
{
    /**
     * Calcular fecha estimada de entrega basada en días hábiles
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
            $diasAñadir = intval($diasEntrega);
            
            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Contar días hábiles
            $fecha = $fechaInicio->copy();
            $diasContados = 0;
            
            while ($diasContados < $diasAñadir) {
                $fecha->addDay();
                
                // Saltar fines de semana
                if ($fecha->isWeekend()) {
                    continue;
                }
                
                // Saltar festivos
                if (in_array($fecha->format('Y-m-d'), $festivos)) {
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
     * (típicamente desde eager loading)
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
            'Creación de Orden',
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
        
        // Prioridad 3: Buscar cualquier proceso que NO esté completado
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('proceso', $nombreProceso)
                ->whereNotIn('estado_proceso', ['Completado', 'Pausado'])
                ->first();
            
            if ($proceso) {
                return $proceso->proceso;
            }
        }
        
        // Prioridad 4: El último proceso creado
        $ultimoProceso = $procesos->sortByDesc('created_at')->first();
        
        return $ultimoProceso?->proceso ?? 'Pendiente';
    }
}
