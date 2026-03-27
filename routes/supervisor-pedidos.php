<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorPedidosController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorNotificationsController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorReceiptsController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorOrdersController;
use App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\PrendaPedidoEditController;

// ========================================
// RUTAS PÚBLICAS DE SUPERVISOR-PEDIDOS (accesibles para asesores, supervisores y admins)
// ========================================
Route::middleware(['auth', 'role:asesor,supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Obtener datos en JSON (accesible para asesores, supervisores y admins)
    Route::get('/{id}/datos', [SupervisorOrdersController::class, 'obtenerDatos'])->name('datos');
    
    // Obtener datos de factura para mostrar en modal (accesible para asesores, supervisores y admins)
    Route::get('/{id}/factura-datos', [SupervisorOrdersController::class, 'obtenerDatosFactura'])->name('factura-datos');
    
    // Obtener datos para comparación (pedido vs cotización) (accesible para asesores, supervisores y admins)
    Route::get('/{id}/comparar', [SupervisorOrdersController::class, 'obtenerDatosComparacion'])->name('comparar');
});

// ========================================
// RUTAS PARA SUPERVISOR DE PEDIDOS
// ========================================
Route::middleware(['auth', 'role:supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Listar órdenes
    Route::get('/', [SupervisorOrdersController::class, 'index'])->name('index');
    
    // Perfil del supervisor
    Route::get('/perfil/editar', [SupervisorPedidosController::class, 'profile'])->name('profile');
    Route::post('/perfil/actualizar', [SupervisorPedidosController::class, 'updateProfile'])->name('update-profile');
    
    // Pendientes Bordados-Estampado
    Route::get('/pendientes-bordado-estampado', [SupervisorReceiptsController::class, 'pendientesBordadoEstampado'])->name('pendientes-bordado-estampado');
    
    // Pendientes Costura
    Route::get('/pendientes-costura', [SupervisorReceiptsController::class, 'pendientesCostura'])->name('pendientes-costura');
    Route::get('/pendientes-costura/filtro-opciones/{campo}', [SupervisorReceiptsController::class, 'obtenerOpcionesFiltroPendientesCostura'])->name('pendientes-costura.filtro-opciones');
    Route::post('/guardar-color-costura', [SupervisorReceiptsController::class, 'guardarColorCostura'])->name('guardar-color-costura');

    // Pendientes Control Calidad
    Route::get('/pendientes-control-calidad', [SupervisorReceiptsController::class, 'pendientesControlCalidad'])->name('pendientes-control-calidad');
    Route::get('/pendientes-control-calidad/filtro-opciones/{campo}', [SupervisorReceiptsController::class, 'obtenerOpcionesFiltroPendientesControlCalidad'])->name('pendientes-control-calidad.filtro-opciones');
    Route::get('/pendientes-control-calidad-count', [SupervisorReceiptsController::class, 'pendientesControlCalidadCount'])->name('pendientes-control-calidad-count');

    Route::post('/{pedidoId}/costura/{prendaId}/activar-recibo', [SupervisorReceiptsController::class, 'activarReciboCostura'])
        ->where(['pedidoId' => '[0-9]+', 'prendaId' => '[0-9]+'])
        ->name('costura.activar-recibo');
    
    Route::post('/{pedidoId}/costura/{prendaId}/anular-recibo', [SupervisorReceiptsController::class, 'anularReciboCostura'])
        ->where(['pedidoId' => '[0-9]+', 'prendaId' => '[0-9]+'])
        ->name('costura.anular-recibo');
    
    // Detalles y aprobación de procesos
    Route::get('/procesos/{id}/detalles', [SupervisorReceiptsController::class, 'obtenerDetallesProceso'])->name('procesos.detalles');
    Route::post('/procesos/{id}/aprobar', [SupervisorReceiptsController::class, 'aprobarProceso'])->name('procesos.aprobar');

    // Fecha de llegada de recibo (autosave)
    Route::post('/recibos/{id}/fecha-llegada', [SupervisorReceiptsController::class, 'guardarFechaLlegadaRecibo'])->name('recibos.fecha-llegada');
    
    // Notificaciones
    Route::get('/notificaciones', [SupervisorNotificationsController::class, 'getNotifications'])->name('notifications');
    Route::post('/notificaciones/marcar-todas-leidas', [SupervisorNotificationsController::class, 'markAllNotificationsAsRead'])->name('mark-all-read');
    Route::post('/notificaciones/{notificationId}/marcar-leida', [SupervisorNotificationsController::class, 'markNotificationAsRead'])->name('mark-read');
    Route::post('/notificaciones/news/{newsId}/toggle-visto', [SupervisorNotificationsController::class, 'toggleNewsVisto'])->name('news.toggle-visto');
    Route::post('/notificaciones/pedido/{pedidoId}/toggle-visto', [SupervisorNotificationsController::class, 'togglePedidoVisto'])->name('pedido.toggle-visto');
    
    // Obtener opciones de filtro (debe ir antes de /{id})
    Route::get('/filtro-opciones/{campo}', [SupervisorOrdersController::class, 'obtenerOpcionesFiltro'])->name('filtro-opciones');
    
    // Mostrar detalles de pedido (AJAX - Refactorizado con DDD)
    Route::get('/{id}/detalle', [SupervisorOrdersController::class, 'showPedidoDetalle'])->name('detalle');
    
    // Ruta para obtener contador de órdenes pendientes de aprobación (DEBE IR ANTES DE /{id})
    Route::get('/ordenes-pendientes-count', [SupervisorOrdersController::class, 'ordenesPendientesCount'])->name('ordenes-pendientes-count');
    
    // Gestión de selecciones de pedidos (DEBE IR ANTES DE /{id})
    Route::post('/seleccionar/{pedidoId}', [SupervisorOrdersController::class, 'seleccionarPedido'])->name('seleccionar');
    Route::delete('/seleccionar/{pedidoId}', [SupervisorOrdersController::class, 'deseleccionarPedido'])->name('deseleccionar');
    Route::get('/selecciones', [SupervisorOrdersController::class, 'obtenerSelecciones'])->name('selecciones');
    
    // Ver detalle de orden
    Route::get('/{id}', [SupervisorOrdersController::class, 'show'])->name('show');
    
    // Descargar PDF
    Route::get('/{id}/pdf', [SupervisorOrdersController::class, 'descargarPDF'])->name('pdf');
    
    // Anular orden
    Route::post('/{id}/anular', [SupervisorOrdersController::class, 'anular'])->name('anular');
    
    // Ocultar/Mostrar orden en supervisor-pedidos
    Route::post('/{id}/ocultar', [SupervisorOrdersController::class, 'ocultarPedido'])->name('ocultar');
    Route::post('/{id}/mostrar', [SupervisorOrdersController::class, 'mostrarPedido'])->name('mostrar');
    
    // Aprobar orden (cambiar estado de PENDIENTE_SUPERVISOR a Pendiente)
    Route::post('/{id}/aprobar', [SupervisorOrdersController::class, 'aprobar'])->name('aprobar');
    
    // Cambiar estado
    Route::patch('/{id}/estado', [SupervisorOrdersController::class, 'cambiarEstado'])->name('cambiar-estado');
    
    // Actualizar pedido
    Route::put('/{id}/actualizar', [SupervisorOrdersController::class, 'update'])->name('actualizar');
    Route::post('/{id}/actualizar', [SupervisorOrdersController::class, 'update'])->name('actualizar.post');
    
    // Obtener datos de una prenda específica para edición modal (supervisor)
    Route::get('/{pedidoId}/prenda/{prendaId}/datos', [PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('prenda-datos');
    
    // Actualizar prenda completa (con novedades) - Ruta adicional para edición de prendas desde el modal
    Route::post('/{id}/actualizar-prenda', [PrendasPedidoController::class, 'actualizarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.actualizar-prenda-completa');
    
    // Agregar prenda nueva a pedido existente (desde modal de edición)
    Route::post('/{id}/agregar-prenda', [PrendasPedidoController::class, 'agregarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.agregar-prenda-completa');
    
    // Actualizar proceso específico de una prenda (para supervisor)
    Route::match(['patch', 'post'], '/{prendaId}/procesos/{procesoId}', [PrendaPedidoEditController::class, 'actualizarProcesoEspecifico'])->where(['prendaId' => '[0-9]+', 'procesoId' => '[0-9]+'])->name('procesos-actualizar');
    
    // Eliminar imagen de prenda
    Route::delete('/imagen/{tipo}/{id}', [SupervisorOrdersController::class, 'deleteImage'])->name('imagen.eliminar');
});
