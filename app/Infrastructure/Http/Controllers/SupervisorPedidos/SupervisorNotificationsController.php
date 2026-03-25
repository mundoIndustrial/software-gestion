<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\UseCases\GetNotificationsUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkAllNotificationsAsReadUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkNotificationAsReadUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleNewsVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\TogglePedidoVistoUseCase;
use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoRequest;
use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationAsReadRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadRequest;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ApplicationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * SupervisorNotificationsController
 * 
 * Gestiona todas las operaciones relacionadas con notificaciones:
 * - Obtener notificaciones
 * - Marcar como leídas (individuales y en lote)
 * - Toggle de visibilidad para noticias y pedidos
 * 
 * Responsabilidad: Orquestar use cases de notificaciones y traducir HTTP <-> DTOs
 * Manejo de errores: Centralizado en ExceptionHandler (sin try-catch)
 */
class SupervisorNotificationsController extends Controller
{
    private GetNotificationsUseCase $getNotificationsUseCase;
    private MarkAllNotificationsAsReadUseCase $markAllNotificationsAsReadUseCase;
    private MarkNotificationAsReadUseCase $markNotificationAsReadUseCase;
    private ToggleNewsVistoUseCase $toggleNewsVistoUseCase;
    private TogglePedidoVistoUseCase $togglePedidoVistoUseCase;

    public function __construct(
        GetNotificationsUseCase $getNotificationsUseCase,
        MarkAllNotificationsAsReadUseCase $markAllNotificationsAsReadUseCase,
        MarkNotificationAsReadUseCase $markNotificationAsReadUseCase,
        ToggleNewsVistoUseCase $toggleNewsVistoUseCase,
        TogglePedidoVistoUseCase $togglePedidoVistoUseCase
    ) {
        $this->getNotificationsUseCase = $getNotificationsUseCase;
        $this->markAllNotificationsAsReadUseCase = $markAllNotificationsAsReadUseCase;
        $this->markNotificationAsReadUseCase = $markNotificationAsReadUseCase;
        $this->toggleNewsVistoUseCase = $toggleNewsVistoUseCase;
        $this->togglePedidoVistoUseCase = $togglePedidoVistoUseCase;
    }

    /**
     * Obtener notificaciones (órdenes pendientes de aprobación)
     */
    public function getNotifications(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException(
                'Usuario no autenticado',
                'get_notifications'
            );
        }

        $response = $this->getNotificationsUseCase->execute();

        return response()->json($response->toArray());
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException(
                'Usuario no autenticado',
                'mark_all_notifications_as_read'
            );
        }

        $markRequest = new MarkNotificationsAsReadRequest(
            (int)$user->id
        );

        $response = $this->markAllNotificationsAsReadUseCase->execute($markRequest);

        return response()->json($response->toArray());
    }

    /**
     * Marcar una notificación como leída
     */
    public function markNotificationAsRead($notificationId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException(
                'Usuario no autenticado',
                'mark_notification_as_read'
            );
        }

        $markRequest = new MarkNotificationAsReadRequest(
            (int)$notificationId
        );

        $response = $this->markNotificationAsReadUseCase->execute($markRequest);

        return response()->json($response->toArray());
    }

    /**
     * Toggle visto de una novedad (News) para el usuario actual
     */
    public function toggleNewsVisto(Request $request, $newsId): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new AuthenticationException(
                'Usuario no autenticado',
                'toggle_news_visto'
            );
        }

        $toggleRequest = new ToggleNewsVistoRequest(
            (int)$newsId,
            (int)$user->id
        );

        $response = $this->toggleNewsVistoUseCase->execute($toggleRequest);

        return response()->json($response->toArray());
    }

    /**
     * Toggle visto de un pedido para el usuario actual
     */
    public function togglePedidoVisto(Request $request, $pedidoId): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new AuthenticationException(
                'Usuario no autenticado',
                'toggle_pedido_visto'
            );
        }

        $toggleRequest = new TogglePedidoVistoRequest(
            (int)$pedidoId,
            (int)$user->id
        );

        $response = $this->togglePedidoVistoUseCase->execute($toggleRequest);

        return response()->json($response->toArray());
    }
}
