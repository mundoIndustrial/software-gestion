<?php

use App\Infrastructure\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\UserController;
use App\Infrastructure\Http\Controllers\Web\DashboardController;
use App\Infrastructure\Http\Controllers\Web\EntregaController;
use App\Infrastructure\Http\Controllers\Web\EntregasCompletasController;


Route::get('/', function () {
    return view('welcome');
});

// ========================================
// RUTAS DE STORAGE - Servir imágenes con fallback de extensiones
// ========================================
// Logica centralizada en StorageService para mejor organizacion

Route::get('/storage/{tipo}/{path}', [App\Infrastructure\Http\Controllers\Storage\StorageController::class, 'serve'])
    ->where(['tipo' => 'cotizaciones|prendas|pedidos|firmas|bodega', 'path' => '.*'])
    ->name('storage.files');

Route::get('/storage/bodega/{path}', [App\Infrastructure\Http\Controllers\Storage\StorageController::class, 'serve'])
    ->where('path', '.*')
    ->defaults('tipo', 'bodega')
    ->name('storage.bodega');

// Rutas con nombre específico para compatibilidad
Route::get('/storage/cotizaciones/{path}', [App\Infrastructure\Http\Controllers\Storage\StorageController::class, 'serveCotizaciones'])
    ->where('path', '.*')
    ->name('storage.cotizaciones');

Route::get('/storage/prendas/{path}', [App\Infrastructure\Http\Controllers\Storage\StorageController::class, 'servePrendas'])
    ->where('path', '.*')
    ->name('storage.prendas');

Route::get('/storage/pedidos/{path}', [App\Infrastructure\Http\Controllers\Storage\StorageController::class, 'servePedidos'])
    ->where('path', '.*')
    ->name('storage.pedidos');

// ========================================
// INSUMOS ROUTES - MODULO INSUMOS
// ========================================
require base_path('routes/insumos.php');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'supervisor-access', 'block-costura-reflectivo-dashboard', 'restrict-visualizador-recibos-logo', 'restrict-lavanderia-role'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // ========================================
    // RUTAS DE LECTURA - Datos Publicos de Pedidos (consultas, sin logica de negocio)
    // ========================================
    Route::get('/pedidos-public/{id}/factura-datos', [App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidoDocumentosController::class, 'obtenerDatosFactura'])
        ->where('id', '[0-9]+')
        ->name('pedidos.public.factura-datos');
    
    Route::get('/pedidos-public/{pedidoId}/ancho-metraje-prenda/{prendaId}', [App\Infrastructure\Http\Controllers\PedidoQueryController::class, 'obtenerAnchoMetrajePrendaPublico'])
        ->where('pedidoId', '[0-9]+')
        ->where('prendaId', '[0-9]+')
        ->name('pedidos.public.ancho-metraje-prenda');
    
    // ========================================
    // RUTA PARA REFRESCAR TOKEN CSRF (Prevenir error 419)
    // ========================================
    Route::get('/refresh-csrf', function () {
        return response()->json([
            'token' => csrf_token(),
            'timestamp' => now()->toIso8601String()
        ]);
    })->name('refresh.csrf');
});



