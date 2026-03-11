<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Bodega\PedidosController;

/**
 * Rutas del módulo de Bodega
 * Requiere autenticación y rol de bodeguero
 */
// Ruta de prueba para diagnóstico (sin autenticación ni permisos)
Route::get('/pedidos-test', function() {
    $user = auth()->user();
    return response()->json([
        'mensaje' => 'Ruta de bodega funciona',
        'autenticado' => $user ? true : false,
        'usuario_actual' => $user ? $user->name : 'No autenticado',
        'usuario_id' => $user ? $user->id : null,
        'role_ids' => $user ? $user->roles_ids : null,
        'role_id' => $user ? $user->role_id : null,
    ]);
});

// Ruta temporal para acceso sin restricciones (solo para diagnóstico)
Route::get('/pedidos-temp', [PedidosController::class, 'index']);

Route::middleware(['auth', 'role:bodeguero,EPP-Bodega,supervisor_pedidos,supervisor_gerencia,admin,despacho'])->prefix('gestion-bodega')->name('gestion-bodega.')->group(function () {
    
    // Ruta de diagnóstico
    Route::get('/diagnostico', function() {
        $user = auth()->user();
        return response()->json([
            'usuario_autenticado' => true,
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'usuario_nombre' => $user->name,
            'roles_ids_raw' => $user->roles_ids,
            'roles_ids' => $user->roles_ids ? ($user->roles_ids) : [],
            'roles_nombres' => $user->getRoleNames()->toArray(),
        ], 200);
    });
    
    // Ruta raíz - redirige a pedidos
    Route::get('/', function () {
        return redirect()->route('gestion-bodega.pedidos');
    });
    
    // Listar pedidos
    Route::get('/pedidos', [PedidosController::class, 'index'])
        ->name('pedidos');

    // Pedidos Anulados
    Route::get('/pedidos-anulados', [PedidosController::class, 'anulados'])
        ->name('pedidos-anulados');

    // Pedidos Entregados
    Route::get('/pedidos-entregados', [PedidosController::class, 'entregados'])
        ->name('pedidos-entregados');

    // Pendiente Costura
    Route::get('/pendiente-costura', [PedidosController::class, 'pendienteCostura'])
        ->name('pendientes-costura');

    // Mostrar detalles de pedido específico para Pendiente Costura
    Route::get('/pendiente-costura/{id}', [PedidosController::class, 'showPendienteCostura'])
        ->name('pendiente-costura-show');

    // Pendiente EPP
    Route::get('/pendiente-epp', [PedidosController::class, 'pendienteEpp'])
        ->name('pendientes-epp');

    // Lista simplificada de EPP pendientes
    Route::get('/pendientes-epp-list', [PedidosController::class, 'pendientesEppList'])
        ->name('pendientes-epp-list');

    // Exportar EPP pendientes a Excel
    Route::get('/pendientes-epp/exportar', [PedidosController::class, 'exportarPendientesEpp'])
        ->name('exportar-pendientes-epp');

    // Mostrar detalles de pedido específico para Pendiente EPP
    Route::get('/pendiente-epp/{id}', [PedidosController::class, 'showPendienteEpp'])
        ->name('pendiente-epp-show');

    // Mostrar detalles de un pedido específico
    Route::get('/pedidos/{id}', [PedidosController::class, 'show'])
        ->name('pedidos-show');

    // Desmarcar pedido como no visto
    Route::post('/pedidos/{id}/desmarcar', [PedidosController::class, 'desmarcar'])
        ->name('desmarcar-pedido');
    
    // Marcar pedido como visto (solo para rol EPP-Bodega)
    Route::post('/pedidos/{id}/marcar-visto', [PedidosController::class, 'marcarVisto'])
        ->name('marcar-visto');
    
    // Marcar detalle de bodega como visto
    Route::post('/bodega-detalles/{id}/marcar-visto', [PedidosController::class, 'marcarVistoDetalle'])
        ->name('marcar-visto-detalle');

    // Dashboard
    Route::get('/dashboard', [PedidosController::class, 'dashboard'])
        ->name('dashboard');

    // Acciones AJAX
    Route::post('/pedidos/{id}/entregar', [PedidosController::class, 'entregar'])
        ->name('entregar');

    Route::post('/pedidos/observaciones', [PedidosController::class, 'actualizarObservaciones'])
        ->name('actualizar-observaciones');

    Route::post('/pedidos/fecha', [PedidosController::class, 'actualizarFecha'])
        ->name('actualizar-fecha');

    // Actualizar fecha de entrega a despacho
    Route::post('/bodega-detalles/{id}/fecha-entrega-despacho', [PedidosController::class, 'actualizarFechaEntregaDespacho'])
        ->name('actualizar-fecha-entrega-despacho');

    // API para datos de factura (JSON)
    Route::get('/pedidos/{id}/factura-datos', [PedidosController::class, 'obtenerDatosFacturaJSON'])
        ->name('factura-datos-json');

    // Guardar detalles de bodega por talla
    Route::post('/detalles-talla/guardar', [PedidosController::class, 'guardarDetallesTalla'])
        ->name('guardar-detalle-talla');
    
    // Guardar y obtener notas
    Route::post('/notas/guardar', [PedidosController::class, 'guardarNota'])
        ->name('guardar-nota');
    
    Route::post('/notas/obtener', [PedidosController::class, 'obtenerNotas'])
        ->name('obtener-notas');
    
    Route::get('/notas/{numero_pedido}/{talla}', [PedidosController::class, 'obtenerNotas'])
        ->name('obtener-notas-get');
    
    // Actualizar y eliminar notas
    Route::post('/notas/{notaId}/actualizar', [PedidosController::class, 'actualizarNota'])
        ->name('actualizar-nota');
    
    Route::post('/notas/{notaId}/eliminar', [PedidosController::class, 'eliminarNota'])
        ->name('eliminar-nota');
    
    // Guardar pedido completo
    Route::post('/pedidos/{numero_pedido}/guardar-completo', [PedidosController::class, 'guardarPedidoCompleto'])
        ->name('guardar-pedido-completo');
    
    // Guardar una fila individual
    Route::post('/pedidos/{numeroPedido}/guardar-fila', [PedidosController::class, 'guardarFilaCompleta'])
        ->name('guardar-fila');

    // Exportar (opcional)
    Route::get('/pedidos/export', [PedidosController::class, 'export'])
        ->name('export');

    // API para filtros dinámicos
    Route::get('/filtro-datos/{tipo}', [PedidosController::class, 'obtenerDatosFiltro'])
        ->name('obtener-datos-filtro');

    // Notificaciones (campana)
    Route::get('/notificaciones', [PedidosController::class, 'getNotifications'])
        ->name('notificaciones');
    Route::post('/notificaciones/marcar-todas-leidas', [PedidosController::class, 'markAllNotificationsAsRead'])
        ->name('notificaciones.marcar-todas');
    Route::post('/notificaciones/news/{newsId}/toggle-visto', [PedidosController::class, 'toggleNewsVisto'])
        ->name('notificaciones.toggle-news');
    Route::post('/notificaciones/pedido/{pedidoId}/toggle-visto', [PedidosController::class, 'togglePedidoVisto'])
        ->name('notificaciones.toggle-pedido');
});

// Ruta alternativa si prefieres acceso público (para testing)
// Descomenta solo en desarrollo
Route::get('/bodega-api/datos-factura/{id}', [PedidosController::class, 'obtenerDatosFactura'])->name('bodega.datos-factura-public');
/*
Route::get('/bodega/pedidos', [PedidosController::class, 'index'])->name('bodega.pedidos');
Route::post('/bodega/pedidos/{id}/entregar', [PedidosController::class, 'entregar'])->name('bodega.entregar');
Route::post('/bodega/pedidos/observaciones', [PedidosController::class, 'actualizarObservaciones'])->name('bodega.actualizar-observaciones');
Route::post('/bodega/pedidos/fecha', [PedidosController::class, 'actualizarFecha'])->name('bodega.actualizar-fecha');
*/
