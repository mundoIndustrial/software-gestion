<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE CATÁLOGOS - APIs públicas de datos maestros
// ========================================
// Accesibles para todos los roles autenticados sin restricciones

Route::middleware(['auth'])->group(function () {
    Route::get('/api/public/tipos-manga', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'obtenerTiposManga'])
        ->name('api.public.tipos-manga');
    Route::post('/api/public/tipos-manga', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'crearObtenerTipoManga'])
        ->name('api.public.tipos-manga.create');
    
    Route::get('/api/public/tipos-broche-boton', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'obtenerTiposBrocheBoton'])
        ->name('api.public.tipos-broche-boton');
    
    Route::get('/api/public/telas', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'obtenerTelas'])
        ->name('api.public.telas');
    Route::post('/api/public/telas', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'crearObtenerTela'])
        ->name('api.public.telas.create');
    
    Route::get('/api/public/colores', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'obtenerColores'])
        ->name('api.public.colores');
    Route::post('/api/public/colores', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'crearObtenerColor'])
        ->name('api.public.colores.create');
});
