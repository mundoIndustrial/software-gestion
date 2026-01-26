<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NotificacionesService
 * 
 * Servicio para gestionar notificaciones del asesor.
 * Encapsula la lógica de obtención y marcado de notificaciones.
 */
class NotificacionesService
{
    /**
     * Obtener todas las notificaciones del asesor
     */
    public function obtenerNotificaciones(): array
    {
        $userId = Auth::id();

        // ============================================
        // NOTIFICACIONES: Fecha Estimada de Entrega
        // ============================================
        $notificacionesFechaEstimada = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('type', 'App\\Notifications\\FechaEstimadaAsignada')
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($notif) {
                $data = json_decode($notif->data, true);
                return [
                    'id' => $notif->id,
                    'tipo' => 'fecha_estimada',
                    'titulo' => 'ðŸ“… Fecha Estimada Asignada',
                    'pedido_id' => $data['pedido_id'],
                    'numero_pedido' => $data['numero_pedido'],
                    'fecha_estimada' => $data['fecha_estimada'],
                    'usuario_que_genero' => $data['usuario_que_genero_nombre'] ?? 'Sistema',
                    'created_at' => $notif->created_at,
                ];
            });

        // Obtener IDs de pedidos ya vistos por el usuario
        $viewedPedidoIds = session('viewed_pedidos_' . $userId, []);
        
        // ============================================
        // NUEVO: Pedidos/Cotizaciones de OTROS asesores
        // ============================================
        $pedidosOtrosAsesores = PedidoProduccion::where('asesor_id', '!=', $userId)
            ->whereNotNull('asesor_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->with('asesora')
            ->get()
            ->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'numero_cotizacion' => $pedido->numero_cotizacion,
                    'cliente' => $pedido->cliente,
                    'asesor_nombre' => $pedido->asesora?->name ?? 'Desconocido',
                    'estado' => $pedido->estado,
                    'created_at' => $pedido->created_at,
                ];
            });

        // ============================================
        // ANTERIOR: Pedidos propios próximos a vencer
        // ============================================
        $pedidosProximosEntregar = PedidoProduccion::where('asesor_id', $userId)
            ->whereIn('estado', ['No iniciado', 'En Ejecución'])
            ->where('created_at', '<=', now()->addDays(7))
            ->whereNotIn('id', $viewedPedidoIds)
            ->orderBy('created_at')
            ->get();

        // Pedidos propios en ejecución
        $pedidosEnEjecucion = PedidoProduccion::where('asesor_id', $userId)
            ->where('estado', 'En Ejecución')
            ->whereNotIn('id', $viewedPedidoIds)
            ->count();

        $totalNotificaciones = $notificacionesFechaEstimada->count() + 
                              $pedidosOtrosAsesores->count() + 
                              $pedidosProximosEntregar->count() + 
                              $pedidosEnEjecucion;

        return [
            'notificaciones_fecha_estimada' => $notificacionesFechaEstimada,
            'pedidos_otros_asesores' => $pedidosOtrosAsesores,
            'pedidos_proximos_entregar' => $pedidosProximosEntregar,
            'pedidos_en_ejecucion' => $pedidosEnEjecucion,
            'total_notificaciones' => $totalNotificaciones
        ];
    }

    /**
     * Marcar todas las notificaciones como leÃ­das
     */
    public function marcarTodosLeidosPedidos(): void
    {
        $userId = Auth::id();
        
        // Marcar todas las notificaciones de fecha estimada como leÃ­das
        DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('type', 'App\\Notifications\\FechaEstimadaAsignada')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        // Obtener todos los pedidos que generan notificaciones
        $pedidosProximos = PedidoProduccion::where('asesor_id', $userId)
            ->whereIn('estado', ['No iniciado', 'En Ejecución'])
            ->where('created_at', '<=', now()->addDays(7))
            ->pluck('id')
            ->toArray();
        
        $pedidosEnEjecucion = PedidoProduccion::where('asesor_id', $userId)
            ->where('estado', 'En Ejecución')
            ->pluck('id')
            ->toArray();
        
        // Combinar todos los IDs de pedidos a marcar como vistos
        $allPedidoIds = array_merge($pedidosProximos, $pedidosEnEjecucion);
        
        // Guardar en sesión del usuario
        session(['viewed_pedidos_' . $userId => $allPedidoIds]);
    }

    /**
     * Marcar una notificación especÃ­fica como leÃ­da
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
        
        // Marcar como leÃ­da
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }
}

