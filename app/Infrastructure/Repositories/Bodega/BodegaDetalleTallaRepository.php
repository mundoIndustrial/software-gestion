<?php

namespace App\Infrastructure\Repositories\Bodega;

use App\Domain\BodegaDetalleTalla\Repositories\BodegaDetalleTallaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BodegaDetalleTallaRepository implements BodegaDetalleTallaRepositoryInterface
{
    public function contarPendientesAsesor(string $asesorNombre): int
    {
        // Excluir registros que apuntan a EPPs que fueron soft-deleted (homologados)
        return DB::table('bodega_detalles_talla as bdt')
            ->leftJoin('pedido_epp as pe', 'bdt.pedido_epp_id', '=', 'pe.id')
            ->leftJoin('pedidos_produccion as pp', 'bdt.numero_pedido', '=', 'pp.numero_pedido')
            ->select('bdt.numero_pedido')
            ->whereNotNull('bdt.numero_pedido')
            ->where('bdt.numero_pedido', '!=', '')
            ->where('bdt.estado_bodega', 'Pendiente')
            ->where('bdt.asesor', 'like', "%{$asesorNombre}%")
            ->whereNull('bdt.deleted_at')
            ->where(function ($query) {
                $query->whereNull('pp.estado')
                    ->orWhere('pp.estado', '!=', 'Entregado');
            })
            // Excluir registros cuyo pedido_epp_id apunta a un EPP que fue soft-deleted (homologado)
            ->where(function ($query) {
                $query->whereNull('bdt.pedido_epp_id')  // Sin pedido_epp_id (prendas)
                    ->orWhereNull('pe.deleted_at');    // O el EPP está vigente (no fue homologado)
            })
            ->distinct()
            ->count('bdt.numero_pedido');
    }

    public function obtenerPendientesAsesor(
        string $asesorNombre,
        string $search = '',
        string $tipo = 'todos',
        int $page = 1,
        int $perPage = 20
    ): array {
        // ========================================
        // CONSULTAR bodega_detalles_talla
        // ========================================
        $queryBodega = DB::table('bodega_detalles_talla as bdt')
            ->leftJoin('pedidos_produccion as pp', 'bdt.pedido_produccion_id', '=', 'pp.id')
            ->leftJoin('pedido_epp as pe', 'bdt.pedido_epp_id', '=', 'pe.id')
            ->select('bdt.*', 'pp.created_at as pedido_fecha_creacion', 'pp.estado as pedido_estado')
            ->whereNotNull('bdt.numero_pedido')
            ->where('bdt.numero_pedido', '!=', '')
            ->where('bdt.estado_bodega', 'Pendiente')
            ->whereNull('bdt.deleted_at')
            ->where(function ($query) {
                $query->whereNull('pp.estado')
                    ->orWhere('pp.estado', '!=', 'Entregado');
            })
            // Excluir registros cuyo pedido_epp_id apunta a un EPP que fue soft-deleted (homologado)
            ->where(function ($query) {
                $query->whereNull('bdt.pedido_epp_id')  // Sin pedido_epp_id (prendas)
                    ->orWhereNull('pe.deleted_at');    // O el EPP está vigente (no fue homologado)
            });
        
        // Filtrar por asesor
        if ($asesorNombre) {
            $queryBodega->where('bdt.asesor', 'like', "%{$asesorNombre}%");
        }
        
        // Búsqueda
        if ($search) {
            $queryBodega->where(function ($q) use ($search) {
                $q->where('bdt.numero_pedido', 'like', "%{$search}%")
                  ->orWhere('bdt.empresa', 'like', "%{$search}%")
                  ->orWhere('bdt.prenda_nombre', 'like', "%{$search}%");
            });
        }
        
        // Obtener todos los datos
        $detallesBodega = $queryBodega->orderBy('pp.created_at', 'desc')->get();
        
        // ========================================
        // AGRUPAR RESULTADOS
        // ========================================
        
        // Procesar bodega
        $pedidosDeBodega = $detallesBodega->groupBy('numero_pedido')->map(function ($items, $numeroPedido) {
            $primerItem = $items->first();
            $totalItems = $items->count();
            $itemsUnicos = $items->groupBy(function ($item) {
                return $item->pedido_epp_id ?? $item->prenda_id;
            })->count();
            $totalCantidad = $items->sum(function($item) {
                return is_numeric($item->cantidad) ? (int)$item->cantidad : 0;
            });

            $pendientesPrendas = $items->filter(fn($item) => strtolower($item->area ?? '') === 'costura')
                ->groupBy(fn($item) => $item->prenda_id)->count();
            $pendientesEpp = $items->filter(fn($item) => strtolower($item->area ?? '') === 'epp')
                ->groupBy(fn($item) => $item->pedido_epp_id)->count();
            
            $areas = $items->pluck('area')->unique()->filter()->values()->toArray();
            $tipoDisplay = implode(' + ', $areas);
            
            return [
                'numero_pedido' => $numeroPedido,
                'id' => $primerItem->pedido_produccion_id ?? 0,
                'cliente' => $primerItem->empresa ?? 'Sin Empresa',
                'asesor' => $primerItem->asesor ?? '',
                'estado' => 'Pendiente',
                'fecha_creacion' => $primerItem->pedido_fecha_creacion ? Carbon::parse($primerItem->pedido_fecha_creacion)->format('d/m/Y') : '-',
                'fecha_entrega' => $primerItem->fecha_entrega ? Carbon::parse($primerItem->fecha_entrega)->format('d/m/Y') : '',
                'tipo' => $tipoDisplay,
                'total_items' => $totalItems,
                'total_pendientes' => $itemsUnicos,
                'pendientes_prendas' => $pendientesPrendas,
                'pendientes_epp' => $pendientesEpp,
                'total_cantidad' => $totalCantidad,
                'areas' => $areas,
                'detalles' => $items->map(function($item) {
                    return [
                        'prenda' => $item->prenda_nombre ?? 'Prenda',
                        'talla' => $item->talla ?? '-',
                        'cantidad' => $item->cantidad ?? 0,
                        'pendientes' => $item->pendientes ?? 0,
                        'area' => $item->area ?? 'Bodega',
                        'estado_costura' => $item->costura_estado ?? 'N/A',
                        'estado_epp' => $item->epp_estado ?? 'N/A',
                        'observaciones' => $item->observaciones_bodega ?? ''
                    ];
                })->toArray()
            ];
        })->values()->toArray();
        
        // Convertir a collection para filtrado y paginación
        $pedidosCollection = collect($pedidosDeBodega);
        
        // Filtrar por tipo de área después de agrupar
        if ($tipo === 'costura') {
            $pedidosCollection = $pedidosCollection->filter(function($pedido) {
                return collect($pedido['detalles'])->some(function($detalle) {
                    return ($detalle['area'] === 'Costura' && $detalle['estado_costura'] === 'Pendiente') 
                        || str_contains(strtolower($detalle['area'] ?? ''), 'costura');
                });
            })->values();
        } elseif ($tipo === 'epp') {
            $pedidosCollection = $pedidosCollection->filter(function($pedido) {
                return collect($pedido['detalles'])->some(function($detalle) {
                    return ($detalle['area'] === 'EPP' && $detalle['estado_epp'] === 'Pendiente') 
                        || str_contains(strtolower($detalle['area'] ?? ''), 'epp');
                });
            })->values();
        }
        
        // Paginación manual
        $total = $pedidosCollection->count();
        $pedidosPaginados = $pedidosCollection->forPage($page, $perPage);
        
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
