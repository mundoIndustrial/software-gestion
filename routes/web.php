<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RegistroOrdenController;
use App\Http\Controllers\RegistroBodegaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\TablerosController;
use App\Http\Controllers\VistasController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::get('/dashboard/entregas-costura-data', [DashboardController::class, 'getEntregasCosturaData'])->name('dashboard.entregas-costura-data');
    Route::get('/dashboard/entregas-corte-data', [DashboardController::class, 'getEntregasCorteData'])->name('dashboard.entregas-corte-data');
    Route::get('/dashboard/kpis', [DashboardController::class, 'getKPIs'])->name('dashboard.kpis');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/news', [DashboardController::class, 'getNews'])->name('dashboard.news');
    Route::get('/entrega/{tipo}', [EntregaController::class, 'index'])->name('entrega.index')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/costura-data', [EntregaController::class, 'costuraData'])->name('entrega.costura-data')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/corte-data', [EntregaController::class, 'corteData'])->name('entrega.corte-data')->where('tipo', 'pedido|bodega');
    Route::post('/entrega/{tipo}', [EntregaController::class, 'store'])->name('entrega.store')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/order-data/{pedido}', [EntregaController::class, 'orderData'])->name('entrega.order-data')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/garments/{pedido}', [EntregaController::class, 'garments'])->name('entrega.garments')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/sizes/{pedido}/{prenda}', [EntregaController::class, 'sizes'])->name('entrega.sizes')->where('tipo', 'pedido|bodega');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/registros', [RegistroOrdenController::class, 'index'])->name('registros.index');
    Route::get('/registros/next-pedido', [RegistroOrdenController::class, 'getNextPedido'])->name('registros.next-pedido');
    Route::get('/registros/{pedido}', [RegistroOrdenController::class, 'show'])->name('registros.show');
    Route::post('/registros', [RegistroOrdenController::class, 'store'])->name('registros.store');
    Route::post('/registros/validate-pedido', [RegistroOrdenController::class, 'validatePedido'])->name('registros.validatePedido');
    Route::patch('/registros/{pedido}', [RegistroOrdenController::class, 'update'])->name('registros.update');
    Route::delete('/registros/{pedido}', [RegistroOrdenController::class, 'destroy'])->name('registros.destroy');
    Route::post('/registros/update-status', [RegistroOrdenController::class, 'updateStatus'])->name('registros.updateStatus');
    Route::get('/registros/{pedido}/entregas', [RegistroOrdenController::class, 'getEntregas'])->name('registros.entregas');
    Route::get('/bodega', [RegistroBodegaController::class, 'index'])->name('bodega.index');
    Route::get('/bodega/next-pedido', [RegistroBodegaController::class, 'getNextPedido'])->name('bodega.next-pedido');
    Route::get('/bodega/{pedido}', [RegistroBodegaController::class, 'show'])->name('bodega.show');
    Route::get('/bodega/{pedido}/entregas', [RegistroBodegaController::class, 'getEntregas'])->name('bodega.entregas');
    Route::post('/bodega', [RegistroBodegaController::class, 'store'])->name('bodega.store');
    Route::post('/bodega/validate-pedido', [RegistroBodegaController::class, 'validatePedido'])->name('bodega.validatePedido');
    Route::patch('/bodega/{pedido}', [RegistroBodegaController::class, 'update'])->name('bodega.update');
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/create-database', [ConfiguracionController::class, 'createDatabase'])->name('configuracion.createDatabase');
    Route::post('/configuracion/select-database', [ConfiguracionController::class, 'selectDatabase'])->name('configuracion.selectDatabase');
    Route::post('/configuracion/migrate-users', [ConfiguracionController::class, 'migrateUsers'])->name('configuracion.migrateUsers');
    Route::get('/tableros', [TablerosController::class, 'index'])->name('tableros.index');
    Route::post('/tableros', [TablerosController::class, 'store'])->name('tableros.store');
    Route::patch('/tableros/{id}', [TablerosController::class, 'update'])->name('tableros.update');
    Route::delete('/tableros/{id}', [TablerosController::class, 'destroy'])->name('tableros.destroy');
    Route::get('/vistas', [VistasController::class, 'index'])->name('vistas.index');
    Route::get('/api/vistas/search', [VistasController::class, 'search'])->name('api.vistas.search');
});

require __DIR__.'/auth.php';
