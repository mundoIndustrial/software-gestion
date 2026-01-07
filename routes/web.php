<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RegistroOrdenController;
use App\Http\Controllers\RegistroOrdenQueryController;
use App\Http\Controllers\RegistroBodegaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\TablerosController;
use App\Http\Controllers\VistasController;
use App\Http\Controllers\BalanceoController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\CotizacionPrendaController;
use App\Infrastructure\Http\Controllers\CotizacionBordadoController;
use App\Http\Controllers\DebugRegistrosController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\StorageController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta de prueba para verificar Echo/Reverb
Route::get('/test-echo', function () {
    return view('test-echo');
})->name('test.echo');

// ========================================
// RUTAS DE STORAGE - Servir imágenes con fallback de extensiones
// ========================================
// Intercepta /storage/cotizaciones/{path} y sirve .webp si .png no existe
Route::get('/storage/cotizaciones/{path}', function ($path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = 'cotizaciones/' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return response($contents, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Content-Disposition', 'inline');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (str_ends_with($fullPath, '.png')) {
        $pathWebp = substr($fullPath, 0, -4) . '.webp';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (str_ends_with($fullPath, '.jpg') || str_ends_with($fullPath, '.jpeg')) {
        $pathWebp = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    abort(404, 'Imagen no encontrada');
})->where('path', '.*')->name('storage.cotizaciones');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'supervisor-access'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // ========================================
    // RUTA PARA REFRESCAR TOKEN CSRF (Prevenir error 419)
    // ========================================
    Route::get('/refresh-csrf', function () {
        return response()->json([
            'token' => csrf_token(),
            'timestamp' => now()->toIso8601String()
        ]);
    })->name('refresh.csrf');
    
    // ========================================
    // RUTAS DE FOTOS (Accesibles para todos los roles autenticados)
    // ========================================
    Route::post('/asesores/fotos/eliminar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'eliminarFotoInmediatamente'])->name('fotos.eliminar-inmediatamente');
    
    // ========================================
    // RUTAS DE NOTIFICACIONES (Accesibles para todos los roles autenticados)
    // ========================================
    // Sistema unificado de notificaciones en tiempo real
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-multiple-read', [App\Http\Controllers\NotificationController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/mark-read-on-open', [App\Http\Controllers\NotificationController::class, 'markAsReadOnOpen'])->name('notifications.mark-read-on-open');
    
    // Contador (mantener compatibilidad)
    Route::post('/contador/notifications/marcar-leidas', [App\Http\Controllers\ContadorController::class, 'markAllNotificationsAsRead'])->name('contador.notifications.mark-all-read');
    Route::get('/contador/notifications', [App\Http\Controllers\ContadorController::class, 'getNotifications'])->name('contador.notifications');
    
    // Asesores (mantener compatibilidad)
    Route::post('/asesores/notifications/mark-all-read', [App\Http\Controllers\AsesoresController::class, 'markAllAsRead'])->name('asesores.notifications.mark-all-read');
    Route::post('/asesores/notifications/{notificationId}/mark-read', [App\Http\Controllers\AsesoresController::class, 'markNotificationAsRead'])->name('asesores.notifications.mark-read');
    Route::get('/asesores/notifications', [App\Http\Controllers\AsesoresController::class, 'getNotifications'])->name('asesores.notifications');
    
    // Supervisor Pedidos (mantener compatibilidad)
    Route::post('/supervisor-pedidos/notifications/mark-all-read', [App\Http\Controllers\SupervisorPedidosController::class, 'markAllNotificationsAsRead'])->name('supervisor-pedidos.notifications.mark-all-read');
    
    // Insumos / Supervisor Planta (mantener compatibilidad)
    Route::post('/insumos/notifications/marcar-leidas', [App\Http\Controllers\Insumos\InsumosController::class, 'markAllNotificationsAsRead'])->name('insumos.notifications.mark-all-read');
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
});

