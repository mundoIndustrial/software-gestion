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
    Route::get('/pedidos-logo/conteos-pendientes', [PedidosLogoController::class, 'obtenerConteosPendientes'])->name('pedidos-logo.conteos-pendientes');
    Route::get('/pedidos-logo/areas-unicas', [PedidosLogoController::class, 'obtenerAreasUnicas'])->name('pedidos-logo.areas-unicas');
    Route::get('/pedidos-logo/asesoras-unicas', [PedidosLogoController::class, 'obtenerAsesorasUnicas'])->name('pedidos-logo.asesoras-unicas');
    Route::get('/pedidos-logo/buscar-valores-columna', [PedidosLogoController::class, 'buscarValoresColumna'])->name('pedidos-logo.buscar-valores-columna');
    Route::get('/pedidos-logo/recibos-procesos/observacion', [SupervisorReceiptsController::class, 'obtenerObservacionReciboProceso'])
        ->name('pedidos-logo.recibos-procesos.observacion.obtener');
    Route::post('/pedidos-logo/area-novedad', [PedidosLogoController::class, 'guardarAreaNovedad'])->name('pedidos-logo.area-novedad');
    Route::post('/pedidos-logo/marcar-completado', [PedidosLogoController::class, 'marcarCompletado'])
        ->middleware('role:bordador,admin')
        ->name('pedidos-logo.marcar-completado');

    Route::get('/logos-confirmados', [VisualizadorLogoController::class, 'logosConfirmados'])->name('logos-confirmados');
    Route::get('/logos-confirmados/data', [VisualizadorLogoController::class, 'logosConfirmadosData'])->name('logos-confirmados.data');
    Route::get('/logos-confirmados/historial-novedades', [VisualizadorLogoController::class, 'logosConfirmadosHistorialNovedades'])->name('logos-confirmados.historial-novedades');
    Route::post('/logos-confirmados/{disenoId}/marcar-revisado', [VisualizadorLogoController::class, 'marcarComoRevisado'])->name('logos-confirmados.marcar-revisado');
    Route::post('/logos-confirmados/{disenoId}/reemplazar', [VisualizadorLogoController::class, 'reemplazarImagen'])->name('logos-confirmados.reemplazar');

    // Visualización de Pedidos (solo lectura)
    Route::get('/pedidos-visualizacion', [VisualizadorLogoController::class, 'pedidosVisualizacion'])->name('pedidos-visualizacion');
    Route::get('/pedidos-visualizacion/data', [VisualizadorLogoController::class, 'pedidosVisualizacionData'])->name('pedidos-visualizacion.data');
    Route::get('/pedidos-visualizacion/{pedidoId}/datos', [VisualizadorLogoController::class, 'pedidoVisualizacionDatos'])->name('pedidos-visualizacion.datos');

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
    
    // Historial de Logos
    Route::get('/historial-logos', [VisualizadorLogoController::class, 'historialLogos'])->name('historial-logos');
    Route::get('/historial-logos/cliente/{clienteId}', [VisualizadorLogoController::class, 'disenosCliente'])->name('historial-logos.cliente');
    
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
