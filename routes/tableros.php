<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TablerosController;
use App\Http\Controllers\TablerosOrdenesController;
use App\Http\Controllers\BalanceoController;
use App\Http\Controllers\VistasController;
use App\Http\Controllers\ConfiguracionController;

// ========================================
// Rutas para tableros_ordenes
// ========================================
Route::get('/tableros_ordenes', function () {
    return view('tableros_ordenes.index');
})->name('tableros-ordenes.index');

Route::get('/tableros_ordenes/api/costureros', [TablerosOrdenesController::class, 'costureros'])->name('tableros-ordenes.api.costureros');
Route::get('/tableros_ordenes/api/recibos/buscar', [TablerosOrdenesController::class, 'buscarRecibos'])->name('tableros-ordenes.api.recibos.buscar');

Route::get('/tableros_ordenes/api/recibos/fijado', [TablerosOrdenesController::class, 'obtenerReciboFijado'])->name('tableros-ordenes.api.recibos.fijado.obtener');
Route::post('/tableros_ordenes/api/recibos/fijar', [TablerosOrdenesController::class, 'fijarRecibo'])->name('tableros-ordenes.api.recibos.fijar');
Route::delete('/tableros_ordenes/api/recibos/fijado', [TablerosOrdenesController::class, 'limpiarReciboFijado'])->name('tableros-ordenes.api.recibos.fijado.limpiar');
Route::get('/tableros_ordenes/api/recibos/por-id', [TablerosOrdenesController::class, 'obtenerReciboPorId'])->name('tableros-ordenes.api.recibos.por-id');

// ========================================
// TABLEROS Y VISTAS - Configuración, visualización y gestión de tableros
// ========================================
Route::middleware(['auth', 'supervisor-readonly'])->group(function () {
    // Gestión de Tableros
    Route::get('/tableros', [TablerosController::class, 'index'])->name('tableros.index');
    Route::get('/tableros/fullscreen', [TablerosController::class, 'fullscreen'])->name('tableros.fullscreen');
    Route::get('/tableros/corte-fullscreen', [TablerosController::class, 'corteFullscreen'])->name('tableros.corte-fullscreen');
    Route::post('/tableros', [TablerosController::class, 'store'])->name('tableros.store');
    Route::patch('/tableros/{id}', [TablerosController::class, 'update'])->name('tableros.update');
    Route::delete('/tableros/{id}', [TablerosController::class, 'destroy'])->name('tableros.destroy');
    Route::post('/tableros/{id}/duplicate', [TablerosController::class, 'duplicate'])->name('tableros.duplicate');
    
    // Piso de Corte
    Route::post('/piso-corte', [TablerosController::class, 'storeCorte'])->name('piso-corte.store');
    Route::get('/get-tiempo-ciclo', [TablerosController::class, 'getTiempoCiclo'])->name('get-tiempo-ciclo');
    
    // Gestión de Telas
    Route::post('/store-tela', [TablerosController::class, 'storeTela'])->name('store-tela');
    Route::get('/search-telas', [TablerosController::class, 'searchTelas'])->name('search-telas');
    
    // Gestión de Máquinas
    Route::post('/store-maquina', [TablerosController::class, 'storeMaquina'])->name('store-maquina');
    Route::get('/search-maquinas', [TablerosController::class, 'searchMaquinas'])->name('search-maquinas');
    
    // Gestión de Operarios
    Route::get('/search-operarios', [TablerosController::class, 'searchOperarios'])->name('search-operarios');
    Route::post('/store-operario', [TablerosController::class, 'storeOperario'])->name('store-operario');
    Route::post('/find-or-create-operario', [TablerosController::class, 'findOrCreateOperario'])->name('find-or-create-operario');
    Route::post('/find-or-create-maquina', [TablerosController::class, 'findOrCreateMaquina'])->name('find-or-create-maquina');
    Route::post('/find-or-create-tela', [TablerosController::class, 'findOrCreateTela'])->name('find-or-create-tela');
    Route::post('/find-hora-id', [TablerosController::class, 'findHoraId'])->name('find-hora-id');
    
    // APIs de Tableros
    Route::get('/tableros/dashboard-tables-data', [TablerosController::class, 'getDashboardTablesData'])->name('tableros.dashboard-tables-data');
    Route::get('/tableros/get-seguimiento-data', [TablerosController::class, 'getSeguimientoData'])->name('tableros.get-seguimiento-data');
    Route::get('/tableros/corte/dashboard', [TablerosController::class, 'getDashboardCorteData'])->name('tableros.corte.dashboard');
    Route::get('/tableros/unique-values', [TablerosController::class, 'getUniqueValues'])->name('tableros.unique-values');
    
    // Vistas
    Route::get('/vistas', [VistasController::class, 'index'])->name('vistas.index');
    Route::get('/api/vistas/search', [VistasController::class, 'search'])->name('api.vistas.search');
    Route::post('/api/vistas/update-cell', [VistasController::class, 'updateCell'])->name('api.vistas.update-cell');
    Route::get('/vistas/control-calidad', [VistasController::class, 'controlCalidad'])->name('vistas.control-calidad');
    Route::get('/vistas/control-calidad-fullscreen', [VistasController::class, 'controlCalidadFullscreen'])->name('vistas.control-calidad-fullscreen');
    
    // ========================================
    // RUTAS DE BALANCEO
    // ========================================
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
    
    // Configuración
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/create-database', [ConfiguracionController::class, 'createDatabase'])->name('configuracion.createDatabase');
    Route::post('/configuracion/select-database', [ConfiguracionController::class, 'selectDatabase'])->name('configuracion.selectDatabase');
    Route::post('/configuracion/migrate-users', [ConfiguracionController::class, 'migrateUsers'])->name('configuracion.migrateUsers');
    Route::post('/configuracion/backup-database', [ConfiguracionController::class, 'backupDatabase'])->name('configuracion.backupDatabase');
    Route::get('/configuracion/download-backup', [ConfiguracionController::class, 'downloadBackup'])->name('configuracion.downloadBackup');
    Route::post('/configuracion/upload-google-drive', [ConfiguracionController::class, 'uploadToGoogleDrive'])->name('configuracion.uploadGoogleDrive');
});
