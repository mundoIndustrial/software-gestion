<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\CostoPrendaController;

// ========================================
// RUTAS PARA CONTADOR (MÓDULO INDEPENDIENTE)
// ========================================
// Admin puede acceder a contador además del rol contador
Route::middleware(['auth', 'role:contador,admin,lider_produccion,supervisor_produccion'])->prefix('contador')->name('contador.')->group(function () {
    Route::get('/dashboard', [ContadorController::class, 'index'])->name('index');
    Route::get('/todas', [ContadorController::class, 'todas'])->name('todas');
    Route::get('/por-revisar', [ContadorController::class, 'porRevisar'])->name('por-revisar');
    Route::get('/aprobadas', [ContadorController::class, 'aprobadas'])->name('aprobadas');
    Route::delete('/cotizacion/{id}', [ContadorController::class, 'deleteCotizacion'])->name('cotizacion-delete');
    
    // Rutas para costos de prendas
    Route::post('/costos/guardar', [CostoPrendaController::class, 'guardar'])->name('costos.guardar');
    Route::get('/costos/obtener/{cotizacion_id}', [CostoPrendaController::class, 'obtener'])->name('costos.obtener');
    
    // Rutas para notas de tallas
    Route::post('/prenda/{prendaId}/notas-tallas', [ContadorController::class, 'guardarNotasTallas'])->name('prenda.guardar-notas-tallas');
    
    // Ruta para texto personalizado de tallas (módulo contador)
    Route::post('/prenda/{prendaId}/texto-personalizado-tallas', [ContadorController::class, 'guardarTextoPersonalizadoTallas'])->name('prenda.guardar-texto-personalizado-tallas');
    
    // Rutas para PDF
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
    
    // Ruta para cambiar estado de cotización
    Route::patch('/cotizacion/{id}/estado', [ContadorController::class, 'cambiarEstado'])->name('cotizacion.cambiar-estado');
    
    // Ruta para obtener costos de prendas
    Route::get('/cotizacion/{id}/costos', [ContadorController::class, 'obtenerCostos'])->name('cotizacion.costos');
    
    // Ruta para guardar tallas costos
    Route::post('/tallas-costos', [ContadorController::class, 'guardarTallasCostos'])->name('tallas-costos.guardar');
    
    // Ruta para obtener contador de cotizaciones pendientes
    Route::get('/cotizaciones-pendientes-count', [ContadorController::class, 'cotizacionesPendientesCount'])->name('cotizaciones-pendientes-count');
    
    // Ruta para perfil del contador
    Route::get('/perfil', [ContadorController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [ContadorController::class, 'updateProfile'])->name('profile.update');
});


