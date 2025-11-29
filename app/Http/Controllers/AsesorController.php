<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsesorController extends Controller
{
    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function getNotifications()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Obtener notificaciones no leídas
            $notifications = $user->notifications()
                ->latest()
                ->limit(10)
                ->get();

            // Contar total de notificaciones no leídas
            $totalNotificaciones = $user->notifications()->count();

            return response()->json([
                'notifications' => $notifications,
                'total_notificaciones' => $totalNotificaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Marcar todas como leídas
            $user->notifications()->update(['read_at' => now()]);

            return response()->json([
                'message' => 'Notificaciones marcadas como leídas',
                'total_notificaciones' => 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
