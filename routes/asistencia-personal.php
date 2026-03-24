<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\AsistenciaPersonalWebController;
use App\Infrastructure\Http\Controllers\AsistenciaPersonalController;

// ========================================
// RUTAS WEB - ASISTENCIA PERSONAL (CRUD)
// ========================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/asistencia-personal', [AsistenciaPersonalWebController::class, 'index'])
        ->name('asistencia-personal.index');
    Route::get('/asistencia-personal/crear', [AsistenciaPersonalWebController::class, 'create'])
        ->name('asistencia-personal.create');
    Route::post('/asistencia-personal', [AsistenciaPersonalWebController::class, 'store'])
        ->name('asistencia-personal.store');
    Route::get('/asistencia-personal/{id}', [AsistenciaPersonalWebController::class, 'show'])
        ->name('asistencia-personal.show');
    Route::get('/asistencia-personal/{id}/editar', [AsistenciaPersonalWebController::class, 'edit'])
        ->name('asistencia-personal.edit');
    Route::patch('/asistencia-personal/{id}', [AsistenciaPersonalWebController::class, 'update'])
        ->name('asistencia-personal.update');
    Route::delete('/asistencia-personal/{id}', [AsistenciaPersonalWebController::class, 'destroy'])
        ->name('asistencia-personal.destroy');
});

// ========================================
// RUTAS API - ASISTENCIA PERSONAL
// ========================================
Route::middleware(['auth'])->prefix('asistencia-personal')->name('asistencia-personal.')->group(function () {
    Route::post('/procesar-pdf', [AsistenciaPersonalController::class, 'procesarPDF'])
        ->name('procesar-pdf');
    Route::post('/validar-registros', [AsistenciaPersonalController::class, 'validarRegistros'])
        ->name('validar-registros');
    Route::post('/crear-personal-batch', [AsistenciaPersonalController::class, 'crearPersonalBatch'])
        ->name('crear-personal-batch');
    Route::post('/guardar-registros', [AsistenciaPersonalController::class, 'guardarRegistros'])
        ->name('guardar-registros');
    Route::post('/calcular-horas', [AsistenciaPersonalController::class, 'calcularHoras'])
        ->name('calcular-horas');
    Route::get('/reportes/{id}/detalles', [AsistenciaPersonalController::class, 'getReportDetails'])
        ->name('reportes.detalles');
    Route::get('/reportes/{id}/ausencias', [AsistenciaPersonalController::class, 'getAbsenciasDelDia'])
        ->name('reportes.ausencias');
    Route::post('/guardar-asistencia-detallada', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'guardarCambios'])
        ->name('guardar-asistencia-detallada');
    Route::post('/guardar-hora-extra-agregada', [AsistenciaPersonalController::class, 'guardarHoraExtraAgregada'])
        ->name('guardar-hora-extra-agregada');
    Route::post('/guardar-marcas-editadas', [AsistenciaPersonalController::class, 'guardarMarcasEditadas'])
        ->name('guardar-marcas-editadas');
    Route::post('/agregar-marca-faltante', [AsistenciaPersonalController::class, 'agregarMarcaFaltante'])
        ->name('agregar-marca-faltante');
    Route::post('/guardar-marcas-multiples', [AsistenciaPersonalController::class, 'guardarMarcasMultiples'])
        ->name('guardar-marcas-multiples');
    // Ruta de prueba temporal
    Route::get('/obtener-todas-las-personas-test', function() {
        return response()->json([
            'success' => true,
            'test' => 'OK',
            'message' => 'La ruta test funciona'
        ]);
    })->middleware(['auth']);
    Route::post('/obtener-horas-extras-agregadas-batch', [AsistenciaPersonalController::class, 'obtenerHorasExtrasAgregadasBatch'])
        ->name('obtener-horas-extras-agregadas-batch');
    Route::get('/obtener-horas-extras-agregadas/{codigo_persona}', [AsistenciaPersonalController::class, 'obtenerHorasExtrasAgregadas'])
        ->name('obtener-horas-extras-agregadas');
    
    // API Routes - Valor Hora Extra
    Route::get('/valor-hora-extra/{codigoPersona}', [App\Http\Controllers\Api_temp\ValorHoraExtraController::class, 'obtener'])
        ->name('valor-hora-extra.obtener');
    Route::post('/valor-hora-extra/guardar', [App\Http\Controllers\Api_temp\ValorHoraExtraController::class, 'guardar'])
        ->name('valor-hora-extra.guardar');
});

// ========================================
// API ROUTES - ASISTENCIAS DETALLADAS (Control de Horas y Marcas)
// ========================================
Route::prefix('asistencias')->name('asistencias.')->middleware(['api'])->group(function () {
    // Obtener asistencias de un personal en un período
    Route::post('obtener', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'obtenerAsistencias'])
        ->name('obtener');
    
    // Obtener asistencia de un día específico
    Route::post('dia', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'obtenerAsistenciaDelDia'])
        ->name('dia');
    
    // Rellenar inteligentemente marcas faltantes
    Route::post('rellenar-inteligente', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'rellenarInteligente'])
        ->name('rellenar-inteligente');
    
    // Guardar cambios de asistencia
    Route::post('guardar', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'guardarCambios'])
        ->name('guardar');
    
    // Obtener resumen del mes
    Route::post('mes', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'obtenerMes'])
        ->name('mes');
});
