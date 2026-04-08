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

        $totalNotificaciones = $pedidosDevueltos->count() + $recibosDevueltos->count();

        return [
            'pedidos_devueltos' => $pedidosDevueltos,
            'recibos_devueltos' => $recibosDevueltos,
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

        session([
            'viewed_pedidos_devueltos_' . $userId => $pedidoIds,
            'viewed_recibos_devueltos_' . $userId => $reciboIds,
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
