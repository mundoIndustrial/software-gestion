<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * RegistroOrdenProcessesService
 * 
 * Responsabilidad: Obtener y formatear procesos de una orden
 * Extrae la lógica de consultas de procesos del controlador
 * 
 * CUMPLE SRP: Solo maneja procesos
 */
class RegistroOrdenProcessesService
{
    /**
     * Obtener procesos de una orden agrupados por tipo
     * 
     * @param int $numeroPedido - Número de pedido
     * @return object - Procesos agrupados y formateados
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOrderProcesses(int $numeroPedido): object
    {
        // Obtener la orden (validates existence)
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

        // Obtener festivos
        $festivos = Festivo::pluck('fecha')->toArray();

        // Obtener procesos ordenados por fecha_inicio
        $procesos = $this->fetchProcessesFromDatabase($numeroPedido);

        // Calcular días hábiles totales
        $totalDiasHabiles = $this->calculateWorkingDays($procesos, $festivos);

        return (object) [
            'numero_pedido' => $numeroPedido,
            'cliente' => $orden->cliente ?? '',
            'fecha_inicio' => $orden->fecha_de_creacion_de_orden ?? null,
            'fecha_estimada_de_entrega' => $orden->fecha_estimada_entrega ?? null,
            'procesos' => $procesos,
            'total_dias_habiles' => $totalDiasHabiles,
            'festivos' => $festivos
        ];
    }

    /**
     * Obtener procesos desde BD, agrupados por tipo
     * 
     * @param int $numeroPedido - Número de pedido
     * @return \Illuminate\Support\Collection
     */
    private function fetchProcessesFromDatabase(int $numeroPedido)
    {
        return DB::table('procesos_prenda')
            ->where('numero_pedido', $numeroPedido)
            ->whereNull('deleted_at')  // Excluir soft-deleted
            ->orderBy('fecha_inicio', 'asc')
            ->select('id', 'proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
            ->get()
            ->groupBy('proceso')
            ->map(function($grupo) {
                return $grupo->first();
            })
            ->values();
    }

    /**
     * Calcular días hábiles entre procesos
     * 
     * @param \Illuminate\Support\Collection $procesos - Procesos de la orden
     * @param array $festivos - Días feriados
     * @return int - Días hábiles totales
     */
    private function calculateWorkingDays($procesos, array $festivos): int
    {
        if ($procesos->count() === 0) {
            return 0;
        }

        $fechaInicio = Carbon::parse($procesos->first()->fecha_inicio);

        // Buscar proceso de entrega/despacho
        $procesoDespachos = $procesos->firstWhere('proceso', 'Despachos')
            ?? $procesos->firstWhere('proceso', 'Entrega')
            ?? $procesos->firstWhere('proceso', 'Despacho');

        if ($procesoDespachos) {
            $fechaFin = Carbon::parse($procesoDespachos->fecha_inicio);
        } elseif ($procesos->count() > 1) {
            $fechaFin = Carbon::parse($procesos->last()->fecha_inicio);
        } else {
            $fechaFin = Carbon::now();
        }

        return $this->calculateWorkingDaysBetween($fechaInicio, $fechaFin, $festivos);
    }

    /**
     * Calcular días hábiles entre dos fechas
     * 
     * @param Carbon $start - Fecha inicio
     * @param Carbon $end - Fecha fin
     * @param array $festivos - Días feriados (formato: Y-m-d)
     * @return int
     */
    private function calculateWorkingDaysBetween(Carbon $start, Carbon $end, array $festivos): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $weekends = $this->countWeekendsBetween($start, $end);

        // Contar festivos dentro del rango
        $festivosInRange = 0;
        $current = $start->copy();
        
        while ($current <= $end) {
            if (in_array($current->format('Y-m-d'), $festivos)) {
                $festivosInRange++;
            }
            $current->addDay();
        }

        return $totalDays - $weekends - $festivosInRange;
    }

    /**
     * Contar fines de semana entre dos fechas
     * 
     * @param Carbon $start - Fecha inicio
     * @param Carbon $end - Fecha fin
     * @return int
     */
    private function countWeekendsBetween(Carbon $start, Carbon $end): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $startDay = $start->dayOfWeek; // 0=Domingo, 6=Sábado

        $fullWeeks = floor($totalDays / 7);
        $extraDays = $totalDays % 7;

        $weekends = $fullWeeks * 2; // 2 fines de semana por semana completa

        // Contar fines de semana en días extra
        for ($i = 0; $i < $extraDays; $i++) {
            $day = ($startDay + $i) % 7;
            if ($day === 0 || $day === 6) $weekends++; // Domingo o Sábado
        }

        return $weekends;
    }
}