Route::middleware(['auth', 'supervisor-readonly'])->group(function () {
    // Query/Search routes (RegistroOrdenQueryController)
    Route::get('/registros', [RegistroOrdenQueryController::class, 'index'])->name('registros.index');
    
    // CRUD routes (RegistroOrdenController) - Rutas sin parámetros primero
    Route::get('/registros/next-pedido', [RegistroOrdenController::class, 'getNextPedido'])->name('registros.next-pedido');
    Route::get('/registros/filter-options', [RegistroOrdenController::class, 'getFilterOptions'])->name('registros.filter-options');
    Route::get('/registros/filter-column-options/{column}', [RegistroOrdenController::class, 'getColumnFilterOptions'])->name('registros.filter-column-options');
    Route::post('/registros/filter-orders', [RegistroOrdenController::class, 'filterOrders'])->name('registros.filter-orders');
    Route::post('/registros/search', [RegistroOrdenController::class, 'searchOrders'])->name('registros.search');
    
    // Rutas con parámetros {pedido}
    Route::get('/registros/{pedido}', [RegistroOrdenQueryController::class, 'show'])->name('registros.show');
    Route::get('/registros/{pedido}/images', [RegistroOrdenQueryController::class, 'getOrderImages'])->name('registros.images');
    Route::get('/registros/{pedido}/descripcion-prendas', [RegistroOrdenQueryController::class, 'getDescripcionPrendas'])->name('registros.descripcion-prendas');
    Route::get('/api/registros/{numero_pedido}/dias', [RegistroOrdenQueryController::class, 'calcularDiasAPI'])->name('api.registros.dias');
    Route::post('/api/registros/dias-batch', [RegistroOrdenQueryController::class, 'calcularDiasBatchAPI'])->name('api.registros.dias-batch');
    Route::post('/api/registros/{id}/calcular-fecha-estimada', [RegistroOrdenQueryController::class, 'calcularFechaEstimada'])->name('api.registros.calcular-fecha-estimada');
    
    // ✅ Ruta para traer LogoPedido por ID
    Route::get('/api/logo-pedidos/{id}', [RegistroOrdenQueryController::class, 'showLogoPedidoById'])->name('api.logo-pedidos.show');
    
    Route::post('/registros', [RegistroOrdenController::class, 'store'])->name('registros.store');
    Route::post('/registros/validate-pedido', [RegistroOrdenController::class, 'validatePedido'])->name('registros.validatePedido');
    Route::post('/registros/update-pedido', [RegistroOrdenController::class, 'updatePedido'])->name('registros.updatePedido');
    Route::post('/registros/update-descripcion-prendas', [RegistroOrdenController::class, 'updateDescripcionPrendas'])->name('registros.updateDescripcionPrendas');
    Route::patch('/registros/{pedido}', [RegistroOrdenController::class, 'update'])->name('registros.update');
    Route::delete('/registros/{pedido}', [RegistroOrdenController::class, 'destroy'])->name('registros.destroy');
    Route::post('/registros/update-status', [RegistroOrdenController::class, 'updateStatus'])->name('registros.updateStatus');
    Route::get('/registros/{pedido}/entregas', [RegistroOrdenController::class, 'getEntregas'])->name('registros.entregas');
    Route::get('/api/registros-por-orden/{pedido}', [RegistroOrdenController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden');
    Route::get('/api/tabla-original/{numeroPedido}/procesos', [RegistroOrdenController::class, 'getProcesosTablaOriginal'])->name('api.tabla-original.procesos');
    Route::post('/registros/{pedido}/edit-full', [RegistroOrdenController::class, 'editFullOrder'])->name('registros.editFull');
    Route::get('/orders/{numero_pedido}', [RegistroOrdenController::class, 'show'])->name('orders.show');

    Route::get('/api/bodega/{numero_pedido}/dias', [RegistroBodegaController::class, 'calcularDiasAPI'])->name('api.bodega.dias');
    Route::get('/api/ordenes/{id}/procesos', [App\Http\Controllers\OrdenController::class, 'getProcesos'])->name('api.ordenes.procesos');
    Route::post('/api/ordenes/{numero_pedido}/novedades', [RegistroOrdenController::class, 'updateNovedades'])->name('api.ordenes.novedades');
    Route::post('/api/ordenes/{numero_pedido}/novedades/add', [RegistroOrdenController::class, 'addNovedad'])->name('api.ordenes.novedades.add');
    Route::post('/api/bodega/{pedido}/novedades', [RegistroBodegaController::class, 'updateNovedadesBodega'])->name('api.bodega.novedades');
    Route::post('/api/bodega/{pedido}/novedades/add', [RegistroBodegaController::class, 'addNovedadBodega'])->name('api.bodega.novedades.add');
    Route::put('/api/procesos/{id}/editar', [App\Http\Controllers\OrdenController::class, 'editarProceso'])->name('api.procesos.editar');
    Route::delete('/api/procesos/{id}/eliminar', [App\Http\Controllers\OrdenController::class, 'eliminarProceso'])->name('api.procesos.eliminar');
    Route::post('/api/procesos/buscar', [App\Http\Controllers\OrdenController::class, 'buscarProceso'])->name('api.procesos.buscar');
    Route::get('/api/tabla-original-bodega/{numeroPedido}/procesos', [RegistroBodegaController::class, 'getProcesosTablaOriginal'])->name('api.tabla-original-bodega.procesos');
    Route::get('/bodega', [RegistroBodegaController::class, 'index'])->name('bodega.index');
    Route::post('/bodega/search', [RegistroBodegaController::class, 'searchOrders'])->name('bodega.search');
    Route::get('/bodega/next-pedido', [RegistroBodegaController::class, 'getNextPedido'])->name('bodega.next-pedido');
    Route::get('/bodega/{pedido}', [RegistroBodegaController::class, 'show'])->name('bodega.show');
    Route::get('/bodega/{pedido}/prendas', [RegistroBodegaController::class, 'getPrendas'])->name('bodega.prendas');
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

// ========================================
// RUTAS PARA COTIZACIONES - PRENDA (DDD REFACTORIZADO)
// ========================================
Route::middleware(['auth', 'role:asesor'])->group(function () {
    // Cotizaciones tipo PRENDA
    Route::get('/cotizaciones-prenda/crear', [CotizacionPrendaController::class, 'create'])->name('cotizaciones-prenda.create');
    Route::post('/cotizaciones-prenda', [CotizacionPrendaController::class, 'store'])->name('cotizaciones-prenda.store');
    Route::get('/cotizaciones-prenda', [CotizacionPrendaController::class, 'lista'])->name('cotizaciones-prenda.lista');
    Route::get('/cotizaciones-prenda/{cotizacion}/editar', [CotizacionPrendaController::class, 'edit'])->name('cotizaciones-prenda.edit');
    Route::put('/cotizaciones-prenda/{cotizacion}', [CotizacionPrendaController::class, 'update'])->name('cotizaciones-prenda.update');
    Route::post('/cotizaciones-prenda/{cotizacion}/enviar', [CotizacionPrendaController::class, 'enviar'])->name('cotizaciones-prenda.enviar');
    Route::delete('/cotizaciones-prenda/{cotizacion}', [CotizacionPrendaController::class, 'destroy'])->name('cotizaciones-prenda.destroy');
    
    // Rutas para borrar imágenes de cotizaciones (Prenda)
    Route::post('/cotizaciones/{id}/borrar-imagen-prenda', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'borrarImagenPrenda'])->name('cotizaciones.borrar-imagen-prenda');
    Route::post('/cotizaciones/{id}/borrar-imagen-tela', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'borrarImagenTela'])->name('cotizaciones.borrar-imagen-tela');
});

// ========================================
// RUTAS PARA COTIZACIONES - BORDADO (DDD REFACTORIZADO)
// ========================================
Route::middleware(['auth', 'role:asesor'])->group(function () {
    // Cotizaciones tipo BORDADO/LOGO
    Route::get('/cotizaciones-bordado/crear', [CotizacionBordadoController::class, 'create'])->name('cotizaciones-bordado.create');
    Route::post('/cotizaciones-bordado', [CotizacionBordadoController::class, 'store'])->name('cotizaciones-bordado.store');
    Route::put('/cotizaciones-bordado/{id}/borrador', [CotizacionBordadoController::class, 'updateBorrador'])->name('cotizaciones-bordado.update-borrador');
    Route::post('/cotizaciones-bordado/{id}/borrar-imagen', [CotizacionBordadoController::class, 'borrarImagen'])->name('cotizaciones-bordado.borrar-imagen');
    Route::get('/cotizaciones-bordado', [CotizacionBordadoController::class, 'lista'])->name('cotizaciones-bordado.lista');
    Route::get('/cotizaciones-bordado/{cotizacion}/editar', [CotizacionBordadoController::class, 'edit'])->name('cotizaciones-bordado.edit');
    Route::put('/cotizaciones-bordado/{cotizacion}', [CotizacionBordadoController::class, 'update'])->name('cotizaciones-bordado.update');
    Route::post('/cotizaciones-bordado/{cotizacion}/enviar', [CotizacionBordadoController::class, 'enviar'])->name('cotizaciones-bordado.enviar');
    Route::delete('/cotizaciones-bordado/{cotizacion}', [CotizacionBordadoController::class, 'destroy'])->name('cotizaciones-bordado.destroy');
});

// ========================================
// RUTAS PARA SUPERVISOR-ADMIN (COTIZACIONES)
// ========================================
// NOTA: Funcionalidad migrada a CotizacionPrendaController y CotizacionBordadoController (DDD)
// Las rutas anteriores de CotizacionesViewController han sido eliminadas
// Usar: CotizacionPrendaController::lista() o CotizacionBordadoController::lista()

// ========================================
// RUTAS PARA APROBADOR DE COTIZACIONES
// ========================================
Route::middleware(['auth'])->group(function () {
    // Solo usuarios con rol aprobador_cotizaciones pueden ver cotizaciones pendientes
    Route::get('/cotizaciones/pendientes', function () {
        // Verificar que el usuario tenga el rol aprobador_cotizaciones
        if (!auth()->user()->hasRole('aprobador_cotizaciones')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
        
        // Obtener cotizaciones pendientes de aprobación (estado APROBADA_CONTADOR)
        $cotizaciones = \App\Models\Cotizacion::where('estado', 'APROBADA_CONTADOR')
            ->with(['aprobaciones.usuario'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Obtener total de aprobadores
        $rolAprobador = \App\Models\Role::where('name', 'aprobador_cotizaciones')->first();
        $totalAprobadores = $rolAprobador 
            ? \App\Models\User::whereJsonContains('roles_ids', $rolAprobador->id)->count()
            : 0;
        
        return view('cotizaciones.pendientes', compact('cotizaciones', 'totalAprobadores'));
    })->name('cotizaciones.pendientes');

    // Obtener datos de cotización para modal (AJAX)
    Route::get('/cotizaciones/{cotizacion}/datos', [CotizacionesViewController::class, 'getDatosForModal'])
        ->name('cotizaciones.obtener-datos');

    // Obtener costos de cotización (AJAX)
    Route::get('/cotizaciones/{cotizacion}/costos', [App\Http\Controllers\ContadorController::class, 'obtenerCostos'])
        ->name('cotizaciones.obtener-costos');

    // Obtener contador de cotizaciones pendientes para aprobador (AJAX)
    Route::get('/pendientes-count', [CotizacionesViewController::class, 'cotizacionesPendientesAprobadorCount'])
        ->name('cotizaciones.pendientes-count');

    // Endpoint para obtener contador de cotizaciones pendientes
    // NOTA: Funcionalidad migrada a Handlers DDD
});

// ========================================
// RUTA DE PDF COMPARTIDA (Accesible para asesores, contador, visualizador_cotizaciones_logo y admin)
// ========================================
Route::middleware(['auth'])->group(function () {
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
});

// ========================================
// RUTAS PARA CONTADOR (MÓDULO INDEPENDIENTE)
// ========================================
// Admin puede acceder a contador además del rol contador
Route::middleware(['auth', 'role:contador,admin'])->prefix('contador')->name('contador.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\ContadorController::class, 'index'])->name('index');
    Route::get('/todas', [App\Http\Controllers\ContadorController::class, 'todas'])->name('todas');
    Route::get('/por-revisar', [App\Http\Controllers\ContadorController::class, 'porRevisar'])->name('por-revisar');
    Route::get('/aprobadas', [App\Http\Controllers\ContadorController::class, 'aprobadas'])->name('aprobadas');
    Route::get('/cotizacion/{id}', [App\Http\Controllers\ContadorController::class, 'getCotizacionDetail'])->name('cotizacion.detail');
    Route::delete('/cotizacion/{id}', [App\Http\Controllers\ContadorController::class, 'deleteCotizacion'])->name('cotizacion.delete');
    // NOTA: Funcionalidad migrada a Handlers DDD
    // Route::get('/por-corregir', [App\Http\Controllers\CotizacionesViewController::class, 'porCorregir'])->name('por-corregir');
    
    // Rutas para costos de prendas
    Route::post('/costos/guardar', [App\Http\Controllers\CostoPrendaController::class, 'guardar'])->name('costos.guardar');
    Route::get('/costos/obtener/{cotizacion_id}', [App\Http\Controllers\CostoPrendaController::class, 'obtener'])->name('costos.obtener');
    
    // Rutas para notas de tallas
    Route::post('/prenda/{prendaId}/notas-tallas', [App\Http\Controllers\ContadorController::class, 'guardarNotasTallas'])->name('prenda.guardar-notas-tallas');
    
    // Ruta para texto personalizado de tallas (módulo contador)
    Route::post('/prenda/{prendaId}/texto-personalizado-tallas', [App\Http\Controllers\ContadorController::class, 'guardarTextoPersonalizadoTallas'])->name('prenda.guardar-texto-personalizado-tallas');
    
    // Rutas para PDF
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
    
    // Ruta para cambiar estado de cotización
    Route::patch('/cotizacion/{id}/estado', [App\Http\Controllers\ContadorController::class, 'cambiarEstado'])->name('cotizacion.cambiar-estado');
    
    // Ruta para obtener costos de prendas
    Route::get('/cotizacion/{id}/costos', [App\Http\Controllers\ContadorController::class, 'obtenerCostos'])->name('cotizacion.costos');
    
    // Ruta para obtener contador de cotizaciones pendientes
    Route::get('/cotizaciones-pendientes-count', [App\Http\Controllers\ContadorController::class, 'cotizacionesPendientesCount'])->name('cotizaciones-pendientes-count');
    
    // Ruta para perfil del contador
    Route::get('/perfil', [App\Http\Controllers\ContadorController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [App\Http\Controllers\ContadorController::class, 'updateProfile'])->name('profile.update');
});

// ========================================
// RUTAS PARA OPERARIOS (CORTADOR Y COSTURERO)
// ========================================
Route::middleware(['auth', 'operario-access'])->prefix('operario')->name('operario.')->group(function () {
    Route::get('/dashboard', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'dashboard'])->name('dashboard');
    Route::get('/mis-pedidos', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'misPedidos'])->name('mis-pedidos');
    Route::get('/pedido/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'verPedido'])->name('ver-pedido');
    Route::get('/api/pedidos', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'obtenerPedidosJson'])->name('api.pedidos');
    Route::get('/api/pedido/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'obtenerPedidoJson'])->name('api.pedido');
    Route::get('/api/novedades/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'obtenerNovedades'])->name('api.novedades');
    Route::post('/buscar', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'buscarPedido'])->name('buscar');
    Route::post('/reportar-pendiente', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'reportarPendiente'])->name('reportar-pendiente');
    Route::post('/api/completar-proceso/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'completarProceso'])->name('api.completar-proceso');
    Route::get('/debug', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'debug'])->name('debug');
});

// ========================================
// RUTAS PARA ASESORES (MÓDULO INDEPENDIENTE)
// ========================================
// Admin puede acceder a asesores además del rol asesor
Route::middleware(['auth', 'role:asesor,admin'])->prefix('asesores')->name('asesores.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\AsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [App\Http\Controllers\AsesoresController::class, 'getDashboardData'])->name('dashboard-data');
    
    // Perfil
    Route::get('/perfil', [App\Http\Controllers\AsesoresController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [App\Http\Controllers\AsesoresController::class, 'updateProfile'])->name('profile.update');
    
    // Pedidos (usando tabla_original)
    Route::get('/pedidos', [App\Http\Controllers\AsesoresController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/create', [App\Http\Controllers\AsesoresController::class, 'create'])->name('pedidos.create');
    Route::get('/pedidos/next-pedido', [App\Http\Controllers\AsesoresController::class, 'getNextPedido'])->name('next-pedido');
    Route::post('/pedidos', [App\Http\Controllers\AsesoresController::class, 'store'])->name('pedidos.store');
    Route::post('/pedidos/confirm', [App\Http\Controllers\AsesoresController::class, 'confirm'])->name('pedidos.confirm');
    Route::post('/pedidos/{id}/anular', [App\Http\Controllers\AsesoresController::class, 'anularPedido'])->name('pedidos.anular');
    Route::get('/pedidos/{pedido}', [App\Http\Controllers\AsesoresController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{pedido}/edit', [App\Http\Controllers\AsesoresController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [App\Http\Controllers\AsesoresController::class, 'update'])->name('pedidos.update');
    Route::delete('/pedidos/{pedido}', [App\Http\Controllers\AsesoresController::class, 'destroy'])->name('pedidos.destroy');
    
    // ========================================
    // SISTEMA DE ÓRDENES CON BORRADORES
    // ========================================
    
    // BORRADORES - Gestión de borradores
    Route::get('/borradores', [App\Http\Controllers\OrdenController::class, 'borradores'])->name('borradores.index');
    
    // ÓRDENES - CRUD principal
    Route::get('/ordenes/create', [App\Http\Controllers\OrdenController::class, 'create'])->name('ordenes.create');
    Route::post('/ordenes/guardar', [App\Http\Controllers\OrdenController::class, 'guardarBorrador'])->name('ordenes.store.draft');
    Route::get('/ordenes/{id}/edit', [App\Http\Controllers\OrdenController::class, 'edit'])->name('ordenes.edit');
    Route::patch('/ordenes/{id}', [App\Http\Controllers\OrdenController::class, 'update'])->name('ordenes.update');
    Route::post('/ordenes/{id}/confirmar', [App\Http\Controllers\OrdenController::class, 'confirmar'])->name('ordenes.confirm');
    Route::delete('/ordenes/{id}', [App\Http\Controllers\OrdenController::class, 'destroy'])->name('ordenes.destroy');
    Route::get('/ordenes', [App\Http\Controllers\OrdenController::class, 'index'])->name('ordenes.index');
    Route::get('/ordenes/{id}', [App\Http\Controllers\OrdenController::class, 'show'])->name('ordenes.show');
    
    // Estadísticas de órdenes
    Route::get('/ordenes/stats', [App\Http\Controllers\OrdenController::class, 'stats'])->name('ordenes.stats');
    

    // ========================================
    // COTIZACIONES - Gestión de cotizaciones y borradores (DDD Refactorizado)
    // ========================================
    // Vista HTML de cotizaciones (usando Infrastructure Controller con Handlers DDD)
    Route::get('/cotizaciones', [App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/filtros/valores', [App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController::class, 'valores'])->name('cotizaciones.filtros.valores');
    
    // API endpoints para cotizaciones
    Route::post('/cotizaciones', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::put('/cotizaciones/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'update'])->name('cotizaciones.update');
    
    // Ruta para generar PDF de cotizaciones (prenda o logo)
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
    
    // Rutas para eliminar imágenes de borradores (ANTES de rutas dinámicas)
    Route::delete('/cotizaciones/imagenes/prenda/{id}', [App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController::class, 'borrarPrenda'])->name('cotizaciones.imagen.borrar-prenda');
    Route::delete('/cotizaciones/imagenes/tela/{id}', [App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController::class, 'borrarTela'])->name('cotizaciones.imagen.borrar-tela');
    Route::delete('/cotizaciones/imagenes/logo/{id}', [App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController::class, 'borrarLogo'])->name('cotizaciones.imagen.borrar-logo');
    
    Route::get('/cotizaciones/{id}/ver', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'showView'])->name('cotizaciones.show');
    Route::get('/cotizaciones/{id}/editar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'getForEdit'])->name('cotizaciones.get-for-edit');
    Route::get('/cotizaciones/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'show'])->name('cotizaciones.api');
    Route::post('/cotizaciones/{id}/imagenes', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'subirImagen'])->name('cotizaciones.subir-imagen');
    
    // Rutas antiguas (compatibilidad con frontend) - Aliases al nuevo controller
    Route::post('/cotizaciones/guardar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'store'])->name('cotizaciones.guardar');
    Route::get('/cotizaciones/reflectivo/{id}/editar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'getReflectivoForEdit'])->name('cotizaciones.reflectivo.edit');
    Route::get('/cotizaciones/{id}/editar-borrador', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'editBorrador'])->name('cotizaciones.edit-borrador');
    Route::delete('/cotizaciones/{id}/borrador', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'destroyBorrador'])->name('cotizaciones.destroy-borrador');
    Route::post('/cotizaciones/{id}/anular', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'anularCotizacion'])->name('cotizaciones.anular');
    Route::delete('/cotizaciones/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
    
    // ========================================
    // PEDIDOS DE PRODUCCIÓN - Gestión de pedidos desde cotizaciones
    // ========================================
    Route::get('/pedidos-produccion/crear', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'crearFormEditable'])->name('pedidos-produccion.crear');
    Route::get('/pedidos-produccion/obtener-datos-cotizacion/{cotizacion_id}', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerDatosCotizacion'])->name('pedidos-produccion.obtener-datos-cotizacion');
    Route::get('/pedidos-produccion', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'index'])->name('pedidos-produccion.index');
    Route::get('/pedidos-produccion/{id}', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'show'])->name('pedidos-produccion.show');
    Route::get('/pedidos-produccion/{id}/plantilla', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'plantilla'])->name('pedidos-produccion.plantilla');
    Route::post('/pedidos-produccion/crear-desde-cotizacion/{cotizacionId}', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'crearDesdeCotizacion'])->name('pedidos-produccion.crear-desde-cotizacion');
    Route::post('/pedidos-produccion/crear-sin-cotizacion', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'crearSinCotizacion'])->name('pedidos-produccion.crear-sin-cotizacion');
    Route::post('/pedidos-produccion/crear-prenda-sin-cotizacion', [App\Http\Controllers\Asesores\PedidosProduccionController::class, 'crearPrendaSinCotizacion'])->name('pedidos-produccion.crear-prenda-sin-cotizacion');
    
    // Incluir rutas del módulo de pedidos refactorizado
    require __DIR__ . '/asesores/pedidos.php';
    
    // ========================================
    // CLIENTES - Gestión de clientes
    // ========================================
    Route::get('/clientes', [App\Http\Controllers\Asesores\ClientesController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [App\Http\Controllers\Asesores\ClientesController::class, 'store'])->name('clientes.store');
    Route::patch('/clientes/{id}', [App\Http\Controllers\Asesores\ClientesController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{id}', [App\Http\Controllers\Asesores\ClientesController::class, 'destroy'])->name('clientes.destroy');
    
    // ========================================
    // REPORTES - Gestión de reportes
    // ========================================
    Route::get('/reportes', [App\Http\Controllers\Asesores\ReportesController::class, 'index'])->name('reportes.index');
    Route::post('/reportes', [App\Http\Controllers\Asesores\ReportesController::class, 'store'])->name('reportes.store');
    Route::patch('/reportes/{id}', [App\Http\Controllers\Asesores\ReportesController::class, 'update'])->name('reportes.update');
    Route::delete('/reportes/{id}', [App\Http\Controllers\Asesores\ReportesController::class, 'destroy'])->name('reportes.destroy');
    
    // Agregar Prendas (Sistema de Variantes)
    Route::get('/prendas/agregar', function () {
        return view('asesores.prendas.agregar-prendas');
    })->name('prendas.agregar');

    // ========================================
    // COTIZACIONES - Rutas protegidas (dentro del grupo asesores)
    // ========================================
    Route::post('/cotizaciones/reflectivo/guardar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'storeReflectivo'])->name('cotizaciones.reflectivo.guardar');
    Route::delete('/cotizaciones/{id}/borrador', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'destroyBorrador'])->name('cotizaciones.destroy-borrador');
    Route::put('/cotizaciones/reflectivo/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'updateReflectivo'])->name('cotizaciones.reflectivo.update');

    // ========================================
    // COTIZACIONES DE PRENDA - Solo Asesor
    // ========================================
    Route::get('/cotizaciones/prenda/crear', [CotizacionPrendaController::class, 'create'])->name('cotizaciones-prenda.create');
    Route::post('/cotizaciones/prenda', [CotizacionPrendaController::class, 'store'])->name('cotizaciones-prenda.store');
    Route::get('/cotizaciones/prenda/lista', [CotizacionPrendaController::class, 'lista'])->name('cotizaciones-prenda.lista');
    Route::get('/cotizaciones/prenda/{cotizacion}/editar', [CotizacionPrendaController::class, 'edit'])->name('cotizaciones-prenda.edit');
    Route::put('/cotizaciones/prenda/{cotizacion}', [CotizacionPrendaController::class, 'update'])->name('cotizaciones-prenda.update');
    Route::post('/cotizaciones/prenda/{cotizacion}/enviar', [CotizacionPrendaController::class, 'enviar'])->name('cotizaciones-prenda.enviar');
    Route::delete('/cotizaciones/prenda/{cotizacion}', [CotizacionPrendaController::class, 'destroy'])->name('cotizaciones-prenda.destroy');

    // ========================================
    // COTIZACIONES DE BORDADO - Solo Asesor
    // ========================================
    Route::get('/cotizaciones/bordado/crear', [CotizacionBordadoController::class, 'create'])->name('cotizaciones-bordado.create');
    Route::post('/cotizaciones/bordado', [CotizacionBordadoController::class, 'store'])->name('cotizaciones-bordado.store');
    Route::get('/cotizaciones/bordado/lista', [CotizacionBordadoController::class, 'lista'])->name('cotizaciones-bordado.lista');
    Route::get('/cotizaciones/bordado/{cotizacion}/editar', [CotizacionBordadoController::class, 'edit'])->name('cotizaciones-bordado.edit');
    Route::put('/cotizaciones/bordado/{cotizacion}', [CotizacionBordadoController::class, 'update'])->name('cotizaciones-bordado.update');
    Route::post('/cotizaciones/bordado/{cotizacion}/enviar', [CotizacionBordadoController::class, 'enviar'])->name('cotizaciones-bordado.enviar');
    Route::delete('/cotizaciones/bordado/{cotizacion}', [CotizacionBordadoController::class, 'destroy'])->name('cotizaciones-bordado.destroy');
});

// ========================================
// API ROUTES - LOGO COTIZACIÓN TÉCNICAS (DDD) - Fuera del grupo de asesores
// ========================================
Route::middleware(['auth', 'role:asesor,admin'])->prefix('api/logo-cotizacion-tecnicas')->name('api.logo-cotizacion-tecnicas.')->group(function () {
    Route::get('tipos-disponibles', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'tiposDisponibles'])->name('tipos');
    Route::post('agregar', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'agregarTecnica'])->name('agregar');
    Route::get('cotizacion/{logoCotizacionId}', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'obtenerTecnicas'])->name('obtener');
    Route::delete('{tecnicaId}', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'eliminarTecnica'])->name('eliminar');
    Route::patch('{tecnicaId}/observaciones', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'actualizarObservaciones'])->name('actualizar-observaciones');
    Route::get('prendas', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'obtenerPrendas'])->name('prendas');
    Route::post('prendas', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'guardarPrenda'])->name('guardar-prenda');
});

// ========================================
// RUTAS PARA SUPERVISOR DE ASESORES
// ========================================
Route::middleware(['auth', 'role:supervisor_asesores,admin'])->prefix('supervisor-asesores')->name('supervisor-asesores.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\SupervisorAsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-stats', [App\Http\Controllers\SupervisorAsesoresController::class, 'dashboardStats'])->name('dashboard-stats');
    
    // Cotizaciones
    Route::get('/cotizaciones', [App\Http\Controllers\SupervisorAsesoresController::class, 'cotizacionesIndex'])->name('cotizaciones.index');
    Route::get('/cotizaciones/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'cotizacionesData'])->name('cotizaciones.data');
    Route::get('/cotizaciones/filtros/valores', [App\Http\Controllers\SupervisorAsesoresController::class, 'cotizacionesFiltrosValores'])->name('cotizaciones.filtros.valores');
    
    // Pedidos
    Route::get('/pedidos', [App\Http\Controllers\SupervisorAsesoresController::class, 'pedidosIndex'])->name('pedidos.index');
    Route::get('/pedidos/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'pedidosData'])->name('pedidos.data');
    
    // Asesores
    Route::get('/asesores', [App\Http\Controllers\SupervisorAsesoresController::class, 'asesoresIndex'])->name('asesores.index');
    Route::get('/asesores/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'asesoresData'])->name('asesores.data');
    Route::get('/asesores/{id}', [App\Http\Controllers\SupervisorAsesoresController::class, 'asesoresShow'])->name('asesores.show');
    
    // Reportes
    Route::get('/reportes', [App\Http\Controllers\SupervisorAsesoresController::class, 'reportesIndex'])->name('reportes.index');
    Route::get('/reportes/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'reportesData'])->name('reportes.data');
    
    // Perfil
    Route::get('/perfil', [App\Http\Controllers\SupervisorAsesoresController::class, 'profileIndex'])->name('profile.index');
    Route::get('/perfil/stats', [App\Http\Controllers\SupervisorAsesoresController::class, 'profileStats'])->name('profile.stats');
    Route::post('/perfil/password-update', [App\Http\Controllers\SupervisorAsesoresController::class, 'profilePasswordUpdate'])->name('profile.password-update');
});

// ========================================
// RUTAS PARA VISUALIZADOR DE COTIZACIONES LOGO
// ========================================
Route::middleware(['auth', 'role:visualizador_cotizaciones_logo,admin'])->prefix('visualizador-logo')->name('visualizador-logo.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\VisualizadorLogoController::class, 'dashboard'])->name('dashboard');
    
    // Cotizaciones
    Route::get('/cotizaciones', [App\Http\Controllers\VisualizadorLogoController::class, 'getCotizaciones'])->name('cotizaciones');
    Route::get('/cotizaciones/{id}', [App\Http\Controllers\VisualizadorLogoController::class, 'verCotizacion'])->name('cotizaciones.ver');
    
    // Estadísticas
    Route::get('/estadisticas', [App\Http\Controllers\VisualizadorLogoController::class, 'getEstadisticas'])->name('estadisticas');
    
    // PDF de Logo - Solo puede ver PDFs de logo
    Route::get('/cotizaciones/{id}/pdf-logo', function($id) {
        return redirect()->route('pdf.cotizacion', ['cotizacionId' => $id, 'tipo' => 'logo']);
    })->name('cotizaciones.pdf-logo');
});

// ========== DEBUG ROUTES PARA OPTIMIZACIÓN DE /registros ==========
// Solo accesible en desarrollo o para admins
Route::middleware(['auth', 'role:admin'])->prefix('debug')->name('debug.')->group(function () {
    Route::get('/registros/performance', [DebugRegistrosController::class, 'debugPerformance'])->name('registros-performance');
    Route::get('/registros/queries', [DebugRegistrosController::class, 'listAllQueries'])->name('registros-queries');
    Route::get('/registros/table-analysis', [DebugRegistrosController::class, 'analyzeTable'])->name('registros-table-analysis');
    Route::get('/registros/suggest-indices', [DebugRegistrosController::class, 'suggestIndices'])->name('registros-suggest-indices');
});

// ========================================
// RUTAS GENERALES - Inventario de Telas (Compartido)
// ========================================
Route::middleware(['auth'])->prefix('inventario-telas')->name('inventario-telas.')->group(function () {
    Route::get('/', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'index'])->name('index');
    Route::post('/store', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'store'])->name('store');
    Route::post('/ajustar-stock', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'ajustarStock'])->name('ajustar-stock');
    Route::delete('/{id}', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'destroy'])->name('destroy');
    Route::get('/historial', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'historial'])->name('historial');
});

