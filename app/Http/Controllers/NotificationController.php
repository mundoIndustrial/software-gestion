<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\NotificationMarkedAsReadEvent;

class NotificationController extends Controller
{
    /**
     * Obtener notificaciones del usuario actual
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 50);
        $status = $request->input('status'); // 'read', 'unread', o null para todas
        $days = $request->input('days', 7);
        
        $query = News::with('user')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $notifications = $query->limit($limit)->get()
            ->filter(function ($item) {
                // Filtrar notificaciones que contienen "token" en descripción o usuario
                $description = strtolower($item->description ?? '');
                $userName = strtolower($item->user ? $item->user->name : '');
                
                return !str_contains($description, 'token') && !str_contains($userName, 'token');
            })
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'event_type' => $item->event_type,
                    'table_name' => $item->table_name,
                    'record_id' => $item->record_id,
                    'description' => $item->description,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'user' => $item->user ? $item->user->name : 'Sistema',
                    'user_id' => $item->user_id,
                    'pedido' => $item->pedido,
                    'metadata' => $item->metadata,
                    'status' => $item->status ?? 'unread',
                    'is_read' => $item->status === 'read',
                ];
            })
            ->values();
        
        $unreadCount = News::where('status', 'unread')
            ->where('created_at', '>=', now()->subDays($days))
            ->where(function($query) {
                $query->where('description', 'NOT LIKE', '%token%')
                      ->orWhereHas('user', function($q) {
                          $q->where('name', 'NOT LIKE', '%token%');
                      });
            })
            ->count();
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total_count' => $notifications->count(),
        ]);
    }
    
    /**
     * Obtener contador de notificaciones no leídas
     */
    public function getUnreadCount()
    {
        $count = News::where('status', 'unread')
            ->where('created_at', '>=', now()->subDays(7))
            ->where('description', 'NOT LIKE', '%token%')
            ->whereDoesntHave('user', function($query) {
                $query->where('name', 'LIKE', '%token%');
            })
            ->count();
        
        return response()->json([
            'unread_count' => $count,
        ]);
    }
    
    /**
     * Marcar una notificación como leída
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = News::findOrFail($id);
        
        $notification->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
        
        broadcast(new NotificationMarkedAsReadEvent(Auth::id(), [$id]));
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
        ]);
    }
    
    /**
     * Marcar múltiples notificaciones como leídas
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:news,id',
        ]);
        
        $ids = $request->input('notification_ids');
        
        News::whereIn('id', $ids)->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
        
        broadcast(new NotificationMarkedAsReadEvent(Auth::id(), $ids));
        
        return response()->json([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas',
            'count' => count($ids),
        ]);
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(Request $request)
    {
        $days = $request->input('days', 7);
        
        $ids = News::where('status', 'unread')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('description', 'NOT LIKE', '%token%')
            ->whereDoesntHave('user', function($query) {
                $query->where('name', 'LIKE', '%token%');
            })
            ->pluck('id')
            ->toArray();
        
        if (empty($ids)) {
            return response()->json([
                'success' => true,
                'message' => 'No hay notificaciones para marcar',
                'count' => 0,
            ]);
        }
        
        News::whereIn('id', $ids)->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
        
        broadcast(new NotificationMarkedAsReadEvent(Auth::id(), $ids));
        
        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones han sido marcadas como leídas',
            'count' => count($ids),
        ]);
    }
    
    /**
     * Marcar notificaciones como leídas al abrir el modal
     */
    public function markAsReadOnOpen(Request $request)
    {
        $days = $request->input('days', 7);
        
        $ids = News::where('status', 'unread')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('description', 'NOT LIKE', '%token%')
            ->whereDoesntHave('user', function($query) {
                $query->where('name', 'LIKE', '%token%');
            })
            ->pluck('id')
            ->toArray();
        
        if (!empty($ids)) {
            News::whereIn('id', $ids)->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
            
            broadcast(new NotificationMarkedAsReadEvent(Auth::id(), $ids));
        }
        
        return response()->json([
            'success' => true,
            'count' => count($ids),
        ]);
    }
}
