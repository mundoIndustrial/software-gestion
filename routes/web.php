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
use App\Http\Controllers\BalanceoController;
use App\Http\Controllers\AsesorController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta de prueba para verificar Echo/Reverb
Route::get('/test-echo', function () {
    return view('test-echo');
})->name('test.echo');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'supervisor-access'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'supervisor-access'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/dashboard/entregas-costura-data', [DashboardController::class, 'getEntregasCosturaData'])->name('dashboard.entregas-costura-data');
    Route::get('/dashboard/entregas-corte-data', [DashboardController::class, 'getEntregasCorteData'])->name('dashboard.entregas-corte-data');
    Route::get('/dashboard/kpis', [DashboardController::class, 'getKPIs'])->name('dashboard.kpis');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/news', [DashboardController::class, 'getNews'])->name('dashboard.news');
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
});

Route::middleware(['auth', 'supervisor-readonly'])->group(function () {
    Route::get('/registros', [RegistroOrdenController::class, 'index'])->name('registros.index');
    Route::get('/registros/next-pedido', [RegistroOrdenController::class, 'getNextPedido'])->name('registros.next-pedido');
    Route::get('/registros/{pedido}', [RegistroOrdenController::class, 'show'])->name('registros.show');
    Route::post('/registros', [RegistroOrdenController::class, 'store'])->name('registros.store');
    Route::post('/registros/validate-pedido', [RegistroOrdenController::class, 'validatePedido'])->name('registros.validatePedido');
    Route::post('/registros/update-pedido', [RegistroOrdenController::class, 'updatePedido'])->name('registros.updatePedido');
    Route::post('/registros/update-descripcion-prendas', [RegistroOrdenController::class, 'updateDescripcionPrendas'])->name('registros.updateDescripcionPrendas');
    Route::patch('/registros/{pedido}', [RegistroOrdenController::class, 'update'])->name('registros.update');
    Route::delete('/registros/{pedido}', [RegistroOrdenController::class, 'destroy'])->name('registros.destroy');
    Route::post('/registros/update-status', [RegistroOrdenController::class, 'updateStatus'])->name('registros.updateStatus');
    Route::get('/registros/{pedido}/entregas', [RegistroOrdenController::class, 'getEntregas'])->name('registros.entregas');
    Route::get('/api/registros-por-orden/{pedido}', [RegistroOrdenController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden');
    Route::post('/registros/{pedido}/edit-full', [RegistroOrdenController::class, 'editFullOrder'])->name('registros.editFull');
    Route::get('/bodega', [RegistroBodegaController::class, 'index'])->name('bodega.index');
    Route::get('/bodega/next-pedido', [RegistroBodegaController::class, 'getNextPedido'])->name('bodega.next-pedido');
    Route::get('/bodega/{pedido}', [RegistroBodegaController::class, 'show'])->name('bodega.show');
    Route::get('/bodega/{pedido}/entregas', [RegistroBodegaController::class, 'getEntregas'])->name('bodega.entregas');
    Route::get('/api/registros-por-orden-bodega/{pedido}', [RegistroBodegaController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden-bodega');
    Route::post('/bodega/{pedido}/edit-full', [RegistroBodegaController::class, 'editFullOrder'])->name('bodega.editFull');
    Route::post('/bodega', [RegistroBodegaController::class, 'store'])->name('bodega.store');
    Route::post('/bodega/validate-pedido', [RegistroBodegaController::class, 'validatePedido'])->name('bodega.validatePedido');
    Route::post('/bodega/update-pedido', [RegistroBodegaController::class, 'updatePedido'])->name('bodega.updatePedido');
    Route::post('/bodega/update-descripcion-prendas', [RegistroBodegaController::class, 'updateDescripcionPrendas'])->name('bodega.updateDescripcionPrendas');
    Route::patch('/bodega/{pedido}', [RegistroBodegaController::class, 'update'])->name('bodega.update');
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/create-database', [ConfiguracionController::class, 'createDatabase'])->name('configuracion.createDatabase');
    Route::post('/configuracion/select-database', [ConfiguracionController::class, 'selectDatabase'])->name('configuracion.selectDatabase');
    Route::post('/configuracion/migrate-users', [ConfiguracionController::class, 'migrateUsers'])->name('configuracion.migrateUsers');
    Route::post('/configuracion/backup-database', [ConfiguracionController::class, 'backupDatabase'])->name('configuracion.backupDatabase');
    Route::get('/configuracion/download-backup', [ConfiguracionController::class, 'downloadBackup'])->name('configuracion.downloadBackup');
    Route::post('/configuracion/upload-google-drive', [ConfiguracionController::class, 'uploadToGoogleDrive'])->name('configuracion.uploadGoogleDrive');
    Route::get('/tableros', [TablerosController::class, 'index'])->name('tableros.index');
    Route::get('/tableros/fullscreen', [TablerosController::class, 'fullscreen'])->name('tableros.fullscreen');
    Route::get('/tableros/corte-fullscreen', [TablerosController::class, 'corteFullscreen'])->name('tableros.corte-fullscreen');
    Route::post('/tableros', [TablerosController::class, 'store'])->name('tableros.store');
    Route::patch('/tableros/{id}', [TablerosController::class, 'update'])->name('tableros.update');
    Route::delete('/tableros/{id}', [TablerosController::class, 'destroy'])->name('tableros.destroy');
    Route::post('/tableros/{id}/duplicate', [TablerosController::class, 'duplicate'])->name('tableros.duplicate');
    Route::post('/piso-corte', [TablerosController::class, 'storeCorte'])->name('piso-corte.store');
    Route::get('/get-tiempo-ciclo', [TablerosController::class, 'getTiempoCiclo'])->name('get-tiempo-ciclo');
    Route::post('/store-tela', [TablerosController::class, 'storeTela'])->name('store-tela');
    Route::get('/search-telas', [TablerosController::class, 'searchTelas'])->name('search-telas');
    Route::post('/store-maquina', [TablerosController::class, 'storeMaquina'])->name('store-maquina');
    Route::get('/search-maquinas', [TablerosController::class, 'searchMaquinas'])->name('search-maquinas');
    Route::get('/search-operarios', [TablerosController::class, 'searchOperarios'])->name('search-operarios');
    Route::post('/store-operario', [TablerosController::class, 'storeOperario'])->name('store-operario');
    Route::post('/find-or-create-operario', [TablerosController::class, 'findOrCreateOperario'])->name('find-or-create-operario');
    Route::post('/find-or-create-maquina', [TablerosController::class, 'findOrCreateMaquina'])->name('find-or-create-maquina');
    Route::post('/find-or-create-tela', [TablerosController::class, 'findOrCreateTela'])->name('find-or-create-tela');
    Route::post('/find-hora-id', [TablerosController::class, 'findHoraId'])->name('find-hora-id');
    Route::get('/tableros/dashboard-tables-data', [TablerosController::class, 'getDashboardTablesData'])->name('tableros.dashboard-tables-data');
    Route::get('/tableros/get-seguimiento-data', [TablerosController::class, 'getSeguimientoData'])->name('tableros.get-seguimiento-data');
    Route::get('/tableros/corte/dashboard', [TablerosController::class, 'getDashboardCorteData'])->name('tableros.corte.dashboard');
    Route::get('/tableros/unique-values', [TablerosController::class, 'getUniqueValues'])->name('tableros.unique-values');
    Route::get('/vistas', [VistasController::class, 'index'])->name('vistas.index');
    Route::get('/api/vistas/search', [VistasController::class, 'search'])->name('api.vistas.search');
    Route::post('/api/vistas/update-cell', [VistasController::class, 'updateCell'])->name('api.vistas.update-cell');
    Route::get('/vistas/control-calidad', [VistasController::class, 'controlCalidad'])->name('vistas.control-calidad');
    Route::get('/vistas/control-calidad-fullscreen', [VistasController::class, 'controlCalidadFullscreen'])->name('vistas.control-calidad-fullscreen');
    
    // Rutas de Balanceo
    Route::get('/balanceo', [BalanceoController::class, 'index'])->name('balanceo.index');
    Route::get('/balanceo/prenda/create', [BalanceoController::class, 'createPrenda'])->name('balanceo.prenda.create');
    Route::post('/balanceo/prenda', [BalanceoController::class, 'storePrenda'])->name('balanceo.prenda.store');
    Route::get('/balanceo/prenda/{id}/edit', [BalanceoController::class, 'editPrenda'])->name('balanceo.prenda.edit');
    Route::put('/balanceo/prenda/{id}', [BalanceoController::class, 'updatePrenda'])->name('balanceo.prenda.update');
    Route::delete('/balanceo/prenda/{id}', [BalanceoController::class, 'destroyPrenda'])->name('balanceo.prenda.destroy');
    Route::get('/balanceo/prenda/{id}', [BalanceoController::class, 'show'])->name('balanceo.show');
    Route::post('/balanceo/prenda/{prendaId}/balanceo', [BalanceoController::class, 'createBalanceo'])->name('balanceo.create');
    Route::patch('/balanceo/{id}', [BalanceoController::class, 'updateBalanceo'])->name('balanceo.update');
    Route::delete('/balanceo/{id}', [BalanceoController::class, 'destroyBalanceo'])->name('balanceo.destroy');
    Route::post('/balanceo/{balanceoId}/operacion', [BalanceoController::class, 'storeOperacion'])->name('balanceo.operacion.store');
    Route::patch('/balanceo/operacion/{id}', [BalanceoController::class, 'updateOperacion'])->name('balanceo.operacion.update');
    Route::delete('/balanceo/operacion/{id}', [BalanceoController::class, 'destroyOperacion'])->name('balanceo.operacion.destroy');
    Route::get('/balanceo/{id}/data', [BalanceoController::class, 'getBalanceoData'])->name('balanceo.data');
    Route::post('/balanceo/{id}/toggle-estado', [BalanceoController::class, 'toggleEstadoCompleto'])->name('balanceo.toggle-estado');
});

// Rutas de Insumos
Route::middleware(['auth', 'insumos-access'])->prefix('insumos')->name('insumos.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Insumos\InsumosController::class, 'dashboard'])->name('dashboard');
    Route::get('/materiales', [\App\Http\Controllers\Insumos\InsumosController::class, 'materiales'])->name('materiales.index');
    Route::post('/materiales/{pedido}/guardar', [\App\Http\Controllers\Insumos\InsumosController::class, 'guardarMateriales'])->name('materiales.guardar');
    Route::post('/materiales/{pedido}/eliminar', [\App\Http\Controllers\Insumos\InsumosController::class, 'eliminarMaterial'])->name('materiales.eliminar');
    Route::get('/api/materiales/{pedido}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerMateriales'])->name('api.materiales');
    Route::get('/api/filtros/{column}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerValoresFiltro'])->name('api.filtros');
    Route::get('/test', function () {
        return view('insumos.test');
    })->name('test');
});

// Rutas de Asesores (Notificaciones)
Route::middleware('auth')->prefix('asesores')->name('asesores.')->group(function () {
    Route::get('/notifications', [AsesorController::class, 'getNotifications'])->name('notifications');
    Route::post('/notifications/mark-all-read', [AsesorController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
});

require __DIR__.'/auth.php';
