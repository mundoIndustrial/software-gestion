<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ObtenerDatosRecibosService
{
    /**
     * Obtener datos dinámicos de recibos para un pedido
     * Incluye información de procesos de prendas
     * 
     * @param int $pedidoId
     * @return array
     * @throws \Exception
     */
    public function obtener(int $pedidoId): array
    {
        Log::info('[RECIBOS] Obteniendo datos para pedido: ' . $pedidoId);

        // Obtener el pedido (solo PedidoProduccion tiene recibos de procesos)
        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Verificar permisos
        if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        Log::info('[RECIBOS] Pedido encontrado', ['numero_pedido' => $pedido->numero_pedido]);

        // Usar el repository para obtener datos
        $repository = resolve(\App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository::class);
        $datos = $repository->obtenerDatosRecibos($pedidoId);

        Log::info('[RECIBOS] Datos obtenidos correctamente', [
            'prendas_count' => count($datos['prendas'] ?? []),
            'procesos_totales' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? [])),
        ]);

        return $datos;
    }

    /**
     * Obtener datos de un recibo específico por número de prenda
     */
    public function obtenerPorPrenda(int $pedidoId, int $prendaId): array
    {
        Log::info('[RECIBOS-PRENDA] Obteniendo para pedido: ' . $pedidoId . ', prenda: ' . $prendaId);

        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Verificar permisos
        if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        $prenda = $pedido->prendas()->find($prendaId);

        if (!$prenda) {
            throw new \Exception('Prenda no encontrada', 404);
        }

        Log::info('[RECIBOS-PRENDA] Prenda encontrada', [
            'prenda_id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda
        ]);

        return [
            'pedido_numero' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'prenda_id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'cantidad' => $prenda->cantidad,
            'procesos' => $prenda->procesos()->orderBy('created_at', 'desc')->get()->toArray(),
            'fecha_creacion' => $pedido->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Obtener resumen de recibos (cantidad de procesos por estado)
     */
    public function obtenerResumen(int $pedidoId): array
    {
        Log::info('[RECIBOS-RESUMEN] Generando resumen para: ' . $pedidoId);

        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Contar procesos por estado
        $procesos = $pedido->prendas()
            ->with(['procesos' => function ($q) {
                $q->groupBy('estado');
            }])
            ->get()
            ->flatMap->procesos
            ->groupBy('estado')
            ->map->count();

        $totalPrendas = $pedido->prendas()->count();
        $totalProcesos = $pedido->prendas()
            ->with('procesos')
            ->get()
            ->flatMap->procesos
            ->count();

        $resumen = [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'total_prendas' => $totalPrendas,
            'total_procesos' => $totalProcesos,
            'procesos_por_estado' => $procesos->toArray(),
            'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
        ];

        Log::info('[RECIBOS-RESUMEN] Resumen generado', [
            'total_prendas' => $totalPrendas,
            'total_procesos' => $totalProcesos,
            'estados' => array_keys($procesos->toArray()),
        ]);

        return $resumen;
    }

    /**
     * Obtener datos para impresión de recibos
     */
    public function obtenerParaImpresion(int $pedidoId): array
    {
        Log::info('[RECIBOS-IMPRESION] Preparando para imprimir: ' . $pedidoId);

        $datos = $this->obtener($pedidoId);
        $resumen = $this->obtenerResumen($pedidoId);

        return array_merge($datos, ['resumen' => $resumen]);
    }
}
