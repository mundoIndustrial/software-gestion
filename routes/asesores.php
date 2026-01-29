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
use App\Http\Controllers\Api_temp\PedidoController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController;
use App\Infrastructure\Http\Controllers\CotizacionController;
use App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Http\Controllers\PDFCotizacionController;
use App\Http\Controllers\PDFPrendaController;
use App\Http\Controllers\PDFReflectivoController;
use App\Infrastructure\Http\Controllers\CotizacionController as CotizacionControllerAlias;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos'])->prefix('asesores')->name('asesores.')->group(function () {
    
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
    Route::get('/pedidos/{id}', [AsesoresController::class, 'show'])->where('id', '[0-9]+')->name('pedidos.show');
    Route::get('/pedidos/{id}/edit', [AsesoresController::class, 'edit'])->where('id', '[0-9]+')->name('pedidos.edit');
    Route::put('/pedidos/{id}', [AsesoresController::class, 'update'])->where('id', '[0-9]+')->name('pedidos.update');
    Route::delete('/pedidos/{id}', [AsesoresController::class, 'destroy'])->where('id', '[0-9]+')->name('pedidos.destroy');

    // ========================================
    // PEDIDOS - APIs DDD (DEPRECATED - Use modern endpoints)
    // ========================================
    Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.api.store');
    Route::post('/pedidos/confirm', [PedidoController::class, 'confirm'])->name('pedidos.api.confirm');
    Route::post('/pedidos/{id}/anular', [AsesoresController::class, 'anularPedido'])->where('id', '[0-9]+')->name('pedidos.api.anular');
    Route::get('/prendas-pedido/{prendaPedidoId}/fotos', [PedidoController::class, 'obtenerFotosPrendaPedido'])->where('prendaPedidoId', '[0-9]+')->name('prendas-pedido.fotos');
    
    // Cargar datos de pedido para edición
    Route::get('/pedidos/{id}/editar-datos', [\App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerDatosEdicion'])->where('id', '[0-9]+')->name('pedidos.api.editar-datos');
    
    // Obtener datos de una prenda específica con procesos para edición modal
    Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('pedidos.prenda-datos');
    
    // Obtener datos del pedido para edición (sin prenda específica)
    Route::get('/pedidos-produccion/{pedidoId}/datos-edicion', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerDatosEdicion'])->where('pedidoId', '[0-9]+')->name('pedidos.datos-edicion');
    
    // Agregar prenda simple al pedido
    Route::post('/pedidos/{pedidoId}/agregar-prenda-simple', [AsesoresController::class, 'agregarPrendaSimple'])->where('pedidoId', '[0-9]+')->name('pedidos.agregar-prenda-simple');
    
    // Agregar prenda completa (con telas y procesos) al pedido en edición
    Route::post('/pedidos/{id}/agregar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'agregarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.agregar-prenda-completa');
    
    // Actualizar prenda completa (con novedades) en un pedido existente
    Route::post('/pedidos/{id}/actualizar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'actualizarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.actualizar-prenda-completa');

    // Actualizar SOLO la variante de prenda (manga, broche, bolsillos) - CON MERGE
    Route::put('/pedidos/{pedidoId}/prendas/{prendaId}/variante', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'actualizarVariantePrend'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('pedidos.actualizar-variante-prenda');

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
    // ÓRDENES/COTIZACIONES - SISTEMA DE BORRADORES
    // ========================================
    Route::get('/borradores', [CotizacionControllerAlias::class, 'borradores'])->name('borradores.index');
    Route::get('/ordenes/create', [CotizacionControllerAlias::class, 'create'])->name('ordenes.create');
    Route::post('/ordenes/guardar', [CotizacionControllerAlias::class, 'guardarBorrador'])->name('ordenes.store.draft');
    Route::get('/ordenes/{id}/edit', [CotizacionControllerAlias::class, 'edit'])->name('ordenes.edit');
    Route::patch('/ordenes/{id}', [CotizacionControllerAlias::class, 'update'])->name('ordenes.update');
    Route::post('/ordenes/{id}/confirmar', [CotizacionControllerAlias::class, 'confirmar'])->name('ordenes.confirm');
    Route::delete('/ordenes/{id}', [CotizacionControllerAlias::class, 'destroy'])->name('ordenes.destroy');
    Route::get('/ordenes', [CotizacionControllerAlias::class, 'index'])->name('ordenes.index');
    Route::get('/ordenes/{id}', [CotizacionControllerAlias::class, 'show'])->name('ordenes.show');
    Route::get('/ordenes/stats', [CotizacionControllerAlias::class, 'stats'])->name('ordenes.stats');

    // ========================================
    // COTIZACIONES - DDD Refactorizado
    // ========================================
    Route::get('/cotizaciones', [CotizacionesViewController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/filtros/valores', [CotizacionesFiltrosController::class, 'valores'])->name('cotizaciones.filtros.valores');
    Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::put('/cotizaciones/{id}', [CotizacionController::class, 'update'])->name('cotizaciones.update');
    
    // ========================================
    // PDF GENERATION - NEW REFACTORED STRUCTURE
    // ========================================
    Route::get('/cotizacion/{id}/pdf/prenda', [PDFPrendaController::class, 'generate'])->name('cotizacion.pdf.prenda');
    Route::get('/cotizacion/{id}/pdf/reflectivo', [PDFReflectivoController::class, 'generate'])->name('cotizacion.pdf.reflectivo');
    Route::get('/cotizacion/{id}/pdf', [PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf'); // Legacy route
    
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
    
    // ========================================
    // DATOS DE CATÁLOGOS (tipos de broche, manga, telas, colores, etc)
    // ========================================
    Route::get('/api/tipos-broche-boton', [PedidoController::class, 'obtenerTiposBrocheBoton'])->name('api.tipos-broche-boton');
    Route::get('/api/tipos-manga', [PedidoController::class, 'obtenerTiposManga'])->name('api.tipos-manga');
    Route::post('/api/tipos-manga', [PedidoController::class, 'crearObtenerTipoManga'])->name('api.tipos-manga.create');
    Route::get('/api/telas', [PedidoController::class, 'obtenerTelas'])->name('api.telas');
    Route::post('/api/telas', [PedidoController::class, 'crearObtenerTela'])->name('api.telas.create');
    Route::get('/api/colores', [PedidoController::class, 'obtenerColores'])->name('api.colores');
    Route::post('/api/colores', [PedidoController::class, 'crearObtenerColor'])->name('api.colores.create');
    Route::get('/api/prendas/autocomplete', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'obtenerPrendasAutocomplete'])->name('api.prendas.autocomplete');
});
