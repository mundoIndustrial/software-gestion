<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;

/**
 * Filtros Service
 * 
 * Maneja el filtrado de registros por fecha y otros criterios.
 * Responsable de:
 * - Filtrar por rango de fechas
 * - Filtrar por día específico
 * - Filtrar por mes
 * - Filtrar por fechas específicas
 */
class FiltrosService extends BaseService
{
    /**
     * Filtrar registros por fecha
     * 
     * Soporta múltiples tipos de filtro:
     * - 'range': rango de fechas (start_date y end_date)
     * - 'day': día específico
     * - 'month': mes específico
     * - 'specific': fechas específicas (comma-separated)
     */
    public function filtrarRegistrosPorFecha($registros, Request $request)
    {
        $filterType = $request->get('filter_type');

        $this->log('Filtrando registros por fecha', [
            'filter_type' => $filterType,
            'total_registros_antes' => count($registros),
        ]);

        if (!$filterType || $filterType === 'range') {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate && $endDate) {
                $resultado = $registros->filter(function($registro) use ($startDate, $endDate) {
                    $fecha = $registro->fecha->format('Y-m-d');
                    return $fecha >= $startDate && $fecha <= $endDate;
                });

                $this->log('Filtro de rango aplicado', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'registros_resultantes' => count($resultado),
                ]);

                return $resultado;
            }
        } elseif ($filterType === 'day') {
            $specificDate = $request->get('specific_date');
            if ($specificDate) {
                $resultado = $registros->filter(function($registro) use ($specificDate) {
                    return $registro->fecha->format('Y-m-d') == $specificDate;
                });

                $this->log('Filtro de día aplicado', [
                    'specific_date' => $specificDate,
                    'registros_resultantes' => count($resultado),
                ]);

                return $resultado;
            }
        } elseif ($filterType === 'month') {
            $month = $request->get('month');
            if ($month) {
                // Formato esperado: YYYY-MM
                $year = substr($month, 0, 4);
                $monthNum = substr($month, 5, 2);
                $startOfMonth = "{$year}-{$monthNum}-01";
                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
                
                $resultado = $registros->filter(function($registro) use ($startOfMonth, $endOfMonth) {
                    $fecha = $registro->fecha->format('Y-m-d');
                    return $fecha >= $startOfMonth && $fecha <= $endOfMonth;
                });

                $this->log('Filtro de mes aplicado', [
                    'month' => $month,
                    'registros_resultantes' => count($resultado),
                ]);

                return $resultado;
            }
        } elseif ($filterType === 'specific') {
            $specificDates = $request->get('specific_dates');
            if ($specificDates) {
                $dates = explode(',', $specificDates);
                $resultado = $registros->filter(function($registro) use ($dates) {
                    return in_array($registro->fecha->format('Y-m-d'), $dates);
                });

                $this->log('Filtro de fechas específicas aplicado', [
                    'cantidad_fechas' => count($dates),
                    'registros_resultantes' => count($resultado),
                ]);

                return $resultado;
            }
        }

        // Si no hay filtro válido, devolver todos los registros
        $this->log('Sin filtro aplicado, devolviendo todos los registros');
        return $registros;
    }
}
