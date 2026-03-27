<?php

/**
 * Rutas para Asesores
 * 
 * Organizacion:
 * - Dashboard: Estadisticas y datos
 * - Perfil: Gestion de perfil
 * - Pedidos: Listado, creacion, edicion
 * - Recibos: Visualizacion de recibos de pedidos
 * - Cotizaciones: Gestion de cotizaciones
 */

use App\Infrastructure\Http\Controllers\Asesores\AsesoresRealtimePedidosController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresDashboardController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPerfilController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresNotificacionesController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosViewController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosQueryController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosCommandController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController;
use App\Infrastructure\Http\Controllers\CotizacionController;
use App\Infrastructure\Http\Controllers\CotizacionEppController;
use App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController;
use App\Infrastructure\Http\Controllers\Asesores\TelasColoresApiController;
use App\Infrastructure\Http\Controllers\Legacy\PDFCotizacionController;
use App\Infrastructure\Http\Controllers\Legacy\PDFPrendaController;
use App\Infrastructure\Http\Controllers\Legacy\PDFCotizacionCombiadaController;
use App\Infrastructure\Http\Controllers\Legacy\PDFLogoController;
use App\Infrastructure\Http\Controllers\Legacy\PDFEppController;
use App\Infrastructure\Http\Controllers\CotizacionController as CotizacionControllerAlias;
use Illuminate\Support\Facades\Route;

