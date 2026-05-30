<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Web\EntregasTalleresController;

Route::middleware(['auth', 'role:vista-costura,admin'])->prefix('entregas-talleres')->name('entregas-talleres.')->group(function () {
    Route::get('/', [EntregasTalleresController::class, 'index'])->name('index');
    Route::get('/buscar', [EntregasTalleresController::class, 'buscar'])->name('buscar');
    Route::get('/recibo/{id}', [EntregasTalleresController::class, 'showRecibo'])->name('show');
    Route::post('/registrar', [EntregasTalleresController::class, 'store'])->name('registrar');
    Route::get('/historial/{id}', [EntregasTalleresController::class, 'historial'])->name('historial');
    
    // API routes for dynamic interactions
    Route::get('/api/search', [EntregasTalleresController::class, 'apiSearch'])->name('api.search');
    Route::get('/api/recibo/{id}/details', [EntregasTalleresController::class, 'apiReciboDetails'])->name('api.recibo.details');
    Route::get('/novedades-count/{id}', [EntregasTalleresController::class, 'novedadesCount'])->name('novedades.count');
    Route::delete('/eliminar/{id}', [EntregasTalleresController::class, 'destroy'])->name('eliminar');
});
