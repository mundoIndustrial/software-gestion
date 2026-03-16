<?php

namespace App\Infrastructure\Services\RegistrosOrdenes;

use App\Domain\RegistrosOrdenes\Contracts\SeguimientoOrdenService;
use App\Models\ConsecutivosRecibosPedidos;
use App\Models\ProcesoPrenda;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use Illuminate\Support\Facades\Log;

/**
 * SeguimientoOrdenServiceImpl
 * 
 * Implementación del servicio de seguimiento
 * Extrae lógica de cálculo de procesos y consecutivos
 */
class SeguimientoOrdenServiceImpl implements SeguimientoOrdenService
{
    public function obtenerSeguimientoPorPrenda($registroId): array
    {
        $prendas = \App\Models\PrendaPedido::where('pedido_produccion_id', $registroId)
            ->with(['variantes', 'procesos.tipoProceso', 'tallas'])
            ->get();

        $resultado = [];

        foreach ($prendas as $prenda) {
            $siguientes = $this->construirSeguimientosPrenda($prenda, $registroId);
            $resultado[] = $siguientes;
        }

        return $resultado;
    }

    public function obtenerConsecutivoCostura($registroId, $prendaId = null): ?array
    {
        $query = ConsecutivosRecibosPedidos::where('pedido_produccion_id', $registroId)
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1);

        if ($prendaId) {
            $query->where('prenda_id', $prendaId);
        }

        $registro = $query->orderByDesc('id')->first();

        if (!$registro) {
            return null;
        }

        return [
            'consecutivo' => $registro->consecutivo_actual,
            'area' => $registro->area,
            'prenda_id' => $registro->prenda_id,
            'tipo' => $registro->tipo_recibo,
        ];
    }

    public function calcularProcesosConFechas($numeroPedido): array
    {
        $procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->with('tipoProceso')
            ->orderBy('id', 'asc')
            ->get();

        $resultado = [];
        foreach ($procesos as $idx => $proceso) {
            $item = $proceso->toArray();
            
            // Calcular fecha fin: created_at del siguiente proceso
            if ($idx < count($procesos) - 1) {
                $siguienteProceso = $procesos[$idx + 1];
                $item['fecha_fin'] = $siguienteProceso->created_at;
            } else {
                $item['fecha_fin'] = null;
            }

            $resultado[] = $item;
        }

        return $resultado;
    }

    public function obtenerUltimoProceso($numeroPedido): ?array
    {
        $proceso = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->with('tipoProceso')
            ->orderByDesc('id')
            ->first();

        return $proceso ? $proceso->toArray() : null;
    }

    private function construirSeguimientosPrenda($prenda, $registroId): array
    {
        $consecutivos = ConsecutivosRecibosPedidos::where('prenda_id', $prenda->id)->get();

        $cantidadTalla = [];
        foreach ($prenda->tallas as $talla) {
            $cantidadTalla[] = [
                'talla' => $talla->talla,
                'cantidad' => $talla->cantidad,
            ];
        }

        $procesosArray = [];
        foreach ($prenda->procesos as $proceso) {
            $procesosArray[] = [
                'tipo' => $proceso->tipoProceso->nombre ?? $proceso->tipo,
                'estado' => $proceso->estado,
            ];
        }

        return [
            'prenda_id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
            'cantidad_talla' => $cantidadTalla,
            'procesos' => $procesosArray,
            'consecutivos' => $consecutivos->toArray(),
        ];
    }
}
