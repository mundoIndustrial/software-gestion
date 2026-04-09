<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NotificacionesService
 * Servicio para gestionar notificaciones del asesor.
 * Encapsula la lógica de obtención y marcado de notificaciones.
 */
class NotificacionesService
{
    /**
     * Obtener todas las notificaciones del asesor
     */
    public function obtenerNotificaciones(?int $asesorId = null): array
    {
        $userId = $asesorId ?? Auth::id();

        $viewedPedidosDevueltos = session('viewed_pedidos_devueltos_' . $userId, []);
        $viewedRecibosDevueltos = session('viewed_recibos_devueltos_' . $userId, []);
        $viewedPedidosCompletosDespacho = session('viewed_pedidos_completos_despacho_' . $userId, []);

        // ============================================
        // NOTIFICACIONES: Pedido devuelto a asesora
        // ============================================
        $pedidosDevueltos = PedidoProduccion::where('asesor_id', $userId)
            ->where('estado', 'DEVUELTO_A_ASESORA')
            ->whereNotIn('id', $viewedPedidosDevueltos)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'tipo' => 'pedido_devuelto',
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'motivo_revision' => $pedido->motivo_revision,
                    'fecha_revision' => $pedido->fecha_revision,
                    'updated_at' => $pedido->updated_at,
                ];
            });

        // ============================================
        // NOTIFICACIONES: Recibo devuelto a asesora
        // ============================================
        $recibosDevueltos = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pr', 'pr.id', '=', 'crp.prenda_id')
            ->where('pp.asesor_id', $userId)
            ->where('crp.estado', 'DEVUELTO_ASESOR')
            ->whereNotIn('crp.id', $viewedRecibosDevueltos)
            ->orderByDesc('crp.updated_at')
            ->select([
                'crp.id',
                'crp.pedido_produccion_id',
                'pp.numero_pedido',
                'crp.consecutivo_actual as numero_recibo',
                'crp.prenda_id',
                'pr.nombre_prenda',
                'crp.tipo_recibo',
                'crp.notas as motivo',
                'crp.updated_at',
            ])
            ->get()
            ->map(function($recibo) {
                return [
                    'id' => $recibo->id,
                    'tipo' => 'recibo_devuelto',
                    'pedido_id' => $recibo->pedido_produccion_id,
                    'numero_pedido' => $recibo->numero_pedido,
                    'numero_recibo' => $recibo->numero_recibo,
                    'prenda_id' => $recibo->prenda_id,
                    'prenda_nombre' => $recibo->nombre_prenda,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'motivo' => $recibo->motivo,
                    'updated_at' => $recibo->updated_at,
                ];
            });

        // ============================================
        // NOTIFICACIONES: Pedido completo en despacho
        // Regla: TODAS las prendas/EPPs del pedido deben estar
        //        COMPLETAS en estado Entregado
        // - Una prenda está COMPLETA cuando TODAS sus tallas
        //   tienen estado_bodega = 'Entregado' (ignorando Anulado/Homologar)
        // - Un EPP está COMPLETO cuando está en 'Entregado'
        // ============================================
        $pedidosCompletosDespacho = DB::table('pedidos_produccion as pp')
            ->where('pp.asesor_id', $userId)
            ->whereNotIn('pp.id', $viewedPedidosCompletosDespacho)
            ->select([
                'pp.id',
                'pp.numero_pedido',
                'pp.cliente',
                DB::raw('MAX(bdt.updated_at) as updated_at'),
                DB::raw("
                    -- Total de prendas NO-ANULADAS del pedido
                    (SELECT COUNT(DISTINCT pr.id) 
                     FROM prendas_pedido pr 
                     WHERE pr.pedido_produccion_id = pp.id 
                       AND pr.deleted_at IS NULL) as total_prendas
                "),
                DB::raw("
                    -- Total de EPPs NO-ANULADOS del pedido
                    (SELECT COUNT(DISTINCT pe.id) 
                     FROM pedido_epp pe 
                     WHERE pe.pedido_produccion_id = pp.id 
                       AND pe.deleted_at IS NULL) as total_epps
                "),
                DB::raw("
                    -- Prendas que están COMPLETAS (todas sus tallas Entregado)
                    (SELECT COUNT(DISTINCT bdt_inner.prenda_id)
                     FROM bodega_detalles_talla bdt_inner
                     WHERE bdt_inner.pedido_produccion_id = pp.id
                       AND bdt_inner.deleted_at IS NULL
                       AND bdt_inner.prenda_id IS NOT NULL
                       AND bdt_inner.prenda_id IN (
                           SELECT DISTINCT pr2.id FROM prendas_pedido pr2
                           WHERE pr2.pedido_produccion_id = pp.id AND pr2.deleted_at IS NULL
                       )
                       -- TODAS las tallas de esta prenda deben estar Entregado
                       AND NOT EXISTS (
                           SELECT 1 FROM bodega_detalles_talla bdt_check
                           WHERE bdt_check.pedido_produccion_id = pp.id
                             AND bdt_check.prenda_id = bdt_inner.prenda_id
                             AND bdt_check.deleted_at IS NULL
                             AND bdt_check.estado_bodega NOT IN ('Anulado', 'Homologar')
                             AND bdt_check.estado_bodega <> 'Entregado'
                       )
                    ) as prendas_completas
                "),
                DB::raw("
                    -- EPPs que están COMPLETOS
                    (SELECT COUNT(DISTINCT bdt_epp.pedido_epp_id)
                     FROM bodega_detalles_talla bdt_epp
                     WHERE bdt_epp.pedido_produccion_id = pp.id
                       AND bdt_epp.deleted_at IS NULL
                       AND bdt_epp.pedido_epp_id IS NOT NULL
                       AND bdt_epp.pedido_epp_id IN (
                           SELECT DISTINCT pe2.id FROM pedido_epp pe2
                           WHERE pe2.pedido_produccion_id = pp.id AND pe2.deleted_at IS NULL
                       )
                       AND bdt_epp.estado_bodega = 'Entregado'
                    ) as epps_completos
                "),
            ])
            ->leftJoin('bodega_detalles_talla as bdt', 'bdt.pedido_produccion_id', '=', 'pp.id')
            ->groupBy('pp.id', 'pp.numero_pedido', 'pp.cliente')
            // Debe haber al menos una prenda o un epp
            ->havingRaw("(
                (SELECT COUNT(DISTINCT pr.id) 
                 FROM prendas_pedido pr 
                 WHERE pr.pedido_produccion_id = pp.id 
                   AND pr.deleted_at IS NULL) +
                (SELECT COUNT(DISTINCT pe.id) 
                 FROM pedido_epp pe 
                 WHERE pe.pedido_produccion_id = pp.id 
                   AND pe.deleted_at IS NULL)
            ) > 0")
            // TODAS las prendas deben estar completas Y TODOS los EPPs deben estar completos
            ->havingRaw("
                (SELECT COUNT(DISTINCT pr.id) 
                 FROM prendas_pedido pr 
                 WHERE pr.pedido_produccion_id = pp.id 
                   AND pr.deleted_at IS NULL) = 
                (SELECT COUNT(DISTINCT bdt_inner.prenda_id)
                 FROM bodega_detalles_talla bdt_inner
                 WHERE bdt_inner.pedido_produccion_id = pp.id
                   AND bdt_inner.deleted_at IS NULL
                   AND bdt_inner.prenda_id IS NOT NULL
                   AND bdt_inner.prenda_id IN (
                       SELECT DISTINCT pr2.id FROM prendas_pedido pr2
                       WHERE pr2.pedido_produccion_id = pp.id AND pr2.deleted_at IS NULL
                   )
                   AND NOT EXISTS (
                       SELECT 1 FROM bodega_detalles_talla bdt_check
                       WHERE bdt_check.pedido_produccion_id = pp.id
                         AND bdt_check.prenda_id = bdt_inner.prenda_id
                         AND bdt_check.deleted_at IS NULL
                         AND bdt_check.estado_bodega NOT IN ('Anulado', 'Homologar')
                         AND bdt_check.estado_bodega <> 'Entregado'
                   ))
                AND
                (SELECT COUNT(DISTINCT pe.id) 
                 FROM pedido_epp pe 
                 WHERE pe.pedido_produccion_id = pp.id 
                   AND pe.deleted_at IS NULL) = 
                (SELECT COUNT(DISTINCT bdt_epp.pedido_epp_id)
                 FROM bodega_detalles_talla bdt_epp
                 WHERE bdt_epp.pedido_produccion_id = pp.id
                   AND bdt_epp.deleted_at IS NULL
                   AND bdt_epp.pedido_epp_id IS NOT NULL
                   AND bdt_epp.pedido_epp_id IN (
                       SELECT DISTINCT pe2.id FROM pedido_epp pe2
                       WHERE pe2.pedido_produccion_id = pp.id AND pe2.deleted_at IS NULL
                   )
                   AND bdt_epp.estado_bodega = 'Entregado')
            ")
            ->orderByRaw('MAX(bdt.updated_at) DESC')
            ->get()
            ->map(function ($pedido) {
                $totalItems = ($pedido->total_prendas ?? 0) + ($pedido->total_epps ?? 0);
                return [
                    'id' => (int) $pedido->id,
                    'tipo' => 'pedido_completo_despacho',
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'total_items' => $totalItems,
                    'updated_at' => $pedido->updated_at,
                ];
            });

        $totalNotificaciones = $pedidosDevueltos->count()
            + $recibosDevueltos->count()
            + $pedidosCompletosDespacho->count();

        return [
            'pedidos_devueltos' => $pedidosDevueltos,
            'recibos_devueltos' => $recibosDevueltos,
            'pedidos_completos_despacho' => $pedidosCompletosDespacho,
            'total_notificaciones' => $totalNotificaciones
        ];
    }

    /**
     * Marcar todas las notificaciones como leidas
     */
    public function marcarTodosLeidosPedidos(): void
    {
        $userId = Auth::id();

        $pedidoIds = PedidoProduccion::where('asesor_id', $userId)
            ->where('estado', 'DEVUELTO_A_ASESORA')
            ->pluck('id')
            ->toArray();

        $reciboIds = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->where('pp.asesor_id', $userId)
            ->where('crp.estado', 'DEVUELTO_ASESOR')
            ->pluck('crp.id')
            ->toArray();

        // Obtener IDs de pedidos completos en despacho
        $pedidosCompletosDespachoIds = DB::table('pedidos_produccion as pp')
            ->where('pp.asesor_id', $userId)
            ->select('pp.id')
            ->groupBy('pp.id')
            // Debe haber al menos una prenda o un epp
            ->havingRaw("(
                (SELECT COUNT(DISTINCT pr.id) 
                 FROM prendas_pedido pr 
                 WHERE pr.pedido_produccion_id = pp.id 
                   AND pr.deleted_at IS NULL) +
                (SELECT COUNT(DISTINCT pe.id) 
                 FROM pedido_epp pe 
                 WHERE pe.pedido_produccion_id = pp.id 
                   AND pe.deleted_at IS NULL)
            ) > 0")
            // TODAS las prendas deben estar completas Y TODOS los EPPs deben estar completos
            ->havingRaw("
                (SELECT COUNT(DISTINCT pr.id) 
                 FROM prendas_pedido pr 
                 WHERE pr.pedido_produccion_id = pp.id 
                   AND pr.deleted_at IS NULL) = 
                (SELECT COUNT(DISTINCT bdt_inner.prenda_id)
                 FROM bodega_detalles_talla bdt_inner
                 WHERE bdt_inner.pedido_produccion_id = pp.id
                   AND bdt_inner.deleted_at IS NULL
                   AND bdt_inner.prenda_id IS NOT NULL
                   AND bdt_inner.prenda_id IN (
                       SELECT DISTINCT pr2.id FROM prendas_pedido pr2
                       WHERE pr2.pedido_produccion_id = pp.id AND pr2.deleted_at IS NULL
                   )
                   AND NOT EXISTS (
                       SELECT 1 FROM bodega_detalles_talla bdt_check
                       WHERE bdt_check.pedido_produccion_id = pp.id
                         AND bdt_check.prenda_id = bdt_inner.prenda_id
                         AND bdt_check.deleted_at IS NULL
                         AND bdt_check.estado_bodega NOT IN ('Anulado', 'Homologar')
                         AND bdt_check.estado_bodega <> 'Entregado'
                   ))
                AND
                (SELECT COUNT(DISTINCT pe.id) 
                 FROM pedido_epp pe 
                 WHERE pe.pedido_produccion_id = pp.id 
                   AND pe.deleted_at IS NULL) = 
                (SELECT COUNT(DISTINCT bdt_epp.pedido_epp_id)
                 FROM bodega_detalles_talla bdt_epp
                 WHERE bdt_epp.pedido_produccion_id = pp.id
                   AND bdt_epp.deleted_at IS NULL
                   AND bdt_epp.pedido_epp_id IS NOT NULL
                   AND bdt_epp.pedido_epp_id IN (
                       SELECT DISTINCT pe2.id FROM pedido_epp pe2
                       WHERE pe2.pedido_produccion_id = pp.id AND pe2.deleted_at IS NULL
                   )
                   AND bdt_epp.estado_bodega = 'Entregado')
            ")
            ->pluck('pp.id')
            ->toArray();

        session([
            'viewed_pedidos_devueltos_' . $userId => $pedidoIds,
            'viewed_recibos_devueltos_' . $userId => $reciboIds,
            'viewed_pedidos_completos_despacho_' . $userId => $pedidosCompletosDespachoIds,
        ]);
    }

    /**
     * Marcar una notificación especifica como leida
     */
    public function marcarNotificacionLeida(string $notificationId): void
    {
        $userId = Auth::id();
        
        // Verificar que la notificación pertenezca al usuario actual
        $notificacion = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\\Models\\User')
            ->first();
        
        if (!$notificacion) {
            throw new \Exception('Notificación no encontrada', 404);
        }
        
        // Marcar como leida
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }
}
