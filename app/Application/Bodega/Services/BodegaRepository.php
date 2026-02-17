<?php

namespace App\Application\Bodega\Services;

use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use App\Models\BodegaNota;
use Illuminate\Support\Collection;

class BodegaRepository
{
    /**
     * Obtener pedidos base con estados permitidos
     */
    public function obtenerPedidosBase(array $estadosPermitidos): Collection
    {
        return ReciboPrenda::with(['asesor'])
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->where(function($q) use ($estadosPermitidos) {
                foreach($estadosPermitidos as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
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
     * Obtener datos básicos de bodega para múltiples pedidos
     */
    public function obtenerDatosBodegaBasicos(): Collection
    {
        return BodegaDetallesTalla::all()
            ->map(function ($item) {
                return $item->toArray();
            })
            ->keyBy(function ($item) {
                return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
            });
    }

    /**
     * Obtener datos de estado específicos por rol
     */
    public function obtenerDatosEstadoRol(array $rolesDelUsuario): Collection
    {
        $datosEstadoRol = collect();
        
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $datosEstadoRol = EppBodegaDetalle::all()
                ->map(function ($item) {
                    return $item->toArray();
                })
                ->keyBy(function ($item) {
                    return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
                });
            
            \Log::info('[BODEGA-DEBUG] EPP-Bodega: cargados ' . $datosEstadoRol->count() . ' registros de estado desde epp_bodega_detalles');
            
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $datosEstadoRol = CosturaBodegaDetalle::all()
                ->map(function ($item) {
                    return $item->toArray();
                })
                ->keyBy(function ($item) {
                    return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
                });
            
            \Log::info('[BODEGA-DEBUG] Costura-Bodega: cargados ' . $datosEstadoRol->count() . ' registros de estado desde costura_bodega_detalles');
        }
        
        return $datosEstadoRol;
    }

    /**
     * Obtener todas las notas de bodega precargadas
     */
    public function obtenerNotasBodega(): Collection
    {
        return BodegaNota::all()
            ->groupBy(function ($item) {
                return $item->numero_pedido . '|' . $item->talla;
            })
            ->map(function ($notas) {
                return $notas->map(function ($nota) {
                    return [
                        'id' => $nota->id,
                        'contenido' => $nota->contenido,
                        'usuario_nombre' => $nota->usuario_nombre,
                        'usuario_rol' => $nota->usuario_rol,
                        'usuario_id' => $nota->usuario_id,
                        'ip_address' => $nota->ip_address,
                        'fecha' => $nota->created_at->format('d/m/Y'),
                        'hora' => $nota->created_at->format('H:i:s'),
                        'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
                        'created_at' => $nota->created_at,
                    ];
                })->sortByDesc('created_at')->values()->toArray();
            });
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
}
