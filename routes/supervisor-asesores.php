<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\SupervisorAsesores\SupervisorAsesoresController;

// ========================================
// RUTAS PARA SUPERVISOR DE ASESORES
// ========================================
Route::middleware(['auth', 'role:supervisor_asesores,supervisor_gerencia,admin,lider_produccion,supervisor_produccion'])->prefix('supervisor-asesores')->name('supervisor-asesores.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [SupervisorAsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-stats', [SupervisorAsesoresController::class, 'dashboardStats'])->name('dashboard-stats');
    
    // Cotizaciones
    Route::get('/cotizaciones', [SupervisorAsesoresController::class, 'cotizacionesIndex'])->name('cotizaciones.index');
    Route::get('/cotizaciones/data', [SupervisorAsesoresController::class, 'cotizacionesData'])->name('cotizaciones.data');
    Route::get('/cotizaciones/filtros/valores', [SupervisorAsesoresController::class, 'cotizacionesFiltrosValores'])->name('cotizaciones.filtros.valores');
    
    // Pedidos
    Route::get('/pedidos', [SupervisorAsesoresController::class, 'pedidosIndex'])->name('pedidos.index');
    Route::get('/pedidos/data', [SupervisorAsesoresController::class, 'pedidosData'])->name('pedidos.data');
    Route::post('/pedidos/{id}/confirmar-correccion', [SupervisorAsesoresController::class, 'confirmarCorreccion'])->name('pedidos.confirmar-correccion');
    
    // Asesores
    Route::get('/asesores', [SupervisorAsesoresController::class, 'asesoresIndex'])->name('asesores.index');
    Route::get('/asesores/data', [SupervisorAsesoresController::class, 'asesoresData'])->name('asesores.data');
    Route::get('/asesores/{id}', [SupervisorAsesoresController::class, 'asesoresShow'])->name('asesores.show');
    
    // Reportes
    Route::get('/reportes', [SupervisorAsesoresController::class, 'reportesIndex'])->name('reportes.index');
    Route::get('/reportes/data', [SupervisorAsesoresController::class, 'reportesData'])->name('reportes.data');
    
    // Perfil
    Route::get('/perfil', [SupervisorAsesoresController::class, 'profileIndex'])->name('profile.index');
    Route::get('/perfil/stats', [SupervisorAsesoresController::class, 'profileStats'])->name('profile.stats');
    Route::post('/perfil/password-update', [SupervisorAsesoresController::class, 'profilePasswordUpdate'])->name('profile.password-update');
});
