<?php

namespace App\Application\Pedidos\UseCases;

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ObtenerProcesosPorPedidoUseCase
 * 
 * Caso de uso para obtener todos los procesos de un pedido con cÃ¡lculo de dÃ­as hÃ¡biles
 * Responsabilidad: Orquestar la obtención de procesos e información relacionada
 * 
 * Patrón: Use Case (Application Layer - DDD)
 */
class ObtenerProcesosPorPedidoUseCase
{
    /**
     * Ejecutar caso de uso
     * 
     * @param int|string $id - nÃºmero de pedido o ID
     * @return array - Datos del pedido con procesos
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function ejecutar($id): array
    {
        // Buscar por numero_pedido o id
        $orden = PedidoProduccion::where('numero_pedido', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        // Obtener festivos
        $festivos = Festivo::pluck('fecha')->toArray();

        // Obtener procesos ordenados por fecha_inicio (solo procesos_prenda sin soft-delete)
        $procesos = DB::table('procesos_prenda')
            ->where('numero_pedido', $orden->numero_pedido)
            ->whereNull('deleted_at')
            ->orderBy('fecha_inicio', 'asc')
            ->select('id', 'numero_pedido', 'proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
            ->get()
            ->groupBy('proceso')
            ->map(function($grupo) {
                return $grupo->first();
            })
            ->values();

        // Calcular dÃ­as hÃ¡biles totales
        $totalDiasHabiles = $this->calcularDiasHabilesBatch(
            $procesos->count() > 0 
                ? Carbon::parse($procesos->first()->fecha_inicio)
                : Carbon::now(),
            $this->obtenerFechaFinal($procesos),
            $festivos
        );

        return [
            'numero_pedido' => $orden->numero_pedido,
            'cliente' => $orden->cliente,
            'fecha_inicio' => $orden->fecha_de_creacion_de_orden,
            'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
            'procesos' => $procesos->toArray(),
            'total_dias_habiles' => $totalDiasHabiles,
            'festivos' => $festivos
        ];
    }

    /**
     * Obtener la fecha final para el cÃ¡lculo de dÃ­as hÃ¡biles
     */
    private function obtenerFechaFinal($procesos): Carbon
    {
        if ($procesos->isEmpty()) {
            return Carbon::now();
        }

        $procesoDespachos = $procesos->firstWhere('proceso', 'Despachos') 
            ?? $procesos->firstWhere('proceso', 'Entrega')
            ?? $procesos->firstWhere('proceso', 'Despacho');

        if ($procesoDespachos) {
            return Carbon::parse($procesoDespachos->fecha_inicio);
        }

        return $procesos->count() > 1
            ? Carbon::parse($procesos->last()->fecha_inicio)
            : Carbon::now();
    }

    /**
     * Calcular dÃ­as hÃ¡biles entre dos fechas
     */
    private function calcularDiasHabilesBatch(Carbon $inicio, Carbon $fin, array $festivos): int
    {
        $diasCalculados = 0;
        $actual = $inicio->copy();

        while ($actual <= $fin) {
            // No es sÃ¡bado (6) ni domingo (0)
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6) {
                // No es festivo
                $dateString = $actual->format('Y-m-d');
                $isFestivo = in_array(
                    $dateString,
                    array_map(fn($f) => Carbon::parse($f)->format('Y-m-d'), $festivos)
                );

                if (!$isFestivo) {
                    $diasCalculados++;
                }
            }
            $actual->addDay();
        }

        return max(0, $diasCalculados - 1);
    }
}


