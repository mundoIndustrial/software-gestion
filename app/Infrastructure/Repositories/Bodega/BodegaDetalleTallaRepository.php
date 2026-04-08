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
        // 1. CONSULTAR bodega_detalles_talla
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
        
        // ========================================
        // 2. CONSULTAR consecutivos_recibos_pedidos
        // ========================================
        $queryConsecutivos = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as pp', 'crp.pedido_produccion_id', '=', 'pp.id')
            ->leftJoin('users as u', 'pp.asesor_id', '=', 'u.id')
            ->select(
                'crp.id',
                'crp.pedido_produccion_id',
                'pp.numero_pedido',
                'pp.estado',
                'pp.cliente',
                'pp.created_at',
                'crp.tipo_recibo',
                'crp.consecutivo_actual',
                'crp.estado as recibo_estado',
                'crp.area',
                'crp.dia_de_entrega',
                'crp.fecha_estimada_de_entrega',
                'u.name as asesor_name'
            )
            ->where('crp.activo', 1)
            ->where(function ($query) {
                $query->whereNull('pp.estado')
                    ->orWhere('pp.estado', '!=', 'Entregado');
            });
        
        // Filtrar por asesor si está disponible
        if ($asesorNombre) {
            $queryConsecutivos->where('u.name', 'like', "%{$asesorNombre}%");
        }
        
        // Búsqueda
        if ($search) {
            $queryConsecutivos->where(function ($q) use ($search) {
                $q->where('pp.numero_pedido', 'like', "%{$search}%")
                  ->orWhere('pp.cliente', 'like', "%{$search}%")
                  ->orWhere('crp.tipo_recibo', 'like', "%{$search}%");
            });
        }
        
        // Obtener todos los datos
        $detallesBodega = $queryBodega->orderBy('pp.created_at', 'desc')->get();
        $detallesConsecutivos = $queryConsecutivos->orderBy('pp.created_at', 'desc')->get();
        
        // ========================================
        // DEBUG: Diagnóstico de pendientes
        // ========================================
        Log::info('[PENDIENTES-DEBUG] Asesor buscado: ' . $asesorNombre);
        Log::info('[PENDIENTES-DEBUG] Bodega registros encontrados: ' . $detallesBodega->count());
        Log::info('[PENDIENTES-DEBUG] Consecutivos registros encontrados: ' . $detallesConsecutivos->count());
        
        // Verificar si hay registros de bodega con pedidos Entregados que NO deberían estar
        $pedidosUnicosBodega = $detallesBodega->pluck('numero_pedido')->unique()->values();
        Log::info('[PENDIENTES-DEBUG] Pedidos únicos de bodega: ' . json_encode($pedidosUnicosBodega->toArray()));
        
        // Para cada pedido, verificar el estado real en pedidos_produccion
        foreach ($pedidosUnicosBodega as $numPedido) {
            $pedidoReal = DB::table('pedidos_produccion')->where('numero_pedido', $numPedido)->first();
            $bdtItem = $detallesBodega->where('numero_pedido', $numPedido)->first();
            Log::info('[PENDIENTES-DEBUG] Pedido #' . $numPedido . ' => estado_real_pp: ' . ($pedidoReal->estado ?? 'NO ENCONTRADO') 
                . ' | pp.id: ' . ($pedidoReal->id ?? 'NULL')
                . ' | bdt.pedido_produccion_id: ' . ($bdtItem->pedido_produccion_id ?? 'NULL')
                . ' | bdt.pedido_estado_join: ' . ($bdtItem->pedido_estado ?? 'NULL')
                . ' | bdt.estado_bodega: ' . ($bdtItem->estado_bodega ?? 'NULL'));
        }
        
        // DEBUG: Consecutivos - ver qué pedidos traen
        $pedidosUnicosConsecutivos = $detallesConsecutivos->pluck('numero_pedido')->unique()->values();
        Log::info('[PENDIENTES-DEBUG] Pedidos únicos de consecutivos: ' . json_encode($pedidosUnicosConsecutivos->toArray()));
        foreach ($pedidosUnicosConsecutivos as $numPedido) {
            $consItem = $detallesConsecutivos->where('numero_pedido', $numPedido)->first();
            Log::info('[PENDIENTES-DEBUG] Consecutivo pedido #' . $numPedido 
                . ' => estado_pp: ' . ($consItem->estado ?? 'NULL')
                . ' | pedido_produccion_id: ' . ($consItem->pedido_produccion_id ?? 'NULL')
                . ' | tipo_recibo: ' . ($consItem->tipo_recibo ?? 'NULL')
                . ' | recibo_estado: ' . ($consItem->recibo_estado ?? 'NULL'));
        }
        
        // ========================================
        // 3. AGRUPAR Y COMBINAR RESULTADOS
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
        
        // Crear array asociativo por numero_pedido para buscar rápidamente
        $pedidosAgrupados = [];
        foreach ($pedidosDeBodega as $pedido) {
            $pedidosAgrupados[$pedido['numero_pedido']] = $pedido;
        }
        
        // Procesar consecutivos y agregar a pedidos existentes o crear nuevos
        foreach ($detallesConsecutivos as $consecutivo) {
            $numeroPedido = $consecutivo->numero_pedido;
            
            if (!isset($pedidosAgrupados[$numeroPedido])) {
                // Crear nueva entrada para este pedido
                $pedidosAgrupados[$numeroPedido] = [
                    'numero_pedido' => $numeroPedido,
                    'id' => $consecutivo->pedido_produccion_id,
                    'cliente' => $consecutivo->cliente ?? 'Sin Cliente',
                    'asesor' => $consecutivo->asesor_name ?? '',
                    'estado' => 'Pendiente',
                    'fecha_creacion' => $consecutivo->created_at ? Carbon::parse($consecutivo->created_at)->format('d/m/Y') : '-',
                    'fecha_entrega' => $consecutivo->fecha_estimada_de_entrega ? Carbon::parse($consecutivo->fecha_estimada_de_entrega)->format('d/m/Y') : '',
                    'tipo' => $consecutivo->area ?? $consecutivo->tipo_recibo ?? 'Recibos',
                    'total_items' => 1,
                    'total_pendientes' => 1,
                    'total_cantidad' => 1,
                    'areas' => [$consecutivo->area ?? $consecutivo->tipo_recibo],
                    'detalles' => []
                ];
            }
            
            // Agregar detalle de consecutivo
            $pedidosAgrupados[$numeroPedido]['detalles'][] = [
                'prenda' => $consecutivo->tipo_recibo ?? 'Recibo',
                'talla' => 'Consecutivo #' . ($consecutivo->consecutivo_actual ?? '-'),
                'cantidad' => 1,
                'pendientes' => 1,
                'area' => $consecutivo->area ?? $consecutivo->tipo_recibo,
                'estado_costura' => $consecutivo->recibo_estado ?? 'Pendiente',
                'estado_epp' => 'N/A',
                'observaciones' => ''
            ];
        }
        
        // Convertir a array de valores para filtrado y paginación
        $pedidosArray = array_values($pedidosAgrupados);
        $pedidosCollection = collect($pedidosArray);
        
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
