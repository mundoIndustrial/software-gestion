<?php

namespace App\Application\Services\Despacho;

use App\Models\News;
use App\Models\NewsVisto;
use App\Models\PedidoProduccion;
use App\Models\PedidoVistoSupervisor;
use App\Models\User;

class DespachoNotificacionesApplicationService
{
    public function obtenerNotificaciones(User $user): array
    {
        $pedidosVistosIds = PedidoVistoSupervisor::where('user_id', $user->id)->pluck('pedido_id')->toArray();

        $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
            ->where('estado', '!=', 'Anulada')
            ->where('estado', '!=', 'pendiente_cartera')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->with(['asesora:id,name'])
            ->select(['id', 'numero_pedido', 'cliente', 'asesor_id', 'created_at', 'estado', 'forma_de_pago'])
            ->orderBy('created_at', 'desc')
            ->get();

        $notificaciones = $ordenesPendientes->map(function ($orden) use ($pedidosVistosIds) {
            return [
                'id' => $orden->id,
                'numero_pedido' => $orden->numero_pedido,
                'cliente' => $orden->cliente,
                'asesor' => ($orden->asesora?->name) ?? 'N/A',
                'fecha' => ($orden->created_at?->format('d/m/Y H:i')) ?? '',
                'estado' => $orden->estado,
                'visto' => in_array($orden->id, $pedidosVistosIds),
            ];
        });

        $totalOrdenesNoVistas = $notificaciones->where('visto', false)->count();

        $newsVistosIds = NewsVisto::where('user_id', $user->id)->pluck('news_id')->toArray();

        $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'epp_homologado', 'order_status_changed', 'order_updated'];
        $novedadesQuery = News::whereIn('event_type', $novedadesTipos)
            ->where(function ($query) {
                $query->where('event_type', '!=', 'order_updated')
                    ->orWhere('table_name', 'pedidos_produccion');
            })
            ->where('created_at', '>=', now()->subMonths(3))
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        \Log::info('[Despacho Notificaciones] Query novedades', [
            'tipos' => $novedadesTipos,
            'desde' => now()->subMonths(3)->toDateTimeString(),
            'total_encontradas' => $novedadesQuery->count(),
            'total_news_table' => News::count(),
        ]);

        $ordenesAnuladas = PedidoProduccion::where('estado', 'Anulada')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->where('updated_at', '>=', now()->subMonths(3))
            ->with(['asesora:id,name'])
            ->select(['id', 'numero_pedido', 'cliente', 'asesor_id', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        $novedades = $novedadesQuery->map(function ($news) use ($newsVistosIds) {
            $icono = match ($news->event_type) {
                'pedido_creado', 'order_created' => 'add_shopping_cart',
                'prenda_agregada' => 'checkroom',
                'prenda_modificada' => 'edit',
                'epp_agregado' => 'health_and_safety',
                'epp_modificado' => 'edit',
                'epp_homologado' => 'compare_arrows',
                'order_status_changed' => 'sync_alt',
                'order_updated' => 'edit_note',
                default => 'notifications',
            };
            $color = match ($news->event_type) {
                'pedido_creado', 'order_created' => '#10b981',
                'prenda_agregada' => '#3b82f6',
                'prenda_modificada' => '#f59e0b',
                'epp_agregado' => '#8b5cf6',
                'epp_modificado' => '#f59e0b',
                'epp_homologado' => '#0ea5e9',
                'order_status_changed' => '#6366f1',
                'order_updated' => '#0f766e',
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

        $novedadesAnuladas = $ordenesAnuladas->map(function ($orden) use ($pedidosVistosIds) {
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
    }

    public function marcarTodasComoLeidas(User $user): void
    {
        $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'epp_homologado', 'order_status_changed', 'order_updated'];
        $newsIds = News::whereIn('event_type', $novedadesTipos)
            ->where(function ($query) {
                $query->where('event_type', '!=', 'order_updated')
                    ->orWhere('table_name', 'pedidos_produccion');
            })
            ->where('created_at', '>=', now()->subMonths(3))
            ->pluck('id');
        foreach ($newsIds as $newsId) {
            NewsVisto::firstOrCreate(['news_id' => $newsId, 'user_id' => $user->id]);
        }

        $pedidoIds = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
            ->where('estado', '!=', 'pendiente_cartera')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->pluck('id');
        foreach ($pedidoIds as $pedidoId) {
            PedidoVistoSupervisor::firstOrCreate(['pedido_id' => $pedidoId, 'user_id' => $user->id]);
        }

        $anuladasIds = PedidoProduccion::where('estado', 'Anulada')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->where('updated_at', '>=', now()->subMonths(3))
            ->pluck('id');
        foreach ($anuladasIds as $anuladaId) {
            PedidoVistoSupervisor::firstOrCreate(['pedido_id' => $anuladaId, 'user_id' => $user->id]);
        }
    }

    public function toggleNewsVisto(User $user, int $newsId): bool
    {
        $existing = NewsVisto::where('news_id', $newsId)->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            return false;
        }

        NewsVisto::create(['news_id' => $newsId, 'user_id' => $user->id]);
        return true;
    }

    public function togglePedidoVisto(User $user, int $pedidoId): bool
    {
        $existing = PedidoVistoSupervisor::where('pedido_id', $pedidoId)->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            return false;
        }

        PedidoVistoSupervisor::create(['pedido_id' => $pedidoId, 'user_id' => $user->id]);
        return true;
    }
}