Route::prefix('asesores')->name('asesores.')->group(function () {
    
    // ========================================
    // DASHBOARD Y NOTIFICACIONES
    // ========================================
    Route::get('/dashboard', [AsesoresDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [AsesoresDashboardController::class, 'getDashboardData'])->name('dashboard-data');
    
    Route::post('/notifications/mark-all-read', [AsesoresNotificacionesController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notificationId}/mark-read', [AsesoresNotificacionesController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::get('/notifications', [AsesoresNotificacionesController::class, 'getNotifications'])->name('notifications');

    // ========================================
    // PERFIL
    // ========================================
    Route::get('/perfil', [AsesoresPerfilController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [AsesoresPerfilController::class, 'updateProfile'])->name('profile.update');

    // ========================================
    // PEDIDOS - VISTAS Y CRUD
    // ========================================
    Route::get('/pedidos', [AsesoresPedidosViewController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/borradores', [AsesoresPedidosViewController::class, 'borradores'])->name('pedidos.borradores');
    Route::get('/pendientes', [AsesoresPedidosViewController::class, 'pendientes'])->name('pendientes');
    Route::get('/pendientes/{id}', [AsesoresPedidosViewController::class, 'pendientesDetalle'])->name('pendientes.detalle');
    Route::get('/api/pendientes-asesor', [AsesoresPedidosQueryController::class, 'obtenerPendientesAsesor'])->name('api.pendientes-asesor');
    Route::get('/api/conteo-pendientes-asesor', [AsesoresPedidosQueryController::class, 'contarPendientesAsesor'])->name('api.conteo-pendientes-asesor');
    Route::get('/pendientes/{id}/notas', [AsesoresPedidosQueryController::class, 'obtenerNotasPedido'])->name('pendientes.notas');
    Route::get('/cotizaciones/create', [AsesoresPedidosViewController::class, 'create'])->name('pedidos.create');
    Route::get('/pedidos/next-pedido', [AsesoresPedidosQueryController::class, 'getNextPedido'])->name('next-pedido');
    Route::get('/pedidos/{id}', [AsesoresPedidosViewController::class, 'show'])->where('id', '[0-9]+')->name('pedidos.show');
    Route::get('/pedidos/{id}/edit', [AsesoresPedidosViewController::class, 'edit'])->where('id', '[0-9]+')->name('pedidos.edit');
    Route::put('/pedidos/{id}', [AsesoresPedidosCommandController::class, 'update'])->where('id', '[0-9]+')->name('pedidos.update');
    Route::delete('/pedidos/borradores/{id}', [AsesoresPedidosCommandController::class, 'destroyBorrador'])->where('id', '[0-9]+')->name('pedidos.borradores.destroy');
    Route::delete('/pedidos/{id}', [AsesoresPedidosCommandController::class, 'destroy'])->where('id', '[0-9]+')->name('pedidos.destroy');

    // ========================================
    // PEDIDOS - APIs DDD (DEPRECATED)
    // ========================================
    // NOTA: La creacion de pedidos se centraliza en /asesores/pedidos/*
    // y no debe existir otro punto de entrada para crear pedidos.
    Route::get('/prendas-pedido/{prendaPedidoId}/fotos', [PedidoQueryController::class, 'obtenerFotosPrendaPedido'])->where('prendaPedidoId', '[0-9]+')->name('prendas-pedido.fotos');
    
    // API para listado de pedidos en tiempo real
    Route::get('/pedidos-api-listar', [AsesoresPedidosQueryController::class, 'apiListar']);
    
    // Ruta de prueba
    Route::get('/test', function() {
        return response()->json(['message' => 'Test working']);
    });
    
    // ========================================
    // TELAS Y COLORES - APIs
    // ========================================
    Route::get('/api/telas', [TelasColoresApiController::class, 'getTelas'])->name('api.telas');
    Route::get('/api/colores', [TelasColoresApiController::class, 'getColores'])->name('api.colores');
    
    // Cargar datos de pedido para edicion
    Route::get('/pedidos/{id}/editar-datos', [\App\Infrastructure\Http\Controllers\PedidoQueryController::class, 'obtenerDatosEdicion'])->where('id', '[0-9]+')->name('pedidos.api.editar-datos');
    
    // Obtener datos de una prenda especifica con procesos para edicion modal
    Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('pedidos.prenda-datos');
    
    // Obtener datos del pedido para edicion (sin prenda especifica)
    Route::get('/pedidos-produccion/{pedidoId}/datos-edicion', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'obtenerDatosEdicion'])->where('pedidoId', '[0-9]+')->name('pedidos.datos-edicion');
    
    // Agregar prenda simple al pedido
    Route::post('/pedidos/{pedidoId}/agregar-prenda-simple', [AsesoresPedidosCommandController::class, 'agregarPrendaSimple'])->where('pedidoId', '[0-9]+')->name('pedidos.agregar-prenda-simple');
    
    // Agregar prenda completa (con telas y procesos) al pedido en edicion
    Route::post('/pedidos/{id}/agregar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'agregarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.agregar-prenda-completa');
    
    // Actualizar prenda completa (con novedades) en un pedido existente
    Route::post('/pedidos/{id}/actualizar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'actualizarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.actualizar-prenda-completa');

    // Eliminar prenda de un pedido y registrar motivo en novedades
    Route::post('/pedidos/{id}/eliminar-prenda', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'eliminarPrenda'])->where('id', '[0-9]+')->name('pedidos.eliminar-prenda');

    // Eliminar EPP de un pedido y registrar motivo en novedades
    Route::post('/pedidos/{id}/eliminar-epp', [\App\Infrastructure\Http\Controllers\Asesores\EppsPedidoController::class, 'eliminarEpp'])->where('id', '[0-9]+')->name('pedidos.eliminar-epp');

    // Homologar EPP: marcar como eliminado y duplicar
    Route::post('/pedidos/{id}/homologar-epp', [\App\Infrastructure\Http\Controllers\Asesores\EppsPedidoController::class, 'homologarEpp'])->where('id', '[0-9]+')->name('pedidos.homologar-epp');

    // Actualizar SOLO la variante de prenda (manga, broche, bolsillos) - CON MERGE
    Route::put('/pedidos/{pedidoId}/prendas/{prendaId}/variante', [\App\Infrastructure\Http\Controllers\Asesores\VariantesPrendaController::class, 'actualizarVariantePrend'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('pedidos.actualizar-variante-prenda');

    // ========================================
    // RECIBOS - NUEVO MODULO
    // ========================================
    Route::get('/recibos', [ReciboController::class, 'index'])->name('recibos.index');
    Route::get('/recibos/{id}', [ReciboController::class, 'show'])->name('recibos.show');
    Route::get('/recibos/{id}/datos', [ReciboController::class, 'datos'])->name('recibos.datos');
    Route::get('/recibos/{id}/pdf', [ReciboController::class, 'generarPDF'])->name('recibos.pdf');
    
    // Alias para compatibilidad con rutas antiguas
    Route::get('/pedidos/{id}/recibos-datos', [ReciboController::class, 'datos'])->where('id', '[0-9]+')->name('pedidos.api.recibos-datos');

    // ========================================
    // OBSERVACIONES DE DESPACHO
    // ========================================
    Route::post('/pedidos/observaciones-despacho/resumen', [ObservacionesDespachoController::class, 'resumen'])->name('observaciones-despacho.resumen');
    Route::get('/pedidos/{id}/observaciones-despacho', [ObservacionesDespachoController::class, 'obtener'])->where('id', '[0-9]+')->name('observaciones-despacho.obtener');
    Route::post('/pedidos/{id}/observaciones-despacho', [ObservacionesDespachoController::class, 'guardar'])->where('id', '[0-9]+')->name('observaciones-despacho.guardar');
    Route::put('/pedidos/{id}/observaciones-despacho/{observacionId}', [ObservacionesDespachoController::class, 'actualizar'])->where('id', '[0-9]+')->name('observaciones-despacho.actualizar');
    Route::delete('/pedidos/{id}/observaciones-despacho/{observacionId}', [ObservacionesDespachoController::class, 'eliminar'])->where('id', '[0-9]+')->name('observaciones-despacho.eliminar');
    Route::post('/pedidos/{id}/observaciones-despacho/marcar-leidas', [ObservacionesDespachoController::class, 'marcarLeidas'])->where('id', '[0-9]+')->name('observaciones-despacho.marcar-leidas');
    Route::post('/pedidos/{id}/observaciones-despacho/marcar-bodega-vistas', [ObservacionesDespachoController::class, 'marcarBodegaVistas'])->where('id', '[0-9]+')->name('observaciones-despacho.marcar-bodega-vistas');

    // ========================================
    // ORDENES/COTIZACIONES - SISTEMA DE BORRADORES
    // ========================================
    Route::get('/borradores', [CotizacionControllerAlias::class, 'borradores'])->name('borradores.index');

    // ========================================
    // COTIZACIONES - DDD Refactorizado
    // ========================================
    Route::get('/cotizaciones', [CotizacionesViewController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/filtros/valores', [CotizacionesFiltrosController::class, 'valores'])->name('cotizaciones.filtros.valores');
    Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::put('/cotizaciones/{id}', [CotizacionController::class, 'update'])->name('cotizaciones.update');

    // Editar cotizacion ya creada (NO borrador) - reusa el mismo formulario y carga
    Route::get('/cotizaciones/{id}/editar-cotizacion', [CotizacionController::class, 'editCotizacion'])->name('cotizaciones.edit-creada');

    // ========================================
    // COTIZACIONES - EPP (nuevo)
    // ========================================
    Route::post('/cotizaciones-epp', [CotizacionEppController::class, 'store'])->name('cotizaciones-epp.store');
    
    // ========================================
    // PDF GENERATION - NEW REFACTORED STRUCTURE
    // ========================================
    // IMPORTANTE: Las rutas especificas deben ir ANTES que la generica
    Route::get('/cotizacion/{id}/pdf/prenda', [PDFPrendaController::class, 'generate'])->name('cotizacion.pdf.prenda');
    Route::get('/cotizacion/{id}/pdf/combinada', [PDFCotizacionCombiadaController::class, 'generate'])->name('cotizacion.pdf.combinada');
    Route::get('/cotizacion/{id}/pdf/logo', [PDFLogoController::class, 'generate'])->name('cotizacion.pdf.logo'); // Route for logo PDF
    Route::get('/cotizacion/{id}/pdf/epp', [PDFEppController::class, 'generate'])->name('cotizacion.pdf.epp');
    Route::get('/cotizacion/{id}/pdf', [PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf'); // Legacy route - DEBE SER ULTIMO
    
    Route::delete('/cotizaciones/imagenes/prenda/{id}', [ImagenBorradorController::class, 'borrarPrenda'])->name('cotizaciones.imagen.borrar-prenda');
    Route::delete('/cotizaciones/imagenes/tela/{id}', [ImagenBorradorController::class, 'borrarTela'])->name('cotizaciones.imagen.borrar-tela');
    Route::delete('/cotizaciones/imagenes/logo/{id}', [ImagenBorradorController::class, 'borrarLogo'])->name('cotizaciones.imagen.borrar-logo');
    Route::get('/cotizaciones/{id}/editar', [CotizacionController::class, 'getForEdit'])->name('cotizaciones.get-for-edit');
    Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show'])->name('cotizaciones.api');
    Route::post('/cotizaciones/{id}/imagenes', [CotizacionController::class, 'subirImagen'])->name('cotizaciones.subir-imagen');

    // ========================================
    // INVENTARIO Y RECURSOS
    // ========================================
    Route::get('/inventario-telas', [AsesoresInventarioTelasController::class, 'index'])->name('inventario.telas');
    
    // ========================================
    // PRENDAS - AUTOCOMPLETE
    // ========================================
    Route::get('/api/prendas/autocomplete', [\App\Infrastructure\Http\Controllers\Asesores\ObtenerPrendasAutocompleteController::class, 'obtenerPrendasAutocomplete'])->name('api.prendas.autocomplete');


    // ========================================
    // FOTOS
    // ========================================
    Route::post('/fotos/eliminar', [CotizacionController::class, 'eliminarFotoInmediatamente'])->name('fotos.eliminar-inmediatamente');

    // ========================================
    // API REALTIME - PEDIDOS
    // ========================================
    Route::get('/realtime/pedidos', [AsesoresRealtimePedidosController::class, 'listar'])->name('realtime.pedidos.listar');

    // ========================================
    // PEDIDOS (UNICA FUENTE PARA CREACION)
    // ========================================
    Route::prefix('pedidos')->name('pedidos.')->middleware('role:asesor,admin,supervisor_pedidos')->group(function () {
        // Formularios de pedidos (GET - renderiza vistas)
        Route::get('crear-desde-cotizacion', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\ObtenerPedidoFormDataController::class, 'crearDesdeCotizacion'])
            ->name('crear-desde-cotizacion');
        Route::get('crear-nuevo', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\ObtenerPedidoFormDataController::class, 'crearNuevo'])
            ->name('crear-nuevo');
        
        // Creacion de pedido (POST - API)
        Route::post('crear', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoController::class, 'crearPedido'])
            ->name('crear');
        
        // Validar pedido antes de crear (POST - API)
        Route::post('validar', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\ValidarPedidoController::class, 'validarPedido'])
            ->name('validar');
        
        // Guardar/actualizar borradores
        Route::post('borrador', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoBorradorController::class, 'guardarBorrador'])
            ->name('guardarBorrador');
        Route::put('{pedidoId}/borrador', [\App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoBorradorController::class, 'actualizarBorrador'])
            ->name('actualizar')
            ->where('pedidoId', '[0-9]+');
    });
});