Route::middleware(['auth', 'supervisor-access'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/dashboard/entregas-costura-data', [DashboardController::class, 'getEntregasCosturaData'])->name('dashboard.entregas-costura-data');
    Route::get('/dashboard/entregas-corte-data', [DashboardController::class, 'getEntregasCorteData'])->name('dashboard.entregas-corte-data');
    Route::get('/dashboard/kpis', [DashboardController::class, 'getKPIs'])->name('dashboard.kpis');
    Route::get('/dashboard/reporte-seguimiento', [DashboardController::class, 'reporteSeguimiento'])->name('dashboard.reporte-seguimiento');
    Route::get('/dashboard/timeline-pedidos', [DashboardController::class, 'timelinePedidos'])->name('dashboard.timeline-pedidos');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/news', [DashboardController::class, 'getNews'])->name('dashboard.news');
    Route::get('/dashboard/admin-notifications', [DashboardController::class, 'getAdminNotifications'])->name('dashboard.admin-notifications');
    Route::post('/dashboard/news/mark-all-read', [DashboardController::class, 'markAllAsRead'])->name('dashboard.news.mark-all-read');
    Route::get('/dashboard/audit-stats', [DashboardController::class, 'getAuditStats'])->name('dashboard.audit-stats');
    Route::get('/entrega/{tipo}', [EntregaController::class, 'index'])->name('entrega.index')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/costura-data', [EntregaController::class, 'costuraData'])->name('entrega.costura-data')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/corte-data', [EntregaController::class, 'corteData'])->name('entrega.corte-data')->where('tipo', 'pedido|bodega');
    Route::post('/entrega/{tipo}', [EntregaController::class, 'store'])->name('entrega.store')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/order-data/{pedido}', [EntregaController::class, 'orderData'])->name('entrega.order-data')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/garments/{pedido}', [EntregaController::class, 'garments'])->name('entrega.garments')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/sizes/{pedido}/{prenda}', [EntregaController::class, 'sizes'])->name('entrega.sizes')->where('tipo', 'pedido|bodega');
    Route::patch('/entrega/{tipo}/{subtipo}/{id}', [EntregaController::class, 'update'])->name('entrega.update')->where('tipo', 'pedido|bodega')->where('subtipo', 'costura|corte');
    Route::delete('/entrega/{tipo}/{subtipo}/{id}', [EntregaController::class, 'destroy'])->name('entrega.destroy')->where('tipo', 'pedido|bodega')->where('subtipo', 'costura|corte');
    
    // Rutas para Entregas Completas (Supervisor -> Despacho -> Asesor)
    Route::get('/entregas-completas', [EntregasCompletasController::class, 'index'])->name('entregas-completas.index');
    Route::get('/entregas-completas/{id}', [EntregasCompletasController::class, 'show'])->name('entregas-completas.show');
    Route::get('/api/entregas-completas', [EntregasCompletasController::class, 'apiIndex'])->name('entregas-completas.api');
});

// ========================================
// RUTAS DE Administracion (CONTADOR, OPERARIOS, CONTROL DE CALIDAD)
// ========================================
// Todos los modulos estan centralizados al final del archivo



// ========================================
// RUTA PARA SERVIR IMAGENES DE STORAGE
// ========================================

Route::get('/storage-serve/{path}', [App\Infrastructure\Http\Controllers\Storage\StorageController::class, 'serveLegacy'])
    ->where('path', '.*')
    ->name('storage.serve');

// ========================================
// RUTAS DE AUTENTICACION
// ========================================
require __DIR__.'/auth.php';

// ========================================
// RUTAS DE ADMINISTACION
// ========================================
// Las rutas de asesores ESTAN definidas en el archivo asesores.php
// Las rutas de operarios ESTAN definidas en el archivo operario.php

// ========================================
// RUTAS DE ASESORES (MODULO INDEPENDIENTE)
// ========================================
// Las rutas de asesores ESTAN definidas en el archivo asesores.php
require __DIR__.'/asesores.php';

// ========================================
// RUTAS DE DESPACHO (MODULO NUEVO)
// ========================================
require __DIR__.'/despacho.php';

// ========================================
// RUTAS DE BODEGA (MODULO NUEVO)
// ========================================
require __DIR__.'/bodega.php';

// ========================================
// RUTAS DE MODULOS INDEPENDIENTES
// ========================================
// Admin: routes/admin.php
// Operarios: routes/operario.php
// Asesores: routes/asesores.php (routes/asesores.php)
// Supervisor de Asesores: routes/supervisor-asesores.php
// Visualizador Logo: routes/visualizador-logo.php
require base_path('routes/admin.php');
require base_path('routes/operario.php');
require base_path('routes/supervisor-asesores.php');
require base_path('routes/visualizador-logo.php');
require base_path('routes/visualizador-pedidos.php');
require base_path('routes/insumos.php');
require base_path('routes/supervisor-pedidos.php');
require base_path('routes/recepcion-despacho.php');
require base_path('routes/tableros.php');
require base_path('routes/registros-pedidos.php');
require base_path('routes/asistencia-personal.php');
require base_path('routes/cartera.php');
require base_path('routes/procesos.php');
require base_path('routes/recibos.php');
require base_path('routes/pedidos.php');
require base_path('routes/notifications.php');
require base_path('routes/catalogo.php');
require base_path('routes/inventario-telas.php');
require base_path('routes/prendas.php');
require base_path('routes/festivos.php');
require base_path('routes/broadcasting.php');
require base_path('routes/seguimiento-proceso.php');
require base_path('routes/epp.php');
require base_path('routes/entregas-talleres.php');
require base_path('routes/lavanderia.php');

// ========================================




// ========================================
// BROADCASTING AUTH ROUTES (WebSocket)
// ========================================

// Las rutas de broadcasting ESTAN modularizadas en routes/broadcasting.php
// Las rutas de seguimiento de procesos ESTAN en routes/seguimiento-proceso.php
