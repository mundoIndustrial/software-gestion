<?php

namespace App\Infrastructure\Repositories;

use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Services\CalculadorDiasService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio de Pedidos de Producción (Capa de Infraestructura)
 * 
 * Responsabilidad: Encapsular TODAS las queries de base de datos
 * No contiene lógica de negocio, solo acceso a datos.
 * 
 * Los métodos aquí evitan que PedidoProduccion.php tenga queries explícitas.
 */
class PedidoProduccionTrackingRepository
{
    /**
     * Obtener el área/proceso actual del pedido
     * 
     * @param PedidoProduccion $pedido
     * @return string El nombre del último proceso registrado
     */
    public function getAreaActual(PedidoProduccion $pedido): string
    {
        $ultimoProceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($ultimoProceso) {
            return $ultimoProceso->proceso;
        }

        return 'Creación Orden';
    }

    /**
     * Obtener la última fecha de fin de procesos
     * 
     * @param PedidoProduccion $pedido
     * @return string|null Fecha en formato string o null
     */
    public function getUltimaFechaProcesoFin(PedidoProduccion $pedido): ?string
    {
        return ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->whereNotNull('fecha_fin')
            ->max('fecha_fin');
    }

    /**
     * Obtener desglose de días por proceso
     * 
     * @param PedidoProduccion $pedido
     * @return array Mapa de [nombreProceso => "X días"]
     */
    public function getDesgloseDiasPorProceso(PedidoProduccion $pedido): array
    {
        $procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->whereNotNull('fecha_fin')
            ->with('prenda')
            ->get()
            ->groupBy('proceso');

        $desglose = [];

        foreach ($procesos as $nombreProceso => $procesoGroup) {
            $totalDiasProc = 0;

            foreach ($procesoGroup as $proceso) {
                if ($proceso->fecha_inicio && $proceso->fecha_fin) {
                    $dias = CalculadorDiasService::calcularDiasHabiles(
                        $proceso->fecha_inicio,
                        $proceso->fecha_fin
                    );
                    $totalDiasProc += $dias;
                }
            }

            if ($totalDiasProc > 0) {
                $desglose[$nombreProceso] = CalculadorDiasService::formatearDias($totalDiasProc);
            }
        }

        return $desglose;
    }

    /**
     * Obtener la fecha del proceso "Despacho" si el pedido está entregado
     * 
     * @param PedidoProduccion $pedido
     * @return Carbon|null Fecha de inicio del proceso Despacho o null
     */
    public function getFechaDespachoSiEntregado(PedidoProduccion $pedido): ?Carbon
    {
        if ($pedido->estado !== 'Entregado') {
            return null;
        }

        $procesoDespacho = DB::table('procesos_prenda')
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where('proceso', 'Despacho')
            ->select('fecha_inicio')
            ->first();

        if ($procesoDespacho && $procesoDespacho->fecha_inicio) {
            return Carbon::parse($procesoDespacho->fecha_inicio);
        }

        return null;
    }

    /**
     * Obtener pedido por ID o número de pedido
     * @param string|int $identificador ID numérico o número de pedido
     */
    public function obtenerPorIdONumero($identificador): ?PedidoProduccion
    {
        if (is_numeric($identificador)) {
            $pedido = PedidoProduccion::where('id', $identificador)->first();
            if ($pedido) {
                return $pedido;
            }
        }

        return PedidoProduccion::where('numero_pedido', $identificador)->first();
    }

    /**
     * Obtener pedido por ID
     */
    public function obtenerPorId(int $id): ?PedidoProduccion
    {
        return PedidoProduccion::find($id);
    }

    /**
     * Obtener pedido por número
     */
    public function obtenerPorNumero(string $numero): ?PedidoProduccion
    {
        return PedidoProduccion::where('numero_pedido', $numero)->first();
    }

    /**
     * Obtener pedido sin número (el más reciente)
     */
    public function obtenerSinNumero(): ?PedidoProduccion
    {
        return PedidoProduccion::whereNull('numero_pedido')
            ->orWhere('numero_pedido', '')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Obtener descripción de prendas de un pedido
     */
    public function obtenerDescripcionPrendas(string $numeroPedido): ?string
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->orWhere('id', $numeroPedido)
            ->first();

        return $pedido ? ($pedido->descripcion_prendas ?? '') : null;
    }

    /**
     * Obtener festivos para cálculos
     */
    public function obtenerFestivos(): array
    {
        return DB::table('festivos')
            ->pluck('fecha')
            ->toArray();
    }
}
