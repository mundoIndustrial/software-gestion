<?php

/**
 * Rutas para Asesores
 * Organizacion:
 * - Dashboard: Estadisticas y datos
 * - Perfil: Gestion de perfil
 * - Pedidos: Listado, creacion, edicion
 * - Recibos: Visualizacion de recibos de pedidos
 * - Cotizaciones: Gestion de cotizaciones
 */

use App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresDashboardController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPerfilController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosViewController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\CotizacionController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Infrastructure\Http\Controllers\Pdf\CotizacionPdfController;
use App\Infrastructure\Http\Controllers\Pdf\PrendaPdfController;
use App\Infrastructure\Http\Controllers\Pdf\CombinadaPdfController;
use App\Infrastructure\Http\Controllers\Pdf\LogoPdfController;
use App\Infrastructure\Http\Controllers\Pdf\EppPdfController;
use App\Infrastructure\Http\Controllers\CotizacionController as CotizacionControllerAlias;
use Illuminate\Support\Facades\Route;

Route::prefix('asesores')->name('asesores.')->group(function () {
    
    // ========================================
    // DASHBOARD Y NOTIFICACIONES
    // ========================================
    Route::get('/dashboard', [AsesoresDashboardController::class, 'dashboard'])->name('dashboard');

    // ========================================
    // PERFIL
    // ========================================
    Route::get('/perfil', [AsesoresPerfilController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [AsesoresPerfilController::class, 'updateProfile'])->name('profile.update');

    // ========================================
    // PEDIDOS - VISTAS Y CRUD
    // ========================================
    Route::get('/pedidos', [AsesoresPedidosViewController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/borradores', [AsesoresPedidosViewController::class, 'borradores'])->name('pedidos.borradores');
    Route::get('/pendientes', [AsesoresPedidosViewController::class, 'pendientes'])->name('pendientes');
    Route::get('/pendientes/{id}', [AsesoresPedidosViewController::class, 'pendientesDetalle'])->name('pendientes.detalle');
    Route::get('/cotizaciones/create', [AsesoresPedidosViewController::class, 'create'])->name('pedidos.create');
    Route::get('/pedidos/{id}', [AsesoresPedidosViewController::class, 'show'])->where('id', '[0-9]+')->name('pedidos.show');
    Route::get('/pedidos/{id}/edit', [AsesoresPedidosViewController::class, 'edit'])->where('id', '[0-9]+')->name('pedidos.edit');

    // ========================================
    // PEDIDOS - APIs DDD (DEPRECATED)
    // ========================================
    // NOTA: La creacion de pedidos se centraliza en /asesores/pedidos/*
    // y no debe existir otro punto de entrada para crear pedidos.
    Route::get('/prendas-pedido/{prendaPedidoId}/fotos', [PedidoQueryController::class, 'obtenerFotosPrendaPedido'])->where('prendaPedidoId', '[0-9]+')->name('prendas-pedido.fotos');
    

    // ========================================
    // RECIBOS - NUEVO MODULO
    // ========================================
    Route::get('/recibos', [ReciboController::class, 'index'])->name('recibos.index');
    Route::get('/recibos/{id}', [ReciboController::class, 'show'])->name('recibos.show');
    Route::get('/recibos/{id}/datos', [ReciboController::class, 'datos'])->name('recibos.datos');
    Route::get('/recibos/{id}/pdf', [ReciboController::class, 'generarPDF'])->name('recibos.pdf');
    

    // ========================================
    // ORDENES/COTIZACIONES - SISTEMA DE BORRADORES
    // ========================================
    Route::get('/borradores', [CotizacionControllerAlias::class, 'borradores'])->name('borradores.index');

    // ========================================
    // COTIZACIONES - DDD Refactorizado
    // ========================================
    Route::get('/cotizaciones', [CotizacionesViewController::class, 'index'])->name('cotizaciones.index');

    // Editar cotizacion ya creada (NO borrador) - reusa el mismo formulario y carga
    Route::get('/cotizaciones/{id}/editar-cotizacion', [CotizacionController::class, 'editCotizacion'])->name('cotizaciones.edit-creada');
    
    // ========================================
    // PDF GENERATION - NEW REFACTORED STRUCTURE
    // ========================================
    // IMPORTANTE: Las rutas especificas deben ir ANTES que la generica
    Route::get('/cotizacion/{id}/pdf/prenda', [PrendaPdfController::class, 'show'])->name('cotizacion.pdf.prenda');
    Route::get('/cotizacion/{id}/pdf/combinada', [CombinadaPdfController::class, 'show'])->name('cotizacion.pdf.combinada');
    Route::get('/cotizacion/{id}/pdf/logo', [LogoPdfController::class, 'show'])->name('cotizacion.pdf.logo'); // Route for logo PDF
    Route::get('/cotizacion/{id}/pdf/epp', [EppPdfController::class, 'show'])->name('cotizacion.pdf.epp');
    Route::get('/cotizacion/{id}/pdf', [CotizacionPdfController::class, 'show'])->name('cotizacion.pdf'); // Ruta compartida migrada a Infrastructure (seam)
    
    // ========================================
    // INVENTARIO Y RECURSOS
    // ========================================
    Route::get('/inventario-telas', [AsesoresInventarioTelasController::class, 'index'])->name('inventario.telas');

    // ========================================
    // API REALTIME - PEDIDOS
    // ========================================

    // ========================================
    // PEDIDOS (UNICA FUENTE PARA CREACION)
    // ========================================
    Route::prefix('pedidos')->name('pedidos.')->middleware('role:asesor,admin,supervisor_pedidos')->group(function () {
        // Formularios de pedidos (GET - renderiza vistas)
        Route::get('crear-desde-cotizacion', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\ObtenerPedidoFormDataController::class, 'crearDesdeCotizacion'])
            ->name('crear-desde-cotizacion');
        Route::get('crear-nuevo', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\ObtenerPedidoFormDataController::class, 'crearNuevo'])
            ->name('crear-nuevo');
        Route::get('revisar-prenda', [AsesoresPedidosViewController::class, 'revisarPrendas'])
            ->name('revisar-prenda');

        // Acciones (POST)
        Route::post('revisar-prenda/{reciboId}/aprobar-insumos', [AsesoresPedidosViewController::class, 'aprobarReciboCosturaParaInsumos'])
            ->name('revisar-prenda.aprobar-insumos');
    });
});