// API Routes para Prendas (Reconocimiento)
Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/tipos-prenda', [App\Http\Controllers\API\PrendaController::class, 'tiposPrenda'])->name('tipos-prenda');
    Route::post('/prenda/reconocer', [App\Http\Controllers\API\PrendaController::class, 'reconocer'])->name('prenda.reconocer');
});

// Rutas de Insumos
Route::middleware(['auth', 'insumos-access'])->prefix('insumos')->name('insumos.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Insumos\InsumosController::class, 'dashboard'])->name('dashboard');
    Route::get('/materiales', [\App\Http\Controllers\Insumos\InsumosController::class, 'materiales'])->name('materiales.index');
    Route::post('/materiales/{pedido}/guardar', [\App\Http\Controllers\Insumos\InsumosController::class, 'guardarMateriales'])->name('materiales.guardar');
    Route::post('/materiales/{pedido}/eliminar', [\App\Http\Controllers\Insumos\InsumosController::class, 'eliminarMaterial'])->name('materiales.eliminar');
    Route::get('/api/materiales/{pedido}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerMateriales'])->name('api.materiales');
    Route::get('/api/filtros/{column}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerValoresFiltro'])->name('api.filtros');
    Route::post('/materiales/{numeroPedido}/cambiar-estado', [\App\Http\Controllers\Insumos\MaterialesController::class, 'cambiarEstado'])->name('materiales.cambiar-estado');
    Route::get('/test', function () {
        return view('insumos.test');
    })->name('test');
    
    // Cálculo de Metrajes
    Route::get('/metrajes', function () {
        return view('insumos.metrajes.index');
    })->name('metrajes.index');
});

