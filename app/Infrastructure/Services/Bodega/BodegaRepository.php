<?php

namespace App\Infrastructure\Services\Bodega;

use App\Domain\Bodega\Services\BodegaRepositoryContract;

use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BodegaRepository implements BodegaRepositoryContract
{
    /**
     * Obtener pedidos base con estados permitidos
     */
    public function obtenerPedidosBase(array $estadosPermitidos): Collection
    {
        // Agrupar por numero_pedido primero para obtener uno de cada pedido
        $numerosPedidos = ReciboPrenda::with(['asesor'])
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->where(function($q) use ($estadosPermitidos) {
                foreach($estadosPermitidos as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->distinct('numero_pedido')
            ->pluck('numero_pedido');
        
        // Ordenar por la fecha más reciente: cambios en anexos O creación del pedido
        // Si hay anexos recientes → salen de primero
        // Si es un pedido nuevo sin anexos → también sale de primero
        $latestAnexoSubquery = DB::table('pedido_anexos_historial')
            ->select('pedido_produccion_id', DB::raw('MAX(created_at) as latest_anexo_at'))
            ->groupBy('pedido_produccion_id');

        return ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosPedidos)
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->leftJoinSub($latestAnexoSubquery, 'pah_latest', function ($join) {
                $join->on('pah_latest.pedido_produccion_id', '=', 'pedidos_produccion.id');
            })
            ->orderByRaw('COALESCE(pah_latest.latest_anexo_at, pedidos_produccion.created_at) DESC')
            ->orderBy('pedidos_produccion.numero_pedido', 'desc')
            ->select('pedidos_produccion.*')
            ->get();
    }

    /**
     * Obtener recibos de un pedido específico
     */
    public function obtenerRecibosPedido(string $numeroPedido, array $estadosPermitidos): Collection
    {
        return ReciboPrenda::with(['asesor'])
            ->where('numero_pedido', $numeroPedido)
            ->where(function($q) use ($estadosPermitidos) {
                foreach($estadosPermitidos as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->get();
    }

    /**
     * Obtener detalles de bodega por criterios específicos
     */
    public function obtenerDetallesPorCriterios(
        string $numeroPedido,
        string $talla,
        ?string $prendaNombre = null,
        ?int $cantidad = null,
        array $rolesDelUsuario = []
    ) {
        // Buscar en tabla base
        $bodegaDataBase = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('talla', $talla)
            ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
            ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
            ->first();
        
        // Buscar en tabla específica del rol
        $bodegaDataEstado = null;
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $bodegaDataEstado = EppBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
                ->first();
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $bodegaDataEstado = CosturaBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
                ->first();
        }
        
        return [
            'base' => $bodegaDataBase,
            'estado' => $bodegaDataEstado
        ];
    }

    /**
     * Verificar si un pedido tiene detalles en bodega para un área específica
     */
    public function tieneDetallesEnArea(string $numeroPedido, string $area): bool
    {
        $detalles = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('area', $area)
            ->get();
        
        return $detalles->isNotEmpty();
    }

    /**
     * Obtener lista única de asesores para filtros
     */
    public function obtenerAsesoresUnicos(Collection $pedidos): array
    {
        return $pedidos->pluck('asesor.nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Obtener estadísticas para dashboard
     */
    public function obtenerEstadisticasDashboard(): array
    {
        $totalPedidos = ReciboPrenda::whereDate('created_at', \Carbon\Carbon::today())->count();
        $entregadosHoy = ReciboPrenda::where('estado', 'entregado')
            ->whereDate('fecha_entrega_real', \Carbon\Carbon::today())
            ->count();
        $retrasados = ReciboPrenda::where('estado', '!=', 'entregado')
            ->where('fecha_entrega', '<', \Carbon\Carbon::now())
            ->count();

        return [
            'totalPedidos' => $totalPedidos,
            'entregadosHoy' => $entregadosHoy,
            'retrasados' => $retrasados,
        ];
    }

    /**
     * Buscar pedido por número de pedido
     */
    public function buscarPedidoPorNumero(string $numeroPedido): ?PedidoProduccion
    {
        return PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
    }

    /**
     * Obtener detalles anteriores para auditoría
     */
    public function obtenerDetalleAnterior(int $pedidoId, string $numeroPedido, string $talla, ?string $prendaNombre, ?int $cantidad)
    {
        return BodegaDetallesTalla::where('pedido_produccion_id', $pedidoId)
            ->where('numero_pedido', $numeroPedido)
            ->where('talla', $talla)
            ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
            ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
            ->first();
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {BodegaRepository}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
