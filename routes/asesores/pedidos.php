<?php

/**
 * Rutas para Pedidos de Producción - Refactorizado
 * 
 * Ubicación: routes/asesores/pedidos.php
 * 
 * Patrón: RESTful
 * Controller: App\Http\Controllers\Asesores\PedidoProduccionController
 */

use App\Http\Controllers\Asesores\PedidoProduccionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:asesor'])->group(function () {
    
    // Listar todos los pedidos de producción
    Route::get('/pedidos-produccion',
        [PedidoProduccionController::class, 'index'])
        ->name('pedidos-produccion.index');

    // Mostrar formulario para crear pedido desde cotización
    Route::get('/pedidos-produccion/crear-desde-cotizacion', 
        [PedidoProduccionController::class, 'mostrarFormularioCrearDesdeCotzacion'])
        ->name('pedidos-produccion.crear-desde-cotizacion');

    // Crear pedido desde cotización (AJAX/JSON)
    Route::post('/cotizaciones/{cotizacion_id}/crear-pedido-produccion',
        [PedidoProduccionController::class, 'crearDesdeCotzacion'])
        ->name('cotizaciones.crear-pedido-produccion');

    // Obtener próximo número de pedido
    Route::get('/next-pedido',
        [PedidoProduccionController::class, 'obtenerProximoNumero'])
        ->name('next-pedido');

    // Obtener datos de cotización (AJAX)
    Route::get('/cotizaciones/{cotizacion_id}',
        [PedidoProduccionController::class, 'obtenerDatosCotizacion'])
        ->name('cotizaciones.obtener-datos');

    // Ruta adicional para obtener datos de cotización (para compatibilidad)
    Route::get('/obtener-datos-cotizacion/{cotizacion_id}',
        [PedidoProduccionController::class, 'obtenerDatosCotizacion'])
        ->name('obtener-datos-cotizacion');

});