// ========================================
// RUTAS PARA SUPERVISOR DE PEDIDOS
// ========================================
Route::middleware(['auth', 'role:supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Listar órdenes
    Route::get('/', [App\Http\Controllers\SupervisorPedidosController::class, 'index'])->name('index');
    
    // Perfil del supervisor
    Route::get('/perfil/editar', [App\Http\Controllers\SupervisorPedidosController::class, 'profile'])->name('profile');
    Route::post('/perfil/actualizar', [App\Http\Controllers\SupervisorPedidosController::class, 'updateProfile'])->name('update-profile');
    
    // Notificaciones
    Route::get('/notificaciones', [App\Http\Controllers\SupervisorPedidosController::class, 'getNotifications'])->name('notifications');
    Route::post('/notificaciones/marcar-todas-leidas', [App\Http\Controllers\SupervisorPedidosController::class, 'markAllNotificationsAsRead'])->name('mark-all-read');
    Route::post('/notificaciones/{notificationId}/marcar-leida', [App\Http\Controllers\SupervisorPedidosController::class, 'markNotificationAsRead'])->name('mark-read');
    
    // Obtener opciones de filtro (debe ir antes de /{id})
    Route::get('/filtro-opciones/{campo}', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerOpcionesFiltro'])->name('filtro-opciones');
    
    // Ruta para obtener contador de órdenes pendientes de aprobación (DEBE IR ANTES DE /{id})
    Route::get('/ordenes-pendientes-count', [App\Http\Controllers\SupervisorPedidosController::class, 'ordenesPendientesCount'])->name('ordenes-pendientes-count');
    
    // Ver detalle de orden
    Route::get('/{id}', [App\Http\Controllers\SupervisorPedidosController::class, 'show'])->name('show');
    
    // Descargar PDF
    Route::get('/{id}/pdf', [App\Http\Controllers\SupervisorPedidosController::class, 'descargarPDF'])->name('pdf');
    
    // Anular orden
    Route::post('/{id}/anular', [App\Http\Controllers\SupervisorPedidosController::class, 'anular'])->name('anular');
    
    // Aprobar orden (enviar a producción)
    Route::post('/{id}/aprobar', [App\Http\Controllers\SupervisorPedidosController::class, 'aprobarOrden'])->name('aprobar');
    
    // Cambiar estado
    Route::patch('/{id}/estado', [App\Http\Controllers\SupervisorPedidosController::class, 'cambiarEstado'])->name('cambiar-estado');
    
    // Obtener datos en JSON
    Route::get('/{id}/datos', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerDatos'])->name('datos');
    
    // Obtener datos para comparación (pedido vs cotización)
    Route::get('/{id}/comparar', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerDatosComparacion'])->name('comparar');
    
    // Editar pedido
    Route::get('/{id}/editar', [App\Http\Controllers\SupervisorPedidosController::class, 'edit'])->name('editar');
    
    // Actualizar pedido
    Route::put('/{id}/actualizar', [App\Http\Controllers\SupervisorPedidosController::class, 'update'])->name('actualizar');
    Route::post('/{id}/actualizar', [App\Http\Controllers\SupervisorPedidosController::class, 'update'])->name('actualizar.post');
    
    // Eliminar imagen de prenda
    Route::delete('/imagen/{tipo}/{id}', [App\Http\Controllers\SupervisorPedidosController::class, 'deleteImage'])->name('imagen.eliminar');
});

// ========================================
// RUTAS API PÚBLICAS PARA FESTIVOS
// ========================================
Route::prefix('api')->name('api.')->group(function () {
    // Rutas públicas para festivos (sin autenticación requerida)
    Route::get('/festivos', [App\Http\Controllers\Api\FestivosController::class, 'index'])->name('festivos.index');
    Route::get('/festivos/detailed', [App\Http\Controllers\Api\FestivosController::class, 'detailed'])->name('festivos.detailed');
    Route::get('/festivos/check', [App\Http\Controllers\Api\FestivosController::class, 'check'])->name('festivos.check');
    Route::get('/festivos/range', [App\Http\Controllers\Api\FestivosController::class, 'range'])->name('festivos.range');
});

// ========================================
// RUTAS PARA ESTADOS DE COTIZACIONES
// ========================================
Route::middleware(['auth', 'verified'])->name('cotizaciones.estado.')->group(function () {
    // Asesor: Enviar cotización a contador
    Route::post('/cotizaciones/{cotizacion}/enviar', [App\Http\Controllers\CotizacionEstadoController::class, 'enviar'])->name('enviar');
    
    // Contador: Aprobar cotización
    Route::post('/cotizaciones/{cotizacion}/aprobar-contador', [App\Http\Controllers\CotizacionEstadoController::class, 'aprobarContador'])->name('aprobar-contador');
    
    // Contador: Aprobar cotización para pedido (APROBADA_COTIZACIONES -> APROBADO_PARA_PEDIDO)
    Route::post('/cotizaciones/{cotizacion}/aprobar-para-pedido', [App\Http\Controllers\CotizacionEstadoController::class, 'aprobarParaPedido'])->name('aprobar-para-pedido');
    
    // Aprobador de Cotizaciones: Aprobar cotización
    Route::post('/cotizaciones/{cotizacion}/aprobar-aprobador', [App\Http\Controllers\CotizacionEstadoController::class, 'aprobarAprobador'])->name('aprobar-aprobador');
    
    // Aprobador de Cotizaciones: Rechazar y enviar a corrección
    Route::post('/cotizaciones/{cotizacion}/rechazar', [App\Http\Controllers\CotizacionEstadoController::class, 'rechazar'])->name('rechazar');
    
    // Ver historial de cambios
    Route::get('/cotizaciones/{cotizacion}/historial', [App\Http\Controllers\CotizacionEstadoController::class, 'historial'])->name('historial');
    
    // Ver seguimiento de cotización
    Route::get('/cotizaciones/{cotizacion}/seguimiento', [App\Http\Controllers\CotizacionEstadoController::class, 'seguimiento'])->name('seguimiento');
});

// ========================================
// RUTAS PARA ESTADOS DE PEDIDOS
// ========================================
Route::middleware(['auth', 'verified'])->name('pedidos.estado.')->group(function () {
    // Supervisor de Pedidos: Aprobar pedido
    Route::post('/pedidos/{pedido}/aprobar-supervisor', [App\Http\Controllers\PedidoEstadoController::class, 'aprobarSupervisor'])->name('aprobar-supervisor');
    
    // Ver historial de cambios
    Route::get('/pedidos/{pedido}/historial', [App\Http\Controllers\PedidoEstadoController::class, 'historial'])->name('historial');
    
    // Ver seguimiento de pedido
    Route::get('/pedidos/{pedido}/seguimiento', [App\Http\Controllers\PedidoEstadoController::class, 'seguimiento'])->name('seguimiento');
});

// ========================================
// RUTA PARA SERVIR IMÁGENES DE STORAGE
// ========================================

Route::get('/storage-serve/{path}', function($path) {
    $path = str_replace('..', '', $path);
    return redirect('/storage/' . ltrim($path, '/'));
})
    ->where('path', '.*')
    ->name('storage.serve');
// ========================================
// API PÚBLICA - DATOS DE PEDIDOS (SIN AUTH)
// ========================================
Route::prefix('api')->group(function () {
    Route::get('operario/pedido/{numeroPedido}', [\App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'getPedidoData'])
        ->name('api.operario.pedido-data');
});

require __DIR__.'/auth.php';
