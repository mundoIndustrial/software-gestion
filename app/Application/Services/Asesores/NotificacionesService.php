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
        // Regla: TODOS los items ORIGINALES del pedido (prendas + epp)
        //        deben tener registro en bodega_detalles_talla en estado "Entregado"
        //        (se ignoran Anulados y Homologar)
        // ============================================
        $pedidosCompletosDespacho = DB::table('pedidos_produccion as pp')
            ->where('pp.asesor_id', $userId)
            ->whereNotIn('pp.id', $viewedPedidosCompletosDespacho)
            // Contar prendas no-anuladas del pedido
            ->select([
                'pp.id',
                'pp.numero_pedido',
                'pp.cliente',
                DB::raw('MAX(bdt.updated_at) as updated_at'),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM prendas_pedido
                    WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL
                ) as total_prendas"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM pedido_epp
                    WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL
                ) as total_epps"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM bodega_detalles_talla
                    WHERE pedido_produccion_id = pp.id 
                      AND deleted_at IS NULL
                      AND estado_bodega NOT IN ('Anulado', 'Homologar')
                      AND estado_bodega = 'Entregado'
                ) as items_entregados"),
            ])
            ->leftJoin('bodega_detalles_talla as bdt', 'bdt.pedido_produccion_id', '=', 'pp.id')
            ->groupBy('pp.id', 'pp.numero_pedido', 'pp.cliente')
            // Total de items del pedido = prendas + epps
            ->havingRaw("(
                (SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) +
                (SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL)
            ) > 0")
            // Items entregados debe ser igual al total de items
            ->havingRaw("(
                SELECT COUNT(*) FROM bodega_detalles_talla
                WHERE pedido_produccion_id = pp.id 
                  AND deleted_at IS NULL
                  AND estado_bodega NOT IN ('Anulado', 'Homologar')
                  AND estado_bodega = 'Entregado'
            ) = (
                (SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) +
                (SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL)
            )")
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
            // Total de items del pedido = prendas + epps
            ->havingRaw("(
                (SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) +
                (SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL)
            ) > 0")
            // Items entregados debe ser igual al total de items
            ->havingRaw("(
                SELECT COUNT(*) FROM bodega_detalles_talla
                WHERE pedido_produccion_id = pp.id 
                  AND deleted_at IS NULL
                  AND estado_bodega NOT IN ('Anulado', 'Homologar')
                  AND estado_bodega = 'Entregado'
            ) = (
                (SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) +
                (SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL)
            )")
            ->select('pp.id')
            ->groupBy('pp.id')
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
