<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Cartera\CarteraPedidosController;
use App\Infrastructure\Http\Controllers\Cartera\CarteraSugerenciasController;

// ========================================
// RUTAS DE CARTERA - PEDIDOS (VISTAS PRINCIPALES)
// ========================================
Route::middleware(['auth', 'role:cartera,admin,supervisor_gerencia'])->prefix('cartera')->name('cartera.')->group(function () {
    // Gestión de pedidos por aprobar (pendientes)
    Route::get('/pedidos', function () {
        return view('cartera-pedidos.cartera-pedidos-supervisor');
    })->name('pedidos');
    
    // Gestión de pedidos aprobados por cartera
    Route::get('/aprobados', function () {
        return view('cartera-pedidos.cartera-aprobados');
    })->name('aprobados');
    
    // Gestión de pedidos rechazados por cartera
    Route::get('/rechazados', function () {
        return view('cartera-pedidos.cartera-rechazados');
    })->name('rechazados');
    
    // Gestión de pedidos anulados
    Route::get('/anulados', function () {
        return view('cartera-pedidos.cartera-anulados');
    })->name('anulados');
});

// ========================================
// API CARTERA - PEDIDOS (CRUD Y OPERACIONES)
// ========================================
Route::middleware(['auth', 'role:cartera,admin,supervisor_gerencia,supervisor_pedidos'])->prefix('api/cartera')->name('api.cartera.')->group(function () {
    // GET pedidos por estado (cartera) - principal para pendientes
    Route::get('/pedidos', [CarteraPedidosController::class, 'obtenerPedidos'])->name('list');
    
    // GET pedidos aprobados (PENDIENTE_SUPERVISOR)
    Route::get('/aprobados', [CarteraPedidosController::class, 'obtenerAprobados'])->name('aprobados');
    
    // GET pedidos rechazados (RECHAZADO_CARTERA)
    Route::get('/rechazados', [CarteraPedidosController::class, 'obtenerRechazados'])->name('rechazados');
    
    // GET pedidos anulados (Anulada)
    Route::get('/anulados', [CarteraPedidosController::class, 'obtenerAnulados'])->name('anulados');
    
    // GET opciones de filtro (clientes y fechas únicos)
    Route::get('/opciones-filtro', [CarteraPedidosController::class, 'obtenerOpcionesFiltro'])->name('opciones-filtro');
    
    // POST aprobar pedido
    Route::post('/pedidos/{id}/aprobar', [CarteraPedidosController::class, 'aprobarPedido'])->name('aprobar');
    
    // POST rechazar pedido
    Route::post('/pedidos/{id}/rechazar', [CarteraPedidosController::class, 'rechazarPedido'])->name('rechazar');
    
    // GET datos de factura para ver en modal
    Route::get('/pedidos/{id}/factura-datos', [CarteraPedidosController::class, 'obtenerDatosFactura'])->name('factura-datos');
});

// ========================================
// API CARTERA - SUGERENCIAS DE FILTROS
// ========================================
Route::middleware(['auth', 'role:cartera,admin,supervisor_gerencia'])->prefix('api/cartera')->name('api.cartera.')->group(function () {
    
    // SUGERENCIAS PARA PENDIENTES
    Route::post('/pedidos/sugerencias/clientes', [CarteraSugerenciasController::class, 'clientesPendientes'])
        ->name('sugerencias.clientes.pendientes');
    Route::post('/pedidos/sugerencias/numeros', [CarteraSugerenciasController::class, 'numerosPendientes'])
        ->name('sugerencias.numeros.pendientes');
    Route::post('/pedidos/sugerencias/fechas', [CarteraSugerenciasController::class, 'fechasPendientes'])
        ->name('sugerencias.fechas.pendientes');
    
    // SUGERENCIAS PARA RECHAZADOS
    Route::post('/rechazados/sugerencias/clientes', [CarteraSugerenciasController::class, 'clientesRechazados'])
        ->name('sugerencias.clientes.rechazados');
    Route::post('/rechazados/sugerencias/numeros', [CarteraSugerenciasController::class, 'numerosRechazados'])
        ->name('sugerencias.numeros.rechazados');
    Route::post('/rechazados/sugerencias/fechas', [CarteraSugerenciasController::class, 'fechasRechazados'])
        ->name('sugerencias.fechas.rechazados');
    
    // SUGERENCIAS PARA APROBADOS
    Route::post('/aprobados/sugerencias/clientes', [CarteraSugerenciasController::class, 'clientesAprobados'])
        ->name('sugerencias.clientes.aprobados');
    Route::post('/aprobados/sugerencias/numeros', [CarteraSugerenciasController::class, 'numerosAprobados'])
        ->name('sugerencias.numeros.aprobados');
    Route::post('/aprobados/sugerencias/fechas', [CarteraSugerenciasController::class, 'fechasAprobados'])
        ->name('sugerencias.fechas.aprobados');
    
    // SUGERENCIAS PARA ANULADOS
    Route::post('/anulados/sugerencias/clientes', [CarteraSugerenciasController::class, 'clientesAnulados'])
        ->name('sugerencias.clientes.anulados');
    Route::post('/anulados/sugerencias/numeros', [CarteraSugerenciasController::class, 'numerosAnulados'])
        ->name('sugerencias.numeros.anulados');
    Route::post('/anulados/sugerencias/fechas', [CarteraSugerenciasController::class, 'fechasAnulados'])
        ->name('sugerencias.fechas.anulados');
});
