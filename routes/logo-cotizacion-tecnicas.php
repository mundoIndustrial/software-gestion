<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController;

Route::prefix('logo-cotizacion-tecnicas')->group(function () {
    Route::get('/tipos-disponibles', [LogoCotizacionTecnicaController::class, 'tiposDisponibles']);
    Route::post('/agregar', [LogoCotizacionTecnicaController::class, 'agregarTecnica']);
    Route::get('/cotizacion/{logoCotizacionId}', [LogoCotizacionTecnicaController::class, 'obtenerTecnicas'])
        ->whereNumber('logoCotizacionId');
    Route::get('/{logoCotizacionId}', [LogoCotizacionTecnicaController::class, 'obtenerTecnicas'])
        ->whereNumber('logoCotizacionId');
    Route::delete('/{prendeId}', [LogoCotizacionTecnicaController::class, 'eliminarTecnica'])
        ->whereNumber('prendeId');
    Route::patch('/{prendeId}/observaciones', [LogoCotizacionTecnicaController::class, 'actualizarObservaciones'])
        ->whereNumber('prendeId');
    Route::put('/{prendeId}/observaciones', [LogoCotizacionTecnicaController::class, 'actualizarObservaciones'])
        ->whereNumber('prendeId');
});
