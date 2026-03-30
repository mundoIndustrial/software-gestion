<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\ProcesoPedidoReadRepository;
use App\Domain\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCaseContract;
use App\Models\Festivo;
use App\Models\PedidoProduccion;
use Carbon\Carbon;

/**
 * ObtenerProcesosPorPedidoUseCase
 * Caso de uso para obtener todos los procesos de un pedido con calculo de dias habiles
 * Responsabilidad: Orquestar la obtencion de procesos e informacion relacionada
 * Patron: Use Case (Application Layer - DDD)
 */
class ObtenerProcesosPorPedidoUseCase implements ObtenerProcesosPorPedidoUseCaseContract
{
    public function __construct(
        private readonly ProcesoPedidoReadRepository $procesoPedidoReadRepository
    ) {
    }

    /**
     * Ejecutar caso de uso
     * @param int|string $id - numero de pedido o ID
     * @param int|null $prendaId - ID de la prenda especifica (opcional)
     * @return array - Datos del pedido con procesos
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function ejecutar($id, $prendaId = null): array
    {
        // Buscar por numero_pedido o id
        $orden = PedidoProduccion::where('numero_pedido', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        // Obtener festivos
        $festivos = Festivo::pluck('fecha')->toArray();

        $procesos = collect(
            $this->procesoPedidoReadRepository
                ->obtenerProcesosPorNumeroPedidoYPrenda($orden->numero_pedido, $prendaId)
        )
            ->groupBy('proceso')
            ->map(function ($grupo) {
                // Mantener el registro mas reciente de cada proceso
                // (al venir ordenado ASC, el ultimo es el mas nuevo)
                return $grupo->last();
            })
            ->values();

        // Calcular dias habiles totales
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
            'fecha_inicio' => $orden->created_at,
            'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
            'procesos' => $procesos->toArray(),
            'total_dias_habiles' => $totalDiasHabiles,
            'festivos' => $festivos,
        ];
    }

    /**
     * Obtener la fecha final para el calculo de dias habiles
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
     * Calcular dias habiles entre dos fechas
     */
    private function calcularDiasHabilesBatch(Carbon $inicio, Carbon $fin, array $festivos): int
    {
        $diasCalculados = 0;
        $actual = $inicio->copy();

        while ($actual <= $fin) {
            // No es sabado (6) ni domingo (0)
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

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {ObtenerProcesosPorPedidoUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
