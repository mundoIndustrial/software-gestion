<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Pdf\LogoPdfController;
use App\Infrastructure\Http\Controllers\VisualizadorLogo\VisualizadorLogoController;
use App\Infrastructure\Http\Controllers\VisualizadorLogo\PedidosLogoController;
use App\Infrastructure\Http\Controllers\VisualizadorLogo\DisenosLogoPedidoController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorReceiptsController;

// ========================================
// RUTAS PARA VISUALIZADOR DE COTIZACIONES LOGO
// ========================================
Route::middleware(['auth', 'role:visualizador_cotizaciones_logo,admin,lider_produccion,supervisor_produccion,diseñador-logos,bordador'])->prefix('visualizador-logo')->name('visualizador-logo.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [VisualizadorLogoController::class, 'dashboard'])->name('dashboard');
    
    // Cotizaciones
    Route::get('/cotizaciones', [VisualizadorLogoController::class, 'getCotizaciones'])->name('cotizaciones');
    Route::get('/cotizaciones/{id}', [VisualizadorLogoController::class, 'verCotizacion'])->name('cotizaciones.ver');
    
    // Pedidos Logo
    Route::get('/pedidos-logo', [VisualizadorLogoController::class, 'pedidosLogo'])->name('pedidos-logo');
    Route::get('/pedidos-logo/data', [PedidosLogoController::class, 'data'])->name('pedidos-logo.data');
    Route::get('/pedidos-logo/recibos-procesos/observacion', [SupervisorReceiptsController::class, 'obtenerObservacionReciboProceso'])
        ->name('pedidos-logo.recibos-procesos.observacion.obtener');
    Route::post('/pedidos-logo/area-novedad', [PedidosLogoController::class, 'guardarAreaNovedad'])->name('pedidos-logo.area-novedad');
    Route::post('/pedidos-logo/marcar-completado', [PedidosLogoController::class, 'marcarCompletado'])
        ->middleware('role:bordador,admin')
        ->name('pedidos-logo.marcar-completado');

    Route::get('/disenos-logo', [VisualizadorLogoController::class, 'disenosLogo'])->name('disenos-logo');
    Route::get('/disenos-logo/data', [VisualizadorLogoController::class, 'disenosLogoData'])->name('disenos-logo.data');

    // Disenos adjuntos del recibo (solo diseñador-logos/admin)
    Route::post('/pedidos-logo/disenos', [DisenosLogoPedidoController::class, 'store'])
        ->middleware('role:admin,diseñador-logos,visualizador_cotizaciones_logo')
        ->name('pedidos-logo.disenos.store');

    Route::get('/pedidos-logo/disenos', [DisenosLogoPedidoController::class, 'index'])
        ->middleware('role:admin,diseñador-logos,bordador,visualizador_cotizaciones_logo')
        ->name('pedidos-logo.disenos.index');

    Route::delete('/pedidos-logo/disenos/{diseno}', [DisenosLogoPedidoController::class, 'destroy'])
        ->middleware('role:admin,diseñador-logos,visualizador_cotizaciones_logo')
        ->name('pedidos-logo.disenos.destroy');
    
    // Estadisticas
    Route::get('/estadisticas', [VisualizadorLogoController::class, 'getEstadisticas'])->name('estadisticas');
    
    // PDF de Logo - Solo puede ver PDFs de logo
    Route::get('/cotizaciones/{id}/pdf-logo', function($id) {
        return redirect()->route('pdf.cotizacion', ['cotizacionId' => $id, 'tipo' => 'logo']);
    })->name('cotizaciones.pdf-logo');
});

// ========================================
// PDF - VISUALIZADOR LOGO
// ========================================
// Estas rutas NO deben vivir bajo el prefijo /asesores porque el usuario del visualizador
// no necesariamente tiene rol asesor y se bloquea con 403.
Route::middleware(['auth', 'role:visualizador_cotizaciones_logo,admin,lider_produccion,supervisor_produccion,contador,aprobador_cotizaciones,asesor'])->group(function () {
    Route::get('/cotizacion/{id}/pdf/logo', [LogoPdfController::class, 'show'])->name('visualizador.cotizacion.pdf.logo');
});
