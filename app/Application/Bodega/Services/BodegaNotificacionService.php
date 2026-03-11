<?php

namespace App\Application\Bodega\Services;

use App\Models\PedidoProduccion;
use App\Models\News;
use App\Models\NewsVisto;
use App\Models\PedidoVistoSupervisor;
use Illuminate\Support\Facades\Auth;

class BodegaNotificacionService
{
    /**
     * Obtener notificaciones y novedades para el usuario
     */
    public function obtenerNotificaciones(): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return [
                    'success' => false,
                    'notificaciones' => [],
                    'novedades' => [],
                ];
            }

            $pedidosVistosIds = PedidoVistoSupervisor::where('user_id', $user->id)->pluck('pedido_id')->toArray();

            // Órdenes pendientes de aprobación
            $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->where('estado', '!=', 'Anulada')
                ->where('estado', '!=', 'pendiente_cartera')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->with(['asesora:id,name'])
                ->select(['id', 'numero_pedido', 'cliente', 'asesor_id', 'fecha_de_creacion_de_orden', 'estado', 'forma_de_pago'])
                ->orderBy('fecha_de_creacion_de_orden', 'desc')
                ->get();

            $notificaciones = $ordenesPendientes->map(function($orden) use ($pedidosVistosIds) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'asesor' => ($orden->asesora?->name) ?? 'N/A',
                    'fecha' => ($orden->fecha_de_creacion_de_orden?->format('d/m/Y H:i')) ?? '',
                    'estado' => $orden->estado,
                    'visto' => in_array($orden->id, $pedidosVistosIds),
                ];
            });

            $totalOrdenesNoVistas = $notificaciones->where('visto', false)->count();

            // Novedades (News)
            $newsVistosIds = NewsVisto::where('user_id', $user->id)->pluck('news_id')->toArray();

            $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'order_status_changed'];
            $novedadesQuery = News::whereIn('event_type', $novedadesTipos)
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            // Anuladas
            $ordenesAnuladas = PedidoProduccion::where('estado', 'Anulada')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->where('updated_at', '>=', now()->subDays(7))
                ->with(['asesora:id,name'])
                ->select(['id', 'numero_pedido', 'cliente', 'asesor_id', 'updated_at'])
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get();

            $novedades = $novedadesQuery->map(function($news) use ($newsVistosIds) {
                $icono = match($news->event_type) {
                    'pedido_creado', 'order_created' => 'add_shopping_cart',
                    'prenda_agregada' => 'checkroom',
                    'prenda_modificada' => 'edit',
                    'epp_agregado' => 'health_and_safety',
                    'epp_modificado' => 'edit',
                    'order_status_changed' => 'sync_alt',
                    default => 'notifications',
                };
                $color = match($news->event_type) {
                    'pedido_creado', 'order_created' => '#10b981',
                    'prenda_agregada' => '#3b82f6',
                    'prenda_modificada' => '#f59e0b',
                    'epp_agregado' => '#8b5cf6',
                    'epp_modificado' => '#f59e0b',
                    'order_status_changed' => '#6366f1',
                    default => '#6b7280',
                };
                return [
                    'id' => $news->id,
                    'tipo' => $news->event_type,
                    'descripcion' => $news->description,
                    'pedido' => $news->pedido,
                    'fecha' => $news->created_at->format('d/m/Y h:i A'),
                    'icono' => $icono,
                    'color' => $color,
                    'timestamp' => $news->created_at->toIso8601String(),
                    'visto' => in_array($news->id, $newsVistosIds),
                    'source' => 'news',
                ];
            });

            $novedadesAnuladas = $ordenesAnuladas->map(function($orden) use ($pedidosVistosIds) {
                return [
                    'id' => 'anulada_' . $orden->id,
                    'tipo' => 'pedido_anulado',
                    'descripcion' => "Orden #{$orden->numero_pedido} - {$orden->cliente} fue ANULADA",
                    'pedido' => $orden->numero_pedido,
                    'fecha' => $orden->updated_at->format('d/m/Y h:i A'),
                    'icono' => 'cancel',
                    'color' => '#ef4444',
                    'timestamp' => $orden->updated_at->toIso8601String(),
                    'visto' => in_array($orden->id, $pedidosVistosIds),
                    'source' => 'anulada',
                ];
            });

            $todasNovedades = $novedades->concat($novedadesAnuladas)->sortByDesc('timestamp')->values();
            $totalNovedadesNoVistas = $todasNovedades->where('visto', false)->count();

            return [
                'success' => true,
                'notificaciones' => $notificaciones->values(),
                'novedades' => $todasNovedades,
                'totalPendientes' => $notificaciones->count(),
                'totalOrdenesNoVistas' => $totalOrdenesNoVistas,
                'totalNovedades' => $todasNovedades->count(),
                'totalNovedadesNoVistas' => $totalNovedadesNoVistas,
                'totalGeneral' => $totalOrdenesNoVistas + $totalNovedadesNoVistas,
            ];
        } catch (\Exception $e) {
            \Log::error('Error notificaciones bodega: ' . $e->getMessage());
            return [
                'success' => false,
                'notificaciones' => [],
                'novedades' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodoComoLeido(): bool
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return false;
            }

            // Marcar news como vistas
            $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'order_status_changed'];
            $newsIds = News::whereIn('event_type', $novedadesTipos)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('id');

            foreach ($newsIds as $newsId) {
                NewsVisto::firstOrCreate(['news_id' => $newsId, 'user_id' => $user->id]);
            }

            // Marcar pedidos pendientes como vistos
            $pedidoIds = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->where('estado', '!=', 'pendiente_cartera')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->pluck('id');

            foreach ($pedidoIds as $pedidoId) {
                PedidoVistoSupervisor::firstOrCreate(['pedido_id' => $pedidoId, 'user_id' => $user->id]);
            }

            // Marcar anuladas como vistas
            $anuladasIds = PedidoProduccion::where('estado', 'Anulada')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->where('updated_at', '>=', now()->subDays(7))
                ->pluck('id');

            foreach ($anuladasIds as $anuladaId) {
                PedidoVistoSupervisor::firstOrCreate(['pedido_id' => $anuladaId, 'user_id' => $user->id]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error al marcar notificaciones como leídas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle visto de una novedad (News)
     */
    public function toggleNewsVisto(int $newsId): bool
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return false;
            }

            $existing = NewsVisto::where('news_id', $newsId)->where('user_id', $user->id)->first();
            if ($existing) {
                $existing->delete();
            } else {
                NewsVisto::create(['news_id' => $newsId, 'user_id' => $user->id]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error al toggle news visto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle visto de un pedido
     */
    public function togglePedidoVisto(int $pedidoId): bool
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return false;
            }

            $existing = PedidoVistoSupervisor::where('pedido_id', $pedidoId)->where('user_id', $user->id)->first();
            if ($existing) {
                $existing->delete();
            } else {
                PedidoVistoSupervisor::create(['pedido_id' => $pedidoId, 'user_id' => $user->id]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error al toggle pedido visto: ' . $e->getMessage());
            return false;
        }
    }
}
