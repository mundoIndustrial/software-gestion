<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetNotificationsResponse;

class GetNotificationsUseCase
{
    public function __construct(
        private \Illuminate\Auth\AuthManager $auth
    ) {}

    /**
     * Obtener notificaciones del supervisor (órdenes pendientes + novedades)
     */
    public function execute(): GetNotificationsResponse
    {
        try {
            $user = $this->auth->user();

            if (!$user) {
                return new GetNotificationsResponse(
                    success: false,
                    notifications: collect([]),
                    news: collect([]),
                    totalPending: 0,
                    totalOrdersNotViewed: 0,
                    totalNews: 0,
                    totalNewsNotViewed: 0,
                    totalGeneral: 0
                );
            }

            // Obtener órdenes pendientes de aprobación
            $ordenesPendientes = $this->getOrdersPendingApproval($user);
            
            // Obtener novedades
            $news = $this->getNews($user);
            $cancelledOrders = $this->getCancelledOrders($user);
            $allNews = $news->concat($cancelledOrders)->sortByDesc('timestamp')->values();

            // Contar no vistas
            $totalOrdenesNoVistas = $ordenesPendientes->where('visto', false)->count();
            $totalNovedadesNoVistas = $allNews->where('visto', false)->count();

            return new GetNotificationsResponse(
                success: true,
                notifications: $ordenesPendientes->values(),
                news: $allNews,
                totalPending: $ordenesPendientes->count(),
                totalOrdersNotViewed: $totalOrdenesNoVistas,
                totalNews: $allNews->count(),
                totalNewsNotViewed: $totalNovedadesNoVistas,
                totalGeneral: $totalOrdenesNoVistas + $totalNovedadesNoVistas
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[GetNotificationsUseCase] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return new GetNotificationsResponse(
                success: false,
                notifications: collect([]),
                news: collect([]),
                totalPending: 0,
                totalOrdersNotViewed: 0,
                totalNews: 0,
                totalNewsNotViewed: 0,
                totalGeneral: 0
            );
        }
    }

    /**
     * Obtener órdenes pendientes de aprobación
     */
    private function getOrdersPendingApproval($user): \Illuminate\Support\Collection
    {
        try {
            $pedidosVistosIds = \DB::table('pedidos_vistos_supervisor')
                ->where('user_id', $user->id)
                ->pluck('pedido_id')
                ->toArray();

            $ordenesPendientes = \DB::table('pedidos_produccion')
                ->whereNull('aprobado_por_supervisor_en')
                ->where('estado', '!=', 'Anulada')
                ->where('estado', '!=', 'pendiente_cartera')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
                ->select([
                    'pedidos_produccion.id', 'numero_pedido', 'cliente', 'asesor_id',
                    'created_at', 'estado', 'forma_de_pago', 'u.name as asesor'
                ])
                ->orderBy('pedidos_produccion.created_at', 'desc')
                ->get();

            return $ordenesPendientes->map(function($orden) use ($pedidosVistosIds) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'asesor' => $orden->asesor ?? 'N/A',
                    'fecha' => $orden->created_at ? \Carbon\Carbon::parse($orden->created_at)->format('d/m/Y H:i') : '',
                    'estado' => $orden->estado,
                    'titulo' => "Orden #" . $orden->numero_pedido . " - " . $orden->cliente,
                    'mensaje' => "Cliente: {$orden->cliente} | Asesor: " . ($orden->asesor ?? 'N/A'),
                    'tipo' => 'orden_pendiente_aprobacion',
                    'timestamp' => $orden->created_at ? \Carbon\Carbon::parse($orden->created_at)->toIso8601String() : null,
                    'visto' => in_array($orden->id, $pedidosVistosIds),
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('[GetNotificationsUseCase] Error fetching pending orders: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Obtener novedades (News) recientes
     */
    private function getNews($user): \Illuminate\Support\Collection
    {
        try {
            $newsVistosIds = \DB::table('news_vistos')
                ->where('user_id', $user->id)
                ->pluck('news_id')
                ->toArray();

            $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'order_status_changed'];
            
            $novedades = \DB::table('news')
                ->whereIn('event_type', $novedadesTipos)
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return $novedades->map(function($news) use ($newsVistosIds) {
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
                    'fecha' => \Carbon\Carbon::parse($news->created_at)->format('d/m/Y h:i A'),
                    'icono' => $icono,
                    'color' => $color,
                    'timestamp' => \Carbon\Carbon::parse($news->created_at)->toIso8601String(),
                    'metadata' => $news->metadata,
                    'visto' => in_array($news->id, $newsVistosIds),
                    'source' => 'news',
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('[GetNotificationsUseCase] Error fetching news: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Obtener órdenes anuladas como novedades
     */
    private function getCancelledOrders($user): \Illuminate\Support\Collection
    {
        try {
            $pedidosVistosIds = \DB::table('pedidos_vistos_supervisor')
                ->where('user_id', $user->id)
                ->pluck('pedido_id')
                ->toArray();

            $ordenesAnuladas = \DB::table('pedidos_produccion')
                ->where('estado', 'Anulada')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->where('pedidos_produccion.updated_at', '>=', now()->subDays(7))
                ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
                ->select(['pedidos_produccion.id', 'numero_pedido', 'cliente', 'asesor_id', 'pedidos_produccion.updated_at', 'u.name as asesor'])
                ->orderBy('pedidos_produccion.updated_at', 'desc')
                ->limit(20)
                ->get();

            return $ordenesAnuladas->map(function($orden) use ($pedidosVistosIds) {
                return [
                    'id' => 'anulada_' . $orden->id,
                    'tipo' => 'pedido_anulado',
                    'descripcion' => "Orden #{$orden->numero_pedido} - {$orden->cliente} fue ANULADA",
                    'pedido' => $orden->numero_pedido,
                    'fecha' => \Carbon\Carbon::parse($orden->updated_at)->format('d/m/Y h:i A'),
                    'icono' => 'cancel',
                    'color' => '#ef4444',
                    'timestamp' => \Carbon\Carbon::parse($orden->updated_at)->toIso8601String(),
                    'metadata' => null,
                    'visto' => in_array($orden->id, $pedidosVistosIds),
                    'source' => 'anulada',
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('[GetNotificationsUseCase] Error fetching cancelled orders: ' . $e->getMessage());
            return collect([]);
        }
    }
}
