<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Contador\CostoPrendaController;
use App\Infrastructure\Http\Controllers\Contador\ContadorModuleController;
use App\Infrastructure\Http\Controllers\Contador\CotizacionAccionesController;
use App\Infrastructure\Http\Controllers\Contador\CotizacionCostosController;
use App\Infrastructure\Http\Controllers\Pdf\CotizacionPdfController;

// ========================================
// RUTAS PARA CONTADOR (Modulo INDEPENDIENTE)
// ========================================
// Admin puede acceder a contador ADEMAS del rol contador
Route::middleware(['auth', 'role:contador,admin,lider_produccion,supervisor_produccion'])->prefix('contador')->name('contador.')->group(function () {
    Route::get('/dashboard', [ContadorModuleController::class, 'index'])->name('index');
    Route::get('/todas', [ContadorModuleController::class, 'todas'])->name('todas');
    Route::get('/por-revisar', [ContadorModuleController::class, 'porRevisar'])->name('por-revisar');
    Route::get('/aprobadas', [ContadorModuleController::class, 'aprobadas'])->name('aprobadas');
    Route::delete('/cotizacion/{id}', [CotizacionAccionesController::class, 'destroy'])->name('cotizacion-delete');
    
    // Rutas para costos de prendas
    Route::post('/costos/guardar', [CostoPrendaController::class, 'guardar'])->name('costos.guardar');
    Route::get('/costos/obtener/{cotizacion_id}', [CostoPrendaController::class, 'obtener'])->name('costos.obtener');
    
    // Rutas para notas de tallas
    Route::post('/prenda/{prendaId}/notas-tallas', [ContadorModuleController::class, 'guardarNotasTallas'])->name('prenda.guardar-notas-tallas');
    
    // Ruta para texto personalizado de tallas (modulo contador)
    Route::post('/prenda/{prendaId}/texto-personalizado-tallas', [ContadorModuleController::class, 'guardarTextoPersonalizadoTallas'])->name('prenda.guardar-texto-personalizado-tallas');
    
    // Rutas para PDF
    Route::get('/cotizacion/{id}/pdf', [CotizacionPdfController::class, 'show'])->name('cotizacion.pdf');
    
    // Ruta para cambiar estado de cotizacion
    Route::patch('/cotizacion/{id}/estado', [CotizacionAccionesController::class, 'cambiarEstado'])->name('cotizacion.cambiar-estado');
    
    // Ruta para obtener costos de prendas
    Route::get('/cotizacion/{id}/costos', [CotizacionCostosController::class, 'show'])->name('cotizacion.costos');
    
    // Ruta para guardar tallas costos
    Route::post('/tallas-costos', [ContadorModuleController::class, 'guardarTallasCostos'])->name('tallas-costos.guardar');
    
    // Ruta para obtener contador de cotizaciones pendientes
    Route::get('/cotizaciones-pendientes-count', [CotizacionAccionesController::class, 'cotizacionesPendientesCount'])->name('cotizaciones-pendientes-count');

    // Ruta para perfil del contador
    Route::get('/perfil', [ContadorModuleController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [ContadorModuleController::class, 'updateProfile'])->name('profile.update');
});

// ========================================
// RUTAS PARA SISTEMA DE ERRORES (Admin)
// ========================================
Route::middleware(['auth', 'role:admin,supervisor_gerencia'])->prefix('admin/configuracion/errores')->name('admin.errores.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\SystemErrorController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\Admin\SystemErrorController::class, 'ver'])->name('ver');
    Route::post('/limpiar', [App\Http\Controllers\Admin\SystemErrorController::class, 'limpiar'])->name('limpiar');
    Route::get('/exportar', [App\Http\Controllers\Admin\SystemErrorController::class, 'exportar'])->name('exportar');
});

// ========================================
// RUTAS PARA TALLERES (Admin)
// ========================================
Route::middleware(['auth', 'role:admin,lider_produccion,supervisor_produccion,visualizador_talleres'])->prefix('talleres')->name('talleres.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\TalleresController::class, 'index'])->name('index');
    Route::get('prestamos/global', [App\Http\Controllers\Admin\TalleresController::class, 'showPrestamosGlobal'])->name('prestamos-global');
    Route::get('api/prestamos/global', [App\Http\Controllers\Admin\TalleresController::class, 'apiPrestamosGlobal'])->name('api.prestamos-global');
    Route::post('api/prestamos/global/marcar-visto', [App\Http\Controllers\Admin\TalleresController::class, 'marcarPrestamoGlobalVisto'])->name('api.prestamos-global.marcar-visto');
    Route::get('{id}/recibos', [App\Http\Controllers\Admin\TalleresController::class, 'showRecibos'])->name('show');
    Route::get('{id}/prestamos', [App\Http\Controllers\Admin\TalleresController::class, 'showPrestamos'])->name('prestamos');
    Route::get('{id}/recibos/{recibo_id}/{es_parcial}/entregas', [App\Http\Controllers\Admin\TalleresController::class, 'showEntregas'])->name('entregas');
    
    // API endpoints para SPA
    Route::get('api/search', [App\Http\Controllers\Admin\TalleresController::class, 'apiSearch'])->name('api.search');
    Route::get('api/{id}/recibos', [App\Http\Controllers\Admin\TalleresController::class, 'apiRecibos'])->name('api.recibos');
    Route::get('api/{taller_id}/recibos/{recibo_id}/{es_parcial}/entregas', [App\Http\Controllers\Admin\TalleresController::class, 'apiEntregas'])->name('api.entregas');
    Route::get('/api/ordenes/todas', [App\Http\Controllers\Admin\TalleresController::class, 'apiOrdenes'])->name('api.ordenes');
    Route::get('/api/recibos/completo', [App\Http\Controllers\Admin\TalleresController::class, 'apiReciboCompleto'])->name('api.recibo-completo');
    Route::get('/api/prestamos/{tipo}/{id}/detalle', [App\Http\Controllers\Admin\TalleresController::class, 'apiDetallePrestamo'])->name('api.prestamos.detalle');
});

Route::middleware(['auth', 'role:admin,lider_produccion,supervisor_produccion'])->prefix('talleres')->name('talleres.')->group(function () {
    // Rutas de escritura (sin acceso para visualizador_talleres)
    Route::patch('/{id}/toggle-status', [App\Http\Controllers\Admin\TalleresController::class, 'toggleStatus'])->name('toggle-status');
    Route::patch('/entrega/{id}/precio', [App\Http\Controllers\Admin\TalleresController::class, 'actualizarPrecio'])->name('actualizar-precio');
    Route::post('/', [App\Http\Controllers\Admin\TalleresController::class, 'store'])->name('store');
    Route::patch('/{id}', [App\Http\Controllers\Admin\TalleresController::class, 'update'])->name('update');
});
