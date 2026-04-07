<?php

namespace App\Infrastructure\Repositories\Bodega;

use App\Domain\BodegaDetalleTalla\Repositories\BodegaDetalleTallaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BodegaDetalleTallaRepository implements BodegaDetalleTallaRepositoryInterface
{
    public function contarPendientesAsesor(string $asesorNombre): int
    {
        // Excluir registros que apuntan a EPPs que fueron soft-deleted (homologados)
        return DB::table('bodega_detalles_talla as bdt')
            ->leftJoin('pedido_epp as pe', 'bdt.pedido_epp_id', '=', 'pe.id')
            ->select('numero_pedido')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->where('estado_bodega', 'Pendiente')
            ->where('asesor', 'like', "%{$asesorNombre}%")
            ->whereNull('bdt.deleted_at')
            // Excluir registros cuyo pedido_epp_id apunta a un EPP que fue soft-deleted (homologado)
            ->where(function ($query) {
                $query->whereNull('bdt.pedido_epp_id')  // Sin pedido_epp_id (prendas)
                    ->orWhereNull('pe.deleted_at');    // O el EPP está vigente (no fue homologado)
            })
            ->distinct()
            ->count('numero_pedido');
    }

    public function obtenerPendientesAsesor(
        string $asesorNombre,
        string $search = '',
        string $tipo = 'todos',
        int $page = 1,
        int $perPage = 20
    ): array {
        // Consultar bodega_detalles_talla filtrado por asesor y estado Pendiente
        // IMPORTANTE: Excluir registros que apuntan a EPPs que fueron homologados (soft-deleted en pedido_epp)
        $query = DB::table('bodega_detalles_talla as bdt')
            ->leftJoin('pedidos_produccion as pp', 'bdt.pedido_produccion_id', '=', 'pp.id')
            ->leftJoin('pedido_epp as pe', 'bdt.pedido_epp_id', '=', 'pe.id')
            ->select('bdt.*', 'pp.created_at as pedido_fecha_creacion')
            ->whereNotNull('bdt.numero_pedido')
            ->where('bdt.numero_pedido', '!=', '')
            ->where('bdt.estado_bodega', 'Pendiente')
            ->whereNull('bdt.deleted_at')
            // Excluir registros cuyo pedido_epp_id apunta a un EPP que fue soft-deleted (homologado)
            ->where(function ($query) {
                $query->whereNull('bdt.pedido_epp_id')  // Sin pedido_epp_id (prendas)
                    ->orWhereNull('pe.deleted_at');    // O el EPP está vigente (no fue homologado)
            });
        
        // Filtrar por asesor
        if ($asesorNombre) {
            $query->where('bdt.asesor', 'like', "%{$asesorNombre}%");
        }
        
        // Búsqueda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bdt.numero_pedido', 'like', "%{$search}%")
                  ->orWhere('bdt.empresa', 'like', "%{$search}%")
                  ->orWhere('bdt.prenda_nombre', 'like', "%{$search}%");
            });
        }
        
        // Obtener todos los datos
        $detalles = $query->orderBy('pp.created_at', 'desc')->get();
        
        // Agrupar por número de pedido
        $pedidosAgrupados = $detalles->groupBy('numero_pedido')->map(function ($items, $numeroPedido) {
            $primerItem = $items->first();
            $totalItems = $items->count();
            // Contar ITEMS ÚNICOS (por EPP o Prenda), no registros (que pueden tener múltiples tallas/colores)
            $itemsUnicos = $items->groupBy(function ($item) {
                return $item->pedido_epp_id ?? $item->prenda_id;
            })->count();
            $totalCantidad = $items->sum(function($item) {
                return is_numeric($item->cantidad) ? (int)$item->cantidad : 0;
            });
            
            $areas = $items->pluck('area')->unique()->filter()->values()->toArray();
            $tipoDisplay = implode(' + ', $areas);
            
            return [
                'id' => $primerItem->pedido_produccion_id ?? 0,
                'numero_pedido' => $numeroPedido,
                'cliente' => $primerItem->empresa ?? 'Sin Empresa',
                'asesor' => $primerItem->asesor ?? '',
                'estado' => $primerItem->estado_bodega ?? 'Pendiente',
                'fecha_creacion' => $primerItem->pedido_fecha_creacion ? Carbon::parse($primerItem->pedido_fecha_creacion)->format('d/m/Y') : '-',
                'fecha_entrega' => $primerItem->fecha_entrega ? Carbon::parse($primerItem->fecha_entrega)->format('d/m/Y') : '',
                'tipo' => $tipoDisplay,
                'total_items' => $totalItems,
                'total_pendientes' => $itemsUnicos,
                'total_cantidad' => $totalCantidad,
                'areas' => $areas,
                'detalles' => $items->map(function($item) {
                    return [
                        'prenda' => $item->prenda_nombre,
                        'talla' => $item->talla,
                        'cantidad' => $item->cantidad,
                        'pendientes' => $item->pendientes,
                        'area' => $item->area,
                        'estado_costura' => $item->costura_estado,
                        'estado_epp' => $item->epp_estado,
                        'observaciones' => $item->observaciones_bodega
                    ];
                })->toArray()
            ];
        })->values();
        
        // Filtrar por tipo de área después de agrupar
        if ($tipo === 'costura') {
            $pedidosAgrupados = $pedidosAgrupados->filter(function($pedido) {
                return collect($pedido['detalles'])->some(function($detalle) {
                    return $detalle['area'] === 'Costura' && $detalle['estado_costura'] === 'Pendiente';
                });
            })->values();
        } elseif ($tipo === 'epp') {
            $pedidosAgrupados = $pedidosAgrupados->filter(function($pedido) {
                return collect($pedido['detalles'])->some(function($detalle) {
                    return $detalle['area'] === 'EPP' && $detalle['estado_epp'] === 'Pendiente';
                });
            })->values();
        }
        
        // Paginación manual
        $total = $pedidosAgrupados->count();
        $pedidosPaginados = $pedidosAgrupados->forPage($page, $perPage);
        
        return [
            'data' => $pedidosPaginados->values()->toArray(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]
        ];
    }
}
