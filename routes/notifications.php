<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE NOTIFICACIONES - Sistema unificado en tiempo real
// ========================================

Route::middleware(['auth'])->group(function () {
    // Notificaciones generales
    Route::get('/notifications', [App\Infrastructure\Http\Controllers\Notifications\NotificationsController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/unread-count', [App\Infrastructure\Http\Controllers\Notifications\NotificationsController::class, 'getUnreadCount'])
        ->name('notifications.unread-count');
    Route::post('/notifications/{id}/mark-read', [App\Infrastructure\Http\Controllers\Notifications\NotificationsController::class, 'markAsRead'])
        ->name('notifications.mark-read');
    Route::post('/notifications/mark-multiple-read', [App\Infrastructure\Http\Controllers\Notifications\NotificationsController::class, 'markMultipleAsRead'])
        ->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [App\Infrastructure\Http\Controllers\Notifications\NotificationsController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');
    Route::post('/notifications/mark-read-on-open', [App\Infrastructure\Http\Controllers\Notifications\NotificationsController::class, 'markAsReadOnOpen'])
        ->name('notifications.mark-read-on-open');
    
    // Contador (compatibilidad)
    Route::post('/contador/notifications/marcar-leidas', [App\Infrastructure\Http\Controllers\Contador\CotizacionNotificacionesController::class, 'markAllAsRead'])
        ->name('contador.notifications.mark-all-read');
    Route::get('/contador/notifications', [App\Infrastructure\Http\Controllers\Contador\CotizacionNotificacionesController::class, 'index'])
        ->name('contador.notifications');
    
    // Supervisor Pedidos (compatibilidad)
    Route::post('/supervisor-pedidos/notifications/mark-all-read', [App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorPedidosController::class, 'markAllNotificationsAsRead'])
        ->name('supervisor-pedidos.notifications.mark-all-read');
    
    // Insumos / Supervisor Planta (compatibilidad)
    Route::post('/insumos/notifications/marcar-leidas', [\App\Infrastructure\Http\Controllers\Insumos\InsumosController::class, 'markAllNotificationsAsRead'])
        ->name('insumos.notifications.mark-all-read');
});
