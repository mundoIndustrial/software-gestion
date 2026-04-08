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
