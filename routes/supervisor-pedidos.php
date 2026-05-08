<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorPedidosController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorReceiptsController;
use App\Infrastructure\Http\Controllers\SupervisorPedidos\SupervisorOrdersController;

/*
|--------------------------------------------------------------------------
| Supervisor Pedidos (Web Shell)
|--------------------------------------------------------------------------
|
| Este archivo conserva solo rutas web que renderizan vistas/páginas.
| Todas las operaciones de datos y acciones del módulo se consumen por
| API REST desde routes/api-supervisor-pedidos.php.
|
*/

Route::middleware(['auth', 'role:supervisor_pedidos,admin'])
    ->prefix('supervisor-pedidos')
    ->name('supervisor-pedidos.')
    ->group(function () {
        // Páginas del módulo
        Route::get('/', [SupervisorOrdersController::class, 'index'])->name('index');
        Route::get('/perfil/editar', [SupervisorPedidosController::class, 'profile'])->name('profile');
        Route::get('/pendientes-bordado-estampado', [SupervisorReceiptsController::class, 'pendientesBordadoEstampado'])
            ->name('pendientes-bordado-estampado');
        Route::get('/pendientes-costura', [SupervisorReceiptsController::class, 'pendientesCostura'])
            ->name('pendientes-costura');
        Route::match(['get', 'post'], '/pendientes-costura/reporte', [SupervisorReceiptsController::class, 'reportePendientesCostura'])
            ->name('pendientes-costura.reporte');
        Route::get('/pendientes-reflectivo', [SupervisorReceiptsController::class, 'pendientesReflectivo'])
            ->name('pendientes-reflectivo');
        Route::get('/pendientes-control-calidad', [SupervisorReceiptsController::class, 'pendientesControlCalidad'])
            ->name('pendientes-control-calidad');
        Route::get('/estadisticas-asesoras', [SupervisorOrdersController::class, 'estadisticasAsesoras'])
            ->name('estadisticas-asesoras');
        Route::get('/entregas-recibidas', [SupervisorOrdersController::class, 'entregasRecibidas'])
            ->name('entregas-recibidas');

        // Vista y documento
        Route::get('/{id}/pdf', [SupervisorOrdersController::class, 'descargarPDF'])->name('pdf');
        Route::get('/{id}', [SupervisorOrdersController::class, 'show'])->name('show');
    });
