<?php

use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorOrdersApiController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorReceiptsApiController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorNotificationsController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorPedidosController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorReceiptsController;
use App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\PrendaPedidoEditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:web', 'role:asesor,supervisor_pedidos,admin'])
    ->prefix('supervisor-pedidos')
    ->name('api.supervisor-pedidos.')
    ->group(function () {
        Route::get('/ordenes/{id}/datos', [SupervisorOrdersApiController::class, 'showData'])
            ->whereNumber('id')
            ->name('ordenes.datos');

        Route::get('/ordenes/{id}/comparar', [SupervisorOrdersApiController::class, 'comparison'])
            ->whereNumber('id')
            ->name('ordenes.comparar');
    });

Route::middleware(['web', 'auth:web', 'role:supervisor_pedidos,admin'])
    ->prefix('supervisor-pedidos')
    ->name('api.supervisor-pedidos.')
    ->group(function () {
        Route::get('/ordenes', [SupervisorOrdersApiController::class, 'index'])->name('ordenes.index');
        Route::get('/ordenes-fragment', [SupervisorOrdersApiController::class, 'indexFragment'])->name('ordenes.fragment');
        Route::get('/ordenes-pendientes-count', [SupervisorOrdersApiController::class, 'pendingCount'])
            ->name('ordenes.pendientes-count');
        Route::post('/ordenes/{id}/aprobar', [SupervisorOrdersApiController::class, 'approve'])
            ->whereNumber('id')
            ->name('ordenes.aprobar');
        Route::post('/ordenes/{id}/anular', [SupervisorOrdersApiController::class, 'cancel'])
            ->whereNumber('id')
            ->name('ordenes.anular');
        Route::post('/ordenes/{id}/ocultar', [SupervisorOrdersApiController::class, 'hide'])
            ->whereNumber('id')
            ->name('ordenes.ocultar');
        Route::post('/ordenes/{id}/mostrar', [SupervisorOrdersApiController::class, 'show'])
            ->whereNumber('id')
            ->name('ordenes.mostrar');
        Route::patch('/ordenes/{id}/estado', [SupervisorOrdersApiController::class, 'changeStatus'])
            ->whereNumber('id')
            ->name('ordenes.cambiar-estado');
        Route::match(['put', 'post'], '/ordenes/{id}/actualizar', [SupervisorOrdersApiController::class, 'update'])
            ->whereNumber('id')
            ->name('ordenes.actualizar');
        Route::delete('/imagenes/{tipo}/{id}', [SupervisorOrdersApiController::class, 'deleteImage'])
            ->whereNumber('id')
            ->name('imagenes.eliminar');

        Route::get('/ordenes/{pedidoId}/prendas/{prendaId}/datos', [PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])
            ->whereNumber('pedidoId')
            ->whereNumber('prendaId')
            ->name('prendas.datos');
        Route::post('/ordenes/{id}/actualizar-prenda', [PrendasPedidoController::class, 'actualizarPrendaCompleta'])
            ->whereNumber('id')
            ->name('prendas.actualizar');
        Route::post('/ordenes/{id}/agregar-prenda', [PrendasPedidoController::class, 'agregarPrendaCompleta'])
            ->whereNumber('id')
            ->name('prendas.agregar');
        Route::match(['patch', 'post'], '/prendas/{prendaId}/procesos/{procesoId}', [PrendaPedidoEditController::class, 'actualizarProcesoEspecifico'])
            ->whereNumber('prendaId')
            ->whereNumber('procesoId')
            ->name('prendas.procesos.actualizar');
        Route::get('/filtro-opciones/{campo}', [SupervisorOrdersApiController::class, 'filterOptions'])
            ->name('filtro-opciones');
        Route::post('/seleccionar/{pedidoId}', [SupervisorOrdersApiController::class, 'select'])
            ->whereNumber('pedidoId')
            ->name('seleccionar');
        Route::delete('/seleccionar/{pedidoId}', [SupervisorOrdersApiController::class, 'deselect'])
            ->whereNumber('pedidoId')
            ->name('deseleccionar');
        Route::get('/selecciones', [SupervisorOrdersApiController::class, 'selections'])
            ->name('selecciones');

        Route::get('/recibos/pendientes-bordado-estampado', [SupervisorReceiptsApiController::class, 'pendingEmbroideryStamping'])
            ->name('recibos.pendientes-bordado-estampado');
        Route::get('/recibos/pendientes-costura', [SupervisorReceiptsApiController::class, 'pendingSewing'])
            ->name('recibos.pendientes-costura');
        Route::get('/recibos/pendientes-control-calidad', [SupervisorReceiptsApiController::class, 'pendingQualityControl'])
            ->name('recibos.pendientes-control-calidad');
        Route::get('/recibos/pendientes-control-calidad-count', [SupervisorReceiptsApiController::class, 'pendingQualityControlCount'])
            ->name('recibos.pendientes-control-calidad-count');
        Route::get('/recibos/pendientes-costura/filtro-opciones/{campo}', [SupervisorReceiptsController::class, 'obtenerOpcionesFiltroPendientesCostura'])
            ->name('recibos.pendientes-costura.filtro-opciones');
        Route::get('/recibos/pendientes-control-calidad/filtro-opciones/{campo}', [SupervisorReceiptsController::class, 'obtenerOpcionesFiltroPendientesControlCalidad'])
            ->name('recibos.pendientes-control-calidad.filtro-opciones');
        Route::post('/recibos/guardar-color-costura', [SupervisorReceiptsController::class, 'guardarColorCostura'])
            ->name('recibos.guardar-color-costura');
        Route::post('/recibos/guardar-color-control-calidad', [SupervisorReceiptsController::class, 'guardarColorControlCalidad'])
            ->name('recibos.guardar-color-control-calidad');
        Route::post('/recibos/guardar-color-bordado-estampado', [SupervisorReceiptsController::class, 'guardarColorBordadoEstampado'])
            ->name('recibos.guardar-color-bordado-estampado');
        Route::post('/ordenes/{pedidoId}/costura/{prendaId}/activar-recibo', [SupervisorReceiptsController::class, 'activarReciboCostura'])
            ->whereNumber('pedidoId')
            ->whereNumber('prendaId')
            ->name('costura.activar-recibo');
        Route::post('/ordenes/{pedidoId}/costura/{prendaId}/anular-recibo', [SupervisorReceiptsController::class, 'anularReciboCostura'])
            ->whereNumber('pedidoId')
            ->whereNumber('prendaId')
            ->name('costura.anular-recibo');
        Route::get('/procesos/{id}/detalles', [SupervisorReceiptsController::class, 'obtenerDetallesProceso'])
            ->whereNumber('id')
            ->name('procesos.detalles');
        Route::post('/procesos/{id}/aprobar', [SupervisorReceiptsController::class, 'aprobarProceso'])
            ->whereNumber('id')
            ->name('procesos.aprobar');
        Route::post('/recibos/{id}/fecha-llegada', [SupervisorReceiptsController::class, 'guardarFechaLlegadaRecibo'])
            ->whereNumber('id')
            ->name('recibos.fecha-llegada');

        Route::get('/notificaciones', [SupervisorNotificationsController::class, 'getNotifications'])
            ->name('notificaciones.index');
        Route::post('/notificaciones/marcar-todas-leidas', [SupervisorNotificationsController::class, 'markAllNotificationsAsRead'])
            ->name('notificaciones.mark-all-read');
        Route::post('/notificaciones/{notificationId}/marcar-leida', [SupervisorNotificationsController::class, 'markNotificationAsRead'])
            ->whereNumber('notificationId')
            ->name('notificaciones.mark-read');
        Route::post('/notificaciones/news/{newsId}/toggle-visto', [SupervisorNotificationsController::class, 'toggleNewsVisto'])
            ->name('notificaciones.news.toggle-visto');
        Route::post('/notificaciones/pedido/{pedidoId}/toggle-visto', [SupervisorNotificationsController::class, 'togglePedidoVisto'])
            ->whereNumber('pedidoId')
            ->name('notificaciones.pedido.toggle-visto');

        Route::post('/perfil/actualizar', [SupervisorPedidosController::class, 'updateProfile'])
            ->name('perfil.actualizar');
    });
