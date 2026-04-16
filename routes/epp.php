<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Epp\EppController;
use App\Infrastructure\Http\Controllers\Epp\PedidoEppController;

$isApiContext = collect(Route::getGroupStack())->contains(function (array $group): bool {
    $prefix = (string) ($group['prefix'] ?? '');
    $as = (string) ($group['as'] ?? '');
    $middleware = $group['middleware'] ?? [];

    if (is_string($middleware)) {
        $middleware = [$middleware];
    }

    return $prefix === 'api'
        || str_starts_with($prefix, 'api/')
        || str_starts_with($as, 'api.')
        || in_array('api', $middleware, true);
});

if (!$isApiContext) {
    /**
     * EPP Management Routes (Web)
     * Gestión de Equipo de Protección Personal desde panel administrativo
     */
    Route::middleware(['auth', 'role:gestor_epp,admin'])->prefix('epp')->name('epp.')->group(function () {
        Route::get('/', [EppController::class, 'vistaGestion'])
            ->name('inicio');

        Route::get('/test', [EppController::class, 'test'])
            ->name('test');
    });

    // Gestión de EPP - Solo accesible para gestor_epp y admin
    Route::middleware(['web', 'auth', 'role:gestor_epp,admin'])->group(function () {
        Route::get('epp/gestion', [EppController::class, 'indexSimple'])
            ->name('epp.gestion.web');

        Route::post('epp', [EppController::class, 'crearEppSimple'])
            ->name('epp.store');
    });

    // CRUD de EPP - Solo accesible para gestor_epp y admin
    Route::middleware(['web', 'auth', 'role:gestor_epp,admin'])->group(function () {
        Route::get('epp/{id}', [EppController::class, 'show'])
            ->name('epp.show');

        Route::put('epp/{id}', [EppController::class, 'update'])
            ->name('epp.update');

        Route::delete('epp/{id}', [EppController::class, 'destroy'])
            ->name('epp.destroy');

        Route::post('epp/{id}/actualizar', [EppController::class, 'actualizarDirecto'])
            ->name('epp.actualizar-directo');

        Route::post('epp/{id}/eliminar', [EppController::class, 'eliminarDirecto'])
            ->name('epp.eliminar-directo');
    });

    // Gestión de EPP en pedidos (rutas web)
    Route::middleware(['web'])->post('pedidos/{pedidoId}/epp/agregar', [EppController::class, 'agregar'])
        ->name('pedidos.epp.agregar');

    Route::middleware(['web'])->get('pedidos/{pedidoId}/epp/{pedidoEppId}', [EppController::class, 'obtenerEppDelPedidoPorId'])
        ->name('pedidos.epp.obtener-por-id');

    Route::middleware(['web'])->patch('pedidos/{pedidoId}/epp/{pedidoEppId}', [EppController::class, 'actualizarEppDelPedido'])
        ->name('pedidos.epp.actualizar');
}

if ($isApiContext) {
    /**
     * EPP API Routes
     * Gestión de imágenes, CRUD, búsqueda e integración con pedidos
     */

    // Gestión de imágenes de EPP
    Route::middleware('api')->prefix('epp/{eppId}/imagenes')->name('epp.imagenes.')->group(function () {
        Route::post('/', [EppController::class, 'subirImagen'])
            ->name('subir');
    });

    // Subir/eliminar imagen de EPP
    Route::middleware('api')->post('epp/imagenes/upload', [EppController::class, 'subirImagenEpp'])
        ->name('epp.imagenes.upload');

    Route::middleware('api')->delete('epp/imagenes/{imagenId}', [EppController::class, 'eliminarImagen'])
        ->name('epp.imagenes.eliminar');

    // Búsqueda y listado de EPP (usado por asesores)
    Route::middleware('api')->get('epp', [EppController::class, 'index'])
        ->name('epp.index');

    // Datos para la vista de gestión EPP (tabla/paginación en frontend)
    Route::middleware('api')->get('epp/gestion', [EppController::class, 'indexSimple'])
        ->name('epp.gestion.data');

    Route::middleware('api')->get('epps/buscar', [EppController::class, 'buscar'])
        ->name('epp.buscar');

    Route::middleware('api')->get('epp/categorias/all', [EppController::class, 'categorias'])
        ->name('epp.categorias');

    Route::middleware('api')->get('epp/categorias/simple', [EppController::class, 'categoriasSimple'])
        ->name('epp.categorias-simple');

    // Gestión de EPP en pedidos - Rutas RESTful
    Route::middleware('api')->prefix('pedidos/{pedido}/epps')->name('pedidos.epps.')->group(function () {
        Route::get('/', [PedidoEppController::class, 'index'])
            ->name('index');

        Route::post('/', [PedidoEppController::class, 'store'])
            ->name('store');

        Route::patch('{pedidoEpp}', [PedidoEppController::class, 'update'])
            ->name('update');

        Route::delete('{pedidoEpp}', [PedidoEppController::class, 'destroy'])
            ->name('destroy');

        Route::get('/exportar/json', [PedidoEppController::class, 'exportarJson'])
            ->name('exportar-json');
    });

    // Gestión de EPP en pedidos (rutas antiguas - compatibilidad)
    Route::middleware('api')->get('pedidos/{pedidoId}/epp', [EppController::class, 'obtenerDelPedido'])
        ->name('pedidos.epp.obtener');

    Route::middleware('api')->post('pedidos/{pedidoId}/epp/agregar', [EppController::class, 'agregar'])
        ->name('pedidos.epp.agregar');

    Route::middleware('api')->delete('pedidos/{pedidoId}/epp/{eppId}', [EppController::class, 'eliminar'])
        ->name('pedidos.epp.eliminar');

    // Gestión de imágenes de EPP en pedidos
    Route::middleware('api')->prefix('pedido-epp')->name('pedido-epp.imagenes.')->group(function () {
        Route::get('{pedidoEppId}/imagenes', [EppController::class, 'obtenerImagenes'])
            ->name('obtener');

        Route::post('{pedidoEppId}/imagenes', [EppController::class, 'agregarImagenes'])
            ->name('agregar');

        Route::delete('imagenes/{imagenId}', [EppController::class, 'eliminarImagenPedidoEpp'])
            ->name('eliminar');
    });
}
