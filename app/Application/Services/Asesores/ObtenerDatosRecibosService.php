<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ObtenerDatosRecibosService
{
    /**
     * Obtener datos dinÃ¡micos de recibos para un pedido
     * Incluye información de procesos de prendas
     * 
     * @param int $pedidoId
     * @return array
     * @throws \Exception
     */
    public function obtener(int $pedidoId): array
    {
        // Obtener el pedido (solo Pedidos tiene recibos de procesos)
        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Verificar permisos
        if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        // Usar el repository para obtener datos
        $repository = resolve(\App\Domain\Pedidos\Repositories\PedidoProduccionRepository::class);
        $datos = $repository->obtenerDatosRecibos($pedidoId);

        return $datos;
    }

    /**
     * Obtener datos de un recibo especÃ­fico por nÃºmero de prenda
     */
    public function obtenerPorPrenda(int $pedidoId, int $prendaId): array
    {
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
        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Contar procesos por estado
        $procesos = $pedido->prendas()
            ->with(['procesos' => function ($q) {
                $q->withTrashed()->groupBy('estado');
            }])
            ->get()
            ->flatMap->procesos
            ->groupBy('estado')
            ->map->count();

        $totalPrendas = $pedido->prendas()->count();
        $totalProcesos = $pedido->prendas()
            ->with(['procesos' => function ($q) {
                $q->withTrashed();
            }])
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

        return $resumen;
    }

    /**
     * Obtener datos para impresión de recibos
     */
    public function obtenerParaImpresion(int $pedidoId): array
    {
        $datos = $this->obtener($pedidoId);
        $resumen = $this->obtenerResumen($pedidoId);

        return array_merge($datos, ['resumen' => $resumen]);
    }
}

