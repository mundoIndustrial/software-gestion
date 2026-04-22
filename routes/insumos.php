<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Insumos\InsumosController;
use App\Infrastructure\Http\Controllers\Insumos\PlooterController;
use App\Infrastructure\Http\Controllers\Insumos\RecibosController;
use App\Infrastructure\Http\Controllers\RegistroOrdenController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorReceiptsController;

// ========================================
// RUTAS DE INSUMOS
// ========================================
Route::middleware(['auth', 'insumos-access'])->prefix('insumos')->name('insumos.')->group(function () {
    Route::get('/materiales', [InsumosController::class, 'materiales'])->name('materiales.index');
    Route::get('/materiales/reflectivo', [InsumosController::class, 'materialesReflectivo'])->name('materiales.reflectivo');
    
    // DEBUG: Endpoint que devuelve los parámetros exactos que recibe
    Route::get('/debug-filter-params', function(\Illuminate\Http\Request $request) {
        return response()->json([
            'filter_columns' => $request->get('filter_columns', []),
            'filter_values' => $request->get('filter_values', []),
            'page' => $request->get('page', 1),
            'query_string' => $request->getQueryString(),
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('debug-filter-params');
    Route::post('/materiales/{pedido}/guardar', [InsumosController::class, 'guardarMateriales'])->name('materiales.guardar');
    Route::post('/materiales/{pedido}/eliminar', [InsumosController::class, 'eliminarMaterial'])->name('materiales.eliminar');
    Route::post('/materiales/{numeroPedido}/guardar-ancho-metraje', [InsumosController::class, 'guardarAnchoMetraje'])->name('materiales.guardar-ancho-metraje');
    Route::get('/materiales/{numeroPedido}/obtener-ancho-metraje', [InsumosController::class, 'obtenerAnchoMetraje'])->name('materiales.obtener-ancho-metraje');
    Route::get('/materiales/{numeroPedido}/obtener-prendas', [InsumosController::class, 'obtenerPrendas'])->name('materiales.obtener-prendas');
    Route::get('/materiales/{numeroPedido}/obtener-ancho-metraje-prenda/{prendaId}', [InsumosController::class, 'obtenerAnchoMetrajePrenda'])->name('materiales.obtener-ancho-metraje-prenda');
    Route::post('/materiales/{numeroPedido}/guardar-ancho-metraje-prenda', [InsumosController::class, 'guardarAnchoMetrajePrenda'])->name('materiales.guardar-ancho-metraje-prenda');
    Route::post('/materiales/{numeroPedido}/eliminar-ancho-metraje-prenda', [InsumosController::class, 'eliminarAnchoMetrajePrenda'])->name('materiales.eliminar-ancho-metraje-prenda');
    Route::get('/materiales/{numeroPedido}/obtener-colores-prenda/{prendaId}', [InsumosController::class, 'obtenerColoresPrenda'])->name('materiales.obtener-colores-prenda');
    Route::get('/materiales/{numeroPedido}/obtener-recibo-prenda/{prendaId}', [InsumosController::class, 'obtenerReciboPrenda'])->name('materiales.obtener-recibo-prenda');
    Route::get('/api/materiales/{pedido}', [InsumosController::class, 'obtenerMateriales'])->name('api.materiales');
    Route::get('/api/filtros/{column}', [InsumosController::class, 'obtenerValoresFiltro'])->name('api.filtros');
    Route::get('/api/contar-costura-pendiente', [InsumosController::class, 'contarCosturaPendiente'])->name('api.contar.costura.pendiente');
    Route::get('/api/recibos-costura-pendiente', [InsumosController::class, 'obtenerRecibosCosTuraPendiente'])->name('api.recibos.costura.pendiente');
    Route::get('/api/recibos-procesos/observacion', [SupervisorReceiptsController::class, 'obtenerObservacionReciboProceso'])->name('api.recibos-procesos.observacion.obtener');
    Route::post('/api/recibo/{id}/marcar-visto', [InsumosController::class, 'marcarReciboVisto'])->name('api.recibo.marcar-visto');
    Route::post('/guardar-observaciones', [InsumosController::class, 'guardarObservaciones'])->name('guardar-observaciones');
    Route::post('/materiales/{numeroPedido}/cambiar-estado', [InsumosController::class, 'cambiarEstado'])->name('materiales.cambiar-estado');
    Route::post('/materiales/recibo/{reciboId}/cambiar-estado', [InsumosController::class, 'cambiarEstadoRecibo'])->name('materiales.recibo.cambiar-estado');
    Route::post('/materiales/{materialId}/toggle-marcado', [RecibosController::class, 'toggleMarcado'])->name('materiales.toggle-marcado');
    Route::post('/materiales/{reciboId}/pasar-revisar', [RecibosController::class, 'pasarRevisar'])->name('materiales.pasar-revisar');
    Route::post('/materiales/{reciboId}/anular', [RecibosController::class, 'anularRecibo'])->name('materiales.anular-recibo');
    Route::get('/materiales/recibos-costura', [RegistroOrdenController::class, 'recibosCostura'])->name('materiales.recibos-costura');
    Route::get('/test', function () {
        return view('insumos.test');
    })->name('test');
    
    // Cálculo de Metrajes
    Route::get('/metrajes', function () {
        return view('insumos.metrajes.index');
    })->name('metrajes.index');
    
    // Gestión de Plooter
    Route::prefix('plooter')->name('plooter.')->group(function () {
        Route::get('/', [PlooterController::class, 'index'])->name('index');
        Route::get('/datos', [PlooterController::class, 'obtenerDatos'])->name('datos');
        Route::get('/estadisticas', [PlooterController::class, 'obtenerEstadisticas'])->name('estadisticas');
        Route::get('/estado/{estado}', [PlooterController::class, 'filtrarPorEstado'])->name('filtrar-estado');
        Route::delete('/{id}', [PlooterController::class, 'remover'])->name('remover');
        Route::post('/{reciboId}/registrar-fecha-envio', [PlooterController::class, 'registrarFechaEnvio'])->name('registrar-fecha-envio');
        Route::post('/{reciboId}/registrar-fecha-llegada', [PlooterController::class, 'registrarFechaLlegada'])->name('registrar-fecha-llegada');
    });
});
