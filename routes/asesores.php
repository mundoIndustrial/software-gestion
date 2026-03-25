<?php

/**
 * Rutas para Asesores
 * 
 * Organización:
 * - Dashboard: Estadísticas y datos
 * - Perfil: Gestión de perfil
 * - Pedidos: Listado, creación, edición
 * - Recibos: Visualización de recibos de pedidos
 * - Cotizaciones: Gestión de cotizaciones
 */

use App\Infrastructure\Http\Controllers\Asesores\AsesoresController;
use App\Infrastructure\Http\Controllers\PedidoCommandController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use App\Infrastructure\Http\Controllers\CatalogoController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController;
use App\Infrastructure\Http\Controllers\CotizacionController;
use App\Infrastructure\Http\Controllers\CotizacionEppController;
use App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController;
use App\Http\Controllers\PDFCotizacionController;
use App\Http\Controllers\PDFPrendaController;
use App\Http\Controllers\PDFCotizacionCombiadaController;
use App\Http\Controllers\PDFLogoController;
use App\Http\Controllers\PDFEppController;
use App\Infrastructure\Http\Controllers\CotizacionController as CotizacionControllerAlias;
use Illuminate\Support\Facades\Route;

Route::prefix('asesores')->name('asesores.')->group(function () {
    
    // ========================================
    // DASHBOARD Y NOTIFICACIONES
    // ========================================
    Route::get('/dashboard', [AsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [AsesoresController::class, 'getDashboardData'])->name('dashboard-data');
    
    Route::post('/notifications/mark-all-read', [AsesoresController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notificationId}/mark-read', [AsesoresController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::get('/notifications', [AsesoresController::class, 'getNotifications'])->name('notifications');

    // ========================================
    // PERFIL
    // ========================================
    Route::get('/perfil', [AsesoresController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [AsesoresController::class, 'updateProfile'])->name('profile.update');

    // ========================================
    // PEDIDOS - VISTAS Y CRUD
    // ========================================
    Route::get('/pedidos', [AsesoresController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/borradores', [AsesoresController::class, 'borradores'])->name('pedidos.borradores');
    Route::get('/pendientes', [AsesoresController::class, 'pendientes'])->name('pendientes');
    Route::get('/pendientes/{id}', [AsesoresController::class, 'pendientesDetalle'])->name('pendientes.detalle');
    Route::get('/api/pendientes-asesor', [AsesoresController::class, 'obtenerPendientesAsesor'])->name('api.pendientes-asesor');
    Route::get('/api/conteo-pendientes-asesor', [AsesoresController::class, 'contarPendientesAsesor'])->name('api.conteo-pendientes-asesor');
    Route::get('/pendientes/{id}/notas', [AsesoresController::class, 'obtenerNotasPedido'])->name('pendientes.notas');
    Route::get('/cotizaciones/create', [AsesoresController::class, 'create'])->name('pedidos.create');
    Route::get('/pedidos/next-pedido', [AsesoresController::class, 'getNextPedido'])->name('next-pedido');
    Route::get('/pedidos/{id}', [AsesoresController::class, 'show'])->where('id', '[0-9]+')->name('pedidos.show');
    Route::get('/pedidos/{id}/edit', [AsesoresController::class, 'edit'])->where('id', '[0-9]+')->name('pedidos.edit');
    Route::put('/pedidos/{id}', [AsesoresController::class, 'update'])->where('id', '[0-9]+')->name('pedidos.update');
    Route::delete('/pedidos/borradores/{id}', [AsesoresController::class, 'destroyBorrador'])->where('id', '[0-9]+')->name('pedidos.borradores.destroy');
    Route::delete('/pedidos/{id}', [AsesoresController::class, 'destroy'])->where('id', '[0-9]+')->name('pedidos.destroy');

    // ========================================
    // PEDIDOS - APIs DDD (DEPRECATED)
    // ========================================
    // NOTA: La creación de pedidos se centraliza en /asesores/pedidos-editable/*
    // y no debe existir otro punto de entrada para crear pedidos.
    Route::post('/pedidos/confirm', [PedidoCommandController::class, 'confirm'])->name('pedidos.api.confirm');
    Route::post('/pedidos/{id}/anular', [AsesoresController::class, 'anularPedido'])->where('id', '[0-9]+')->name('pedidos.api.anular');
    Route::get('/prendas-pedido/{prendaPedidoId}/fotos', [PedidoQueryController::class, 'obtenerFotosPrendaPedido'])->where('prendaPedidoId', '[0-9]+')->name('prendas-pedido.fotos');
    
    // API para listado de pedidos en tiempo real
    Route::get('/pedidos-api-listar', [AsesoresController::class, 'apiListar']);
    
    // Ruta de prueba
    Route::get('/test', function() {
        return response()->json(['message' => 'Test working']);
    });
    
    // Cargar datos de pedido para edición
    Route::get('/pedidos/{id}/editar-datos', [\App\Infrastructure\Http\Controllers\PedidoQueryController::class, 'obtenerDatosEdicion'])->where('id', '[0-9]+')->name('pedidos.api.editar-datos');
    
    // Obtener datos de una prenda específica con procesos para edición modal
    Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('pedidos.prenda-datos');
    
    // Obtener datos del pedido para edición (sin prenda específica)
    Route::get('/pedidos-produccion/{pedidoId}/datos-edicion', [\App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController::class, 'obtenerDatosEdicion'])->where('pedidoId', '[0-9]+')->name('pedidos.datos-edicion');
    
    // Agregar prenda simple al pedido
    Route::post('/pedidos/{pedidoId}/agregar-prenda-simple', [AsesoresController::class, 'agregarPrendaSimple'])->where('pedidoId', '[0-9]+')->name('pedidos.agregar-prenda-simple');
    
    // Agregar prenda completa (con telas y procesos) al pedido en edición
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
    // RECIBOS - NUEVO MÓDULO
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
    // ÓRDENES/COTIZACIONES - SISTEMA DE BORRADORES
    // ========================================
    Route::get('/borradores', [CotizacionControllerAlias::class, 'borradores'])->name('borradores.index');

    // ========================================
    // COTIZACIONES - DDD Refactorizado
    // ========================================
    Route::get('/cotizaciones', [CotizacionesViewController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/filtros/valores', [CotizacionesFiltrosController::class, 'valores'])->name('cotizaciones.filtros.valores');
    Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::put('/cotizaciones/{id}', [CotizacionController::class, 'update'])->name('cotizaciones.update');

    // Editar cotización ya creada (NO borrador) - reusa el mismo formulario y carga
    Route::get('/cotizaciones/{id}/editar-cotizacion', [CotizacionController::class, 'editCotizacion'])->name('cotizaciones.edit-creada');

    // ========================================
    // COTIZACIONES - EPP (nuevo)
    // ========================================
    Route::post('/cotizaciones-epp', [CotizacionEppController::class, 'store'])->name('cotizaciones-epp.store');
    
    // ========================================
    // PDF GENERATION - NEW REFACTORED STRUCTURE
    // ========================================
    // IMPORTANTE: Las rutas específicas deben ir ANTES que la genérica
    Route::get('/cotizacion/{id}/pdf/prenda', [PDFPrendaController::class, 'generate'])->name('cotizacion.pdf.prenda');
    Route::get('/cotizacion/{id}/pdf/combinada', [PDFCotizacionCombiadaController::class, 'generate'])->name('cotizacion.pdf.combinada');
    Route::get('/cotizacion/{id}/pdf/logo', [PDFLogoController::class, 'generate'])->name('cotizacion.pdf.logo'); // Route for logo PDF
    Route::get('/cotizacion/{id}/pdf/epp', [PDFEppController::class, 'generate'])->name('cotizacion.pdf.epp');
    Route::get('/cotizacion/{id}/pdf', [PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf'); // Legacy route - DEBE SER ÚLTIMO
    
    Route::delete('/cotizaciones/imagenes/prenda/{id}', [ImagenBorradorController::class, 'borrarPrenda'])->name('cotizaciones.imagen.borrar-prenda');
    Route::delete('/cotizaciones/imagenes/tela/{id}', [ImagenBorradorController::class, 'borrarTela'])->name('cotizaciones.imagen.borrar-tela');
    Route::delete('/cotizaciones/imagenes/logo/{id}', [ImagenBorradorController::class, 'borrarLogo'])->name('cotizaciones.imagen.borrar-logo');
    Route::get('/cotizaciones/{id}/editar', [CotizacionController::class, 'getForEdit'])->name('cotizaciones.get-for-edit');
    Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show'])->name('cotizaciones.api');
    Route::post('/cotizaciones/{id}/imagenes', [CotizacionController::class, 'subirImagen'])->name('cotizaciones.subir-imagen');

    // ========================================
    // INVENTARIO Y RECURSOS
    // ========================================
    Route::get('/inventario-telas', [AsesoresController::class, 'inventarioTelas'])->name('inventario.telas');
    
    // ========================================
    // DATOS DE CATÁLOGOS (tipos de broche, manga, telas, colores, etc)
    // ========================================
    Route::get('/api/tipos-broche-boton', [CatalogoController::class, 'obtenerTiposBrocheBoton'])->name('api.tipos-broche-boton');
    Route::get('/api/tipos-manga', [CatalogoController::class, 'obtenerTiposManga'])->name('api.tipos-manga');
    Route::post('/api/tipos-manga', [CatalogoController::class, 'crearObtenerTipoManga'])->name('api.tipos-manga.create');
    Route::get('/api/telas', [CatalogoController::class, 'obtenerTelas'])->name('api.telas');
    Route::post('/api/telas', [CatalogoController::class, 'crearObtenerTela'])->name('api.telas.create');
    Route::get('/api/colores', [CatalogoController::class, 'obtenerColores'])->name('api.colores');
    Route::post('/api/colores', [CatalogoController::class, 'crearObtenerColor'])->name('api.colores.create');
    Route::get('/api/prendas/autocomplete', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'obtenerPrendasAutocomplete'])->name('api.prendas.autocomplete');

    // ========================================
    // FOTOS
    // ========================================
    Route::post('/fotos/eliminar', [CotizacionController::class, 'eliminarFotoInmediatamente'])->name('fotos.eliminar-inmediatamente');

    // ========================================
    // API REALTIME - PEDIDOS
    // ========================================
    Route::get('/realtime/pedidos', function () {
        // Debug: Ver información del usuario y roles
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'No authenticated user'], 403);
        }
        
        // Debug: Mostrar todos los roles del usuario
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Verificar si el usuario tiene permisos (verificación manual)
        $hasPermission = $user->hasRole('asesor') || 
                       $user->hasRole('admin') || 
                       $user->hasRole('supervisor_pedidos') || 
                       $user->hasRole('despacho') ||
                       $user->hasRole('insumos');
        
        // Log para debug
        \Log::info('[REALTIME-API] Verificación de permisos', [
            'user_id' => $user->id,
            'user_roles' => $userRoles,
            'has_permission' => $hasPermission
        ]);
        
        if (!$hasPermission) {
            return response()->json([
                'error' => 'Unauthorized',
                'debug' => [
                    'user_id' => $user->id,
                    'user_roles' => $userRoles,
                    'has_permission' => $hasPermission
                ]
            ], 403);
        }
        
        // Obtener pedidos según el rol del usuario
        $query = \App\Models\PedidoProduccion::select('id', 'numero_pedido', 'cliente', 'estado', 'area', 'novedades', 'forma_de_pago', 'created_at', 'fecha_estimada_de_entrega');
        
        // Si es asesor, solo mostrar sus pedidos
        if ($user->hasRole('asesor')) {
            $query->where('asesor_id', $user->id);
        }
        
        $pedidos = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $pedidos->toArray(),
            'debug' => [
                'user_id' => $user->id,
                'user_roles' => $userRoles,
                'pedidos_count' => $pedidos->count()
            ]
        ]);
    })->name('realtime.pedidos.listar');

    // ========================================
    // PEDIDOS EDITABLES (UNICA FUENTE PARA CREACION)
    // ========================================
    Route::prefix('pedidos-editable')->name('pedidos-editable.')->middleware('role:asesor,admin,supervisor_pedidos')->group(function () {

        // Mostrar formulario para crear desde COTIZACIÓN (pre-carga cotizaciones)
        Route::get('crear-desde-cotizacion', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'crearDesdeCotizacion'])
            ->name('crear-desde-cotizacion');
        
        // Mostrar formulario para crear PEDIDO NUEVO (vacío)
        Route::get('crear-nuevo', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'crearNuevo'])
            ->name('crear-nuevo');
        
        // Gestión de ítems (retorna JSON)
        Route::post('items/agregar', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'agregarItem'])
            ->name('agregar-item');
        Route::post('items/eliminar', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'eliminarItem'])
            ->name('eliminar-item');
        Route::get('items', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'obtenerItems'])
            ->name('obtener-items');

        // Cotizaciones: cargar items EPP (para "Crear desde cotización")
        Route::get('cotizaciones/{cotizacion}/epp-items', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'obtenerItemsEppCotizacion'])
            ->name('cotizaciones.epp-items');
        
        // Validación y creación
        Route::post('validar', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'validarPedido'])
            ->name('validar');
        Route::post('crear', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'crearPedido'])
            ->name('crear');
        Route::post('borrador', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'guardarBorrador'])
            ->name('guardarBorrador');
        Route::post('{pedidoId}/actualizar', [\App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'actualizarBorrador'])
            ->name('actualizar')
            ->where('pedidoId', '[0-9]+');
    });
});
