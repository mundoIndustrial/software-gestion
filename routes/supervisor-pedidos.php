<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorPedidosController;
use App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\PrendaPedidoEditController;

// ========================================
// RUTAS PÚBLICAS DE SUPERVISOR-PEDIDOS (accesibles para asesores, supervisores y admins)
// ========================================
Route::middleware(['auth', 'role:asesor,supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Obtener datos en JSON (accesible para asesores, supervisores y admins)
    Route::get('/{id}/datos', [SupervisorPedidosController::class, 'obtenerDatos'])->name('datos');
    
    // Obtener datos de factura para mostrar en modal (accesible para asesores, supervisores y admins)
    Route::get('/{id}/factura-datos', [SupervisorPedidosController::class, 'obtenerDatosFactura'])->name('factura-datos');
    
    // Obtener datos para comparación (pedido vs cotización) (accesible para asesores, supervisores y admins)
    Route::get('/{id}/comparar', [SupervisorPedidosController::class, 'obtenerDatosComparacion'])->name('comparar');
});

// ========================================
// RUTAS PARA SUPERVISOR DE PEDIDOS
// ========================================
Route::middleware(['auth', 'role:supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Listar órdenes
    Route::get('/', [SupervisorPedidosController::class, 'index'])->name('index');
    
    // Perfil del supervisor
    Route::get('/perfil/editar', [SupervisorPedidosController::class, 'profile'])->name('profile');
    Route::post('/perfil/actualizar', [SupervisorPedidosController::class, 'updateProfile'])->name('update-profile');
    
    // Pendientes Bordados-Estampado
    Route::get('/pendientes-bordado-estampado', [SupervisorPedidosController::class, 'pendientesBordadoEstampado'])->name('pendientes-bordado-estampado');
    
    // Pendientes Costura
    Route::get('/pendientes-costura', [SupervisorPedidosController::class, 'pendientesCostura'])->name('pendientes-costura');
    Route::get('/pendientes-costura/filtro-opciones/{campo}', [SupervisorPedidosController::class, 'obtenerOpcionesFiltroPendientesCostura'])->name('pendientes-costura.filtro-opciones');
    Route::post('/guardar-color-costura', [SupervisorPedidosController::class, 'guardarColorCostura'])->name('guardar-color-costura');

    // Pendientes Control Calidad
    Route::get('/pendientes-control-calidad', [SupervisorPedidosController::class, 'pendientesControlCalidad'])->name('pendientes-control-calidad');
    Route::get('/pendientes-control-calidad/filtro-opciones/{campo}', [SupervisorPedidosController::class, 'obtenerOpcionesFiltroPendientesControlCalidad'])->name('pendientes-control-calidad.filtro-opciones');

    Route::post('/{pedidoId}/costura/{prendaId}/activar-recibo', [SupervisorPedidosController::class, 'activarReciboCostura'])
        ->where(['pedidoId' => '[0-9]+', 'prendaId' => '[0-9]+'])
        ->name('costura.activar-recibo');
    
    Route::post('/{pedidoId}/costura/{prendaId}/anular-recibo', [SupervisorPedidosController::class, 'anularReciboCostura'])
        ->where(['pedidoId' => '[0-9]+', 'prendaId' => '[0-9]+'])
        ->name('costura.anular-recibo');
    
    // Detalles y aprobación de procesos
    Route::get('/procesos/{id}/detalles', [SupervisorPedidosController::class, 'obtenerDetallesProceso'])->name('procesos.detalles');
    Route::post('/procesos/{id}/aprobar', [SupervisorPedidosController::class, 'aprobarProceso'])->name('procesos.aprobar');

    // Fecha de llegada de recibo (autosave)
    Route::post('/recibos/{id}/fecha-llegada', [SupervisorPedidosController::class, 'guardarFechaLlegadaRecibo'])->name('recibos.fecha-llegada');
    
    // Notificaciones
    Route::get('/notificaciones', [SupervisorPedidosController::class, 'getNotifications'])->name('notifications');
    Route::post('/notificaciones/marcar-todas-leidas', [SupervisorPedidosController::class, 'markAllNotificationsAsRead'])->name('mark-all-read');
    Route::post('/notificaciones/{notificationId}/marcar-leida', [SupervisorPedidosController::class, 'markNotificationAsRead'])->name('mark-read');
    Route::post('/notificaciones/news/{newsId}/toggle-visto', [SupervisorPedidosController::class, 'toggleNewsVisto'])->name('news.toggle-visto');
    Route::post('/notificaciones/pedido/{pedidoId}/toggle-visto', [SupervisorPedidosController::class, 'togglePedidoVisto'])->name('pedido.toggle-visto');
    
    // Obtener opciones de filtro (debe ir antes de /{id})
    Route::get('/filtro-opciones/{campo}', [SupervisorPedidosController::class, 'obtenerOpcionesFiltro'])->name('filtro-opciones');
    
    // Mostrar detalles de pedido (AJAX - Refactorizado con DDD)
    Route::get('/{id}/detalle', [SupervisorPedidosController::class, 'showPedidoDetalle'])->name('detalle');
    
    // Ruta para obtener contador de órdenes pendientes de aprobación (DEBE IR ANTES DE /{id})
    Route::get('/ordenes-pendientes-count', [SupervisorPedidosController::class, 'ordenesPendientesCount'])->name('ordenes-pendientes-count');
    
    // Gestión de selecciones de pedidos (DEBE IR ANTES DE /{id})
    Route::post('/seleccionar/{pedidoId}', [SupervisorPedidosController::class, 'seleccionarPedido'])->name('seleccionar');
    Route::delete('/seleccionar/{pedidoId}', [SupervisorPedidosController::class, 'deseleccionarPedido'])->name('deseleccionar');
    Route::get('/selecciones', [SupervisorPedidosController::class, 'obtenerSelecciones'])->name('selecciones');
    
    // Ver detalle de orden
    Route::get('/{id}', [SupervisorPedidosController::class, 'show'])->name('show');
    
    // Descargar PDF
    Route::get('/{id}/pdf', [SupervisorPedidosController::class, 'descargarPDF'])->name('pdf');
    
    // Anular orden
    Route::post('/{id}/anular', [SupervisorPedidosController::class, 'anular'])->name('anular');
    
    // Ocultar/Mostrar orden en supervisor-pedidos
    Route::post('/{id}/ocultar', [SupervisorPedidosController::class, 'ocultarPedido'])->name('ocultar');
    Route::post('/{id}/mostrar', [SupervisorPedidosController::class, 'mostrarPedido'])->name('mostrar');
    
    // Aprobar orden (cambiar estado de PENDIENTE_SUPERVISOR a Pendiente)
    Route::post('/{id}/aprobar', [SupervisorPedidosController::class, 'aprobar'])->name('aprobar');
    
    // Cambiar estado
    Route::patch('/{id}/estado', [SupervisorPedidosController::class, 'cambiarEstado'])->name('cambiar-estado');
    
    // Editar pedido
    Route::get('/{id}/editar', [SupervisorPedidosController::class, 'edit'])->name('editar');
    
    // Actualizar pedido
    Route::put('/{id}/actualizar', [SupervisorPedidosController::class, 'update'])->name('actualizar');
    Route::post('/{id}/actualizar', [SupervisorPedidosController::class, 'update'])->name('actualizar.post');
    
    // Obtener datos de una prenda específica para edición modal (supervisor)
    Route::get('/{pedidoId}/prenda/{prendaId}/datos', [PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('prenda-datos');
    
    // Actualizar prenda completa (con novedades) - Ruta adicional para edición de prendas desde el modal
    Route::post('/{id}/actualizar-prenda', [PrendasPedidoController::class, 'actualizarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.actualizar-prenda-completa');
    
    // Agregar prenda nueva a pedido existente (desde modal de edición)
    Route::post('/{id}/agregar-prenda', [PrendasPedidoController::class, 'agregarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.agregar-prenda-completa');
    
    // Actualizar proceso específico de una prenda (para supervisor)
    Route::match(['patch', 'post'], '/{prendaId}/procesos/{procesoId}', [PrendaPedidoEditController::class, 'actualizarProcesoEspecifico'])->where(['prendaId' => '[0-9]+', 'procesoId' => '[0-9]+'])->name('procesos-actualizar');
    
    // Eliminar imagen de prenda
    Route::delete('/imagen/{tipo}/{id}', [SupervisorPedidosController::class, 'deleteImage'])->name('imagen.eliminar');
});
