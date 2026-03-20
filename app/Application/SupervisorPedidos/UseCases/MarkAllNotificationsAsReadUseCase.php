<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadResponse;
use App\Models\NewsVisto;
use App\Models\PedidoVistoSupervisor;
use App\Models\PedidoProduccion;
use App\Models\News;
use Illuminate\Support\Facades\DB;

class MarkAllNotificationsAsReadUseCase
{
    public function execute(MarkNotificationsAsReadRequest $request): MarkNotificationsAsReadResponse
    {
        try {
            $userId = $request->getUserId();
            $totalMarked = 0;

            // Marcar todas las novedades como vistas por este usuario (últimos 7 días)
            $novedadesTipos = [
                'pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada',
                'epp_agregado', 'epp_modificado', 'order_status_changed'
            ];

            $newsIds = News::whereIn('event_type', $novedadesTipos)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('id')
                ->toArray();

            foreach ($newsIds as $newsId) {
                NewsVisto::firstOrCreate([
                    'news_id' => $newsId,
                    'user_id' => $userId,
                ]);
                $totalMarked++;
            }

            // Marcar todas las órdenes pendientes como vistas
            $pedidoIds = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->where('estado', '!=', 'pendiente_cartera')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->pluck('id')
                ->toArray();

            foreach ($pedidoIds as $pedidoId) {
                PedidoVistoSupervisor::firstOrCreate([
                    'pedido_id' => $pedidoId,
                    'user_id' => $userId,
                ]);
                $totalMarked++;
            }

            // También marcar anuladas como vistas (últimos 7 días)
            $anuladasIds = PedidoProduccion::where('estado', 'Anulada')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->where('updated_at', '>=', now()->subDays(7))
                ->pluck('id')
                ->toArray();

            foreach ($anuladasIds as $anuladaId) {
                PedidoVistoSupervisor::firstOrCreate([
                    'pedido_id' => $anuladaId,
                    'user_id' => $userId,
                ]);
                $totalMarked++;
            }

            return new MarkNotificationsAsReadResponse(
                success: true,
                message: 'Todas las notificaciones han sido marcadas como leídas',
                notificationsMarked: $totalMarked
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al marcar las notificaciones como leídas: ' . $e->getMessage());
        }
    }
}
