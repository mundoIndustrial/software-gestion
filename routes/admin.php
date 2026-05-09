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
Route::middleware(['auth', 'role:admin,lider_produccion,supervisor_produccion'])->prefix('talleres')->name('talleres.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\TalleresController::class, 'index'])->name('index');
    Route::get('/{id}/recibos', [App\Http\Controllers\Admin\TalleresController::class, 'showRecibos'])->name('show');
    Route::get('/{id}/recibos/{recibo_id}/{es_parcial}/entregas', [App\Http\Controllers\Admin\TalleresController::class, 'showEntregas'])->name('entregas');
});
