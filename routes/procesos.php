<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\ProcesosPrendaDetalleController;

/**
 * API Routes for Procesos (DDD)
 * Gestión de procesos de prendas, estados y cambios
 */
Route::middleware('auth')->prefix('procesos')->name('procesos.')->group(function () {
    // Obtener tipos de procesos disponibles
    Route::get('tipos', [ProcesosPrendaDetalleController::class, 'tipos'])
        ->name('tipos');

    // Procesos de una prenda
    Route::prefix('prendas/{prendaId}')->name('prenda.')->group(function () {
        Route::get('/', [ProcesosPrendaDetalleController::class, 'obtenerPorPrenda'])
            ->name('listar');
        
        Route::post('/', [ProcesosPrendaDetalleController::class, 'crear'])
            ->name('crear');
    });

    // Operaciones en procesos específicos
    Route::prefix('{procesoId}')->name('proceso.')->group(function () {
        Route::put('/', [ProcesosPrendaDetalleController::class, 'actualizar'])
            ->name('actualizar');
        
        Route::delete('/', [ProcesosPrendaDetalleController::class, 'eliminar'])
            ->name('eliminar');
        
        // Cambios de estado
        Route::post('aprobar', [ProcesosPrendaDetalleController::class, 'aprobar'])
            ->name('aprobar');
        
        Route::post('rechazar', [ProcesosPrendaDetalleController::class, 'rechazar'])
            ->name('rechazar');

        // Activar/Desactivar recibo
        Route::post('activar-recibo', [ProcesosPrendaDetalleController::class, 'activarRecibo'])
            ->name('activar-recibo');

        // Gestión de imágenes
        Route::prefix('imagenes')->name('imagenes.')->group(function () {
            Route::get('/', [ProcesosPrendaDetalleController::class, 'obtenerImagenes'])
                ->name('listar');
            
            Route::post('/', [ProcesosPrendaDetalleController::class, 'subirImagen'])
                ->name('subir');
            
            Route::post('{imagenId}/principal', [ProcesosPrendaDetalleController::class, 'marcarComoPrincipal'])
                ->name('principal');
            
            Route::delete('{imagenId}', [ProcesosPrendaDetalleController::class, 'eliminarImagen'])
                ->name('eliminar');
        });
    });
});

/**
 * API Routes for Procesos - Activar/Anular Recibos (middleware api)
 * Rutas específicas con middleware api
 */
Route::middleware(['api'])->prefix('procesos')->group(function () {
    Route::post('{procesoId}/activar-recibo', [ProcesosPrendaDetalleController::class, 'activarRecibo'])
        ->name('procesos.activar-recibo');

    Route::post('{procesoId}/anular-recibo', [ProcesosPrendaDetalleController::class, 'anularRecibo'])
        ->name('procesos.anular-recibo');
});
