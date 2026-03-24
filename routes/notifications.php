<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE NOTIFICACIONES - Sistema unificado en tiempo real
// ========================================

Route::middleware(['auth'])->group(function () {
    // Notificaciones generales
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])
        ->name('notifications.unread-count');
    Route::post('/notifications/{id}/mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-read');
    Route::post('/notifications/mark-multiple-read', [App\Http\Controllers\NotificationController::class, 'markMultipleAsRead'])
        ->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');
    Route::post('/notifications/mark-read-on-open', [App\Http\Controllers\NotificationController::class, 'markAsReadOnOpen'])
        ->name('notifications.mark-read-on-open');
    
    // Contador (compatibilidad)
    Route::post('/contador/notifications/marcar-leidas', [App\Http\Controllers\ContadorController::class, 'markAllNotificationsAsRead'])
        ->name('contador.notifications.mark-all-read');
    Route::get('/contador/notifications', [App\Http\Controllers\ContadorController::class, 'getNotifications'])
        ->name('contador.notifications');
    
    // Supervisor Pedidos (compatibilidad)
    Route::post('/supervisor-pedidos/notifications/mark-all-read', [App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorPedidosController::class, 'markAllNotificationsAsRead'])
        ->name('supervisor-pedidos.notifications.mark-all-read');
    
    // Insumos / Supervisor Planta (compatibilidad)
    Route::post('/insumos/notifications/marcar-leidas', [\App\Infrastructure\Http\Controllers\Insumos\InsumosController::class, 'markAllNotificationsAsRead'])
        ->name('insumos.notifications.mark-all-read');
});
