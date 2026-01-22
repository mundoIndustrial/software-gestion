<?php

/**
 * Rutas para Asesores
 * 
 * Organización:
 * - Dashboard: Estadísticas y datos
 * - Perfil: Gestión de perfil
 * - Pedidos: Listado, creación, edición
 * - Recibos: Visualización de recibos de pedidos
 * - Cotizaciones: Gestión de cotizaciones
 */

use App\Infrastructure\Http\Controllers\Asesores\AsesoresController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresAPIController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController;
use App\Infrastructure\Http\Controllers\CotizacionController;
use App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Http\Controllers\PDFCotizacionController;
use App\Http\Controllers\OrdenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:asesor,admin'])->prefix('asesores')->name('asesores.')->group(function () {
    
    // ========================================
    // DASHBOARD Y NOTIFICACIONES
    // ========================================
    Route::get('/dashboard', [AsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [AsesoresController::class, 'getDashboardData'])->name('dashboard-data');
    
    Route::post('/notifications/mark-all-read', [AsesoresController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notificationId}/mark-read', [AsesoresController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::get('/notifications', [AsesoresController::class, 'getNotifications'])->name('notifications');

    // ========================================
    // PERFIL
    // ========================================
    Route::get('/perfil', [AsesoresController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [AsesoresController::class, 'updateProfile'])->name('profile.update');

    // ========================================
    // PEDIDOS - VISTAS Y CRUD
    // ========================================
    Route::get('/pedidos', [AsesoresController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/create', [AsesoresController::class, 'create'])->name('pedidos.create');
    Route::get('/pedidos/next-pedido', [AsesoresController::class, 'getNextPedido'])->name('next-pedido');
    Route::get('/pedidos/{pedido}', [AsesoresController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{pedido}/edit', [AsesoresController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [AsesoresController::class, 'update'])->name('pedidos.update');
    Route::delete('/pedidos/{pedido}', [AsesoresController::class, 'destroy'])->name('pedidos.destroy');

    // ========================================
    // PEDIDOS - APIs DDD
    // ========================================
    Route::post('/pedidos', [AsesoresAPIController::class, 'store'])->name('pedidos.api.store');
    Route::post('/pedidos/confirm', [AsesoresAPIController::class, 'confirm'])->name('pedidos.api.confirm');
    Route::post('/pedidos/{id}/anular', [AsesoresAPIController::class, 'anularPedido'])->where('id', '[0-9]+')->name('pedidos.api.anular');
    Route::get('/pedidos/{id}/factura-datos', [AsesoresController::class, 'obtenerDatosFactura'])->where('id', '[0-9]+')->name('pedidos.factura-datos');
    Route::get('/prendas-pedido/{prendaPedidoId}/fotos', [AsesoresAPIController::class, 'obtenerFotosPrendaPedido'])->where('prendaPedidoId', '[0-9]+')->name('prendas-pedido.fotos');
    
    // Actualizar prenda desde editor modal
    Route::post('/pedidos-produccion/actualizar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'actualizarPrenda'])->name('pedidos.actualizar-prenda');
    
    // Cargar datos de pedido para edición
    Route::get('/pedidos/{id}/editar-datos', [AsesoresAPIController::class, 'obtenerDatosEdicion'])->where('id', '[0-9]+')->name('pedidos.api.editar-datos');
    
    // Agregar prenda simple al pedido
    Route::post('/pedidos/{pedidoId}/agregar-prenda-simple', [AsesoresController::class, 'agregarPrendaSimple'])->where('pedidoId', '[0-9]+')->name('pedidos.agregar-prenda-simple');
    
    // Agregar prenda completa (con telas y procesos) al pedido en edición
    Route::post('/pedidos/{id}/agregar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'agregarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.agregar-prenda-completa');

    // ========================================
    // RECIBOS - NUEVO MÓDULO
    // ========================================
    Route::get('/recibos', [ReciboController::class, 'index'])->name('recibos.index');
    Route::get('/recibos/{id}', [ReciboController::class, 'show'])->name('recibos.show');
    Route::get('/recibos/{id}/datos', [ReciboController::class, 'datos'])->name('recibos.datos');
    Route::get('/recibos/{id}/pdf', [ReciboController::class, 'generarPDF'])->name('recibos.pdf');
    
    // Alias para compatibilidad con rutas antiguas
    Route::get('/pedidos/{id}/recibos-datos', [ReciboController::class, 'datos'])->where('id', '[0-9]+')->name('pedidos.api.recibos-datos');

    // ========================================
    // ÓRDENES - SISTEMA DE BORRADORES
    // ========================================
    Route::get('/borradores', [OrdenController::class, 'borradores'])->name('borradores.index');
    Route::get('/ordenes/create', [OrdenController::class, 'create'])->name('ordenes.create');
    Route::post('/ordenes/guardar', [OrdenController::class, 'guardarBorrador'])->name('ordenes.store.draft');
    Route::get('/ordenes/{id}/edit', [OrdenController::class, 'edit'])->name('ordenes.edit');
    Route::patch('/ordenes/{id}', [OrdenController::class, 'update'])->name('ordenes.update');
    Route::post('/ordenes/{id}/confirmar', [OrdenController::class, 'confirmar'])->name('ordenes.confirm');
    Route::delete('/ordenes/{id}', [OrdenController::class, 'destroy'])->name('ordenes.destroy');
    Route::get('/ordenes', [OrdenController::class, 'index'])->name('ordenes.index');
    Route::get('/ordenes/{id}', [OrdenController::class, 'show'])->name('ordenes.show');
    Route::get('/ordenes/stats', [OrdenController::class, 'stats'])->name('ordenes.stats');

    // ========================================
    // COTIZACIONES - DDD Refactorizado
    // ========================================
    Route::get('/cotizaciones', [CotizacionesViewController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/filtros/valores', [CotizacionesFiltrosController::class, 'valores'])->name('cotizaciones.filtros.valores');
    Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::put('/cotizaciones/{id}', [CotizacionController::class, 'update'])->name('cotizaciones.update');
    Route::get('/cotizacion/{id}/pdf', [PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
    Route::delete('/cotizaciones/imagenes/prenda/{id}', [ImagenBorradorController::class, 'borrarPrenda'])->name('cotizaciones.imagen.borrar-prenda');
    Route::delete('/cotizaciones/imagenes/tela/{id}', [ImagenBorradorController::class, 'borrarTela'])->name('cotizaciones.imagen.borrar-tela');
    Route::delete('/cotizaciones/imagenes/logo/{id}', [ImagenBorradorController::class, 'borrarLogo'])->name('cotizaciones.imagen.borrar-logo');
    Route::get('/cotizaciones/{id}/ver', [CotizacionController::class, 'showView'])->name('cotizaciones.show');
    Route::get('/cotizaciones/{id}/editar', [CotizacionController::class, 'getForEdit'])->name('cotizaciones.get-for-edit');
    Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show'])->name('cotizaciones.api');
    Route::post('/cotizaciones/{id}/imagenes', [CotizacionController::class, 'subirImagen'])->name('cotizaciones.subir-imagen');

    // ========================================
    // INVENTARIO Y RECURSOS
    // ========================================
    Route::get('/inventario-telas', [AsesoresController::class, 'inventarioTelas'])->name('inventario.telas');
});
