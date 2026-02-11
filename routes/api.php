<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api_temp\V1\OrdenController;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\Api_temp\ProcesosController;
use App\Http\Controllers\Api_temp\PedidoController;
use App\Infrastructure\Http\Controllers\CotizacionPrendaController;
use App\Modules\Pedidos\Infrastructure\Http\Controllers\PedidoEppController;
use App\Infrastructure\Http\Controllers\AsistenciaPersonalController;
use App\Infrastructure\Http\Controllers\PrendaEditorController;

/**
 * RUTAS PÚBLICAS - DATOS GENERALES (SIN AUTENTICACIÓN)
 */
Route::prefix('asistencia-personal')->group(function () {
    Route::get('/obtener-todas-las-personas', [AsistenciaPersonalController::class, 'obtenerTodasLasPersonas']);
});

/**
 * API Routes for DDD-based Orden management (FASE 3 - DDD)
 * 
 * Prefix: /api/v1
 * Auth: bearer token (JWT o similar)
 * Controller: App\Http\Controllers\Api\V1\OrdenController
 * 
 * Cumple: SOLID (SRP), DDD (Pure Domain Layer)
 */
Route::middleware('api')->prefix('api/v1')->name('api.v1.')->group(function () {
    
    // Rutas de lectura (GET)
    Route::get('ordenes', [OrdenController::class, 'index'])
        ->name('ordenes.index');
    
    Route::get('ordenes/{numero}', [OrdenController::class, 'show'])
        ->name('ordenes.show');
    
    Route::get('ordenes/cliente/{cliente}', [OrdenController::class, 'porCliente'])
        ->name('ordenes.por-cliente');
    
    Route::get('ordenes/estado/{estado}', [OrdenController::class, 'porEstado'])
        ->name('ordenes.por-estado');

    // Rutas de escritura (POST, PATCH, DELETE)
    Route::post('ordenes', [OrdenController::class, 'store'])
        ->name('ordenes.store');

    // Transiciones de estado
    Route::patch('ordenes/{numero}/aprobar', [OrdenController::class, 'aprobar'])
        ->name('ordenes.aprobar');

    Route::patch('ordenes/{numero}/iniciar-produccion', [OrdenController::class, 'iniciarProduccion'])
        ->name('ordenes.iniciar-produccion');

    Route::patch('ordenes/{numero}/completar', [OrdenController::class, 'completar'])
        ->name('ordenes.completar');

    Route::delete('ordenes/{numero}', [OrdenController::class, 'destroy'])
        ->name('ordenes.destroy');
});

/**
 * API Routes for Prendas (Nueva Arquitectura)
 * 
 * Prefix: /api
 * Auth: session-based (web guard) - Solo para rutas que modifican
 * Controller: App\Http\Controllers\PrendaController
 */
// Rutas PUBLIC - Lectura (GET)
Route::middleware('api')->group(function () {
    Route::apiResource('prendas', PrendaController::class, ['only' => ['show', 'index']]);
    Route::get('prendas/search', [PrendaController::class, 'search'])->name('prendas.search');
    
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('{id}', [PedidoController::class, 'show'])
            ->name('mostrar');
        
        Route::get('cliente/{clienteId}', [PedidoController::class, 'listarPorCliente'])
            ->name('listar-por-cliente');
    });
});

// Rutas PROTECTED - Escritura (POST, PATCH, DELETE)
//  Usando web,auth porque necesitamos sesión + autenticación
Route::withoutMiddleware(['api']) // Remover el middleware api global
    ->middleware(['web', 'auth'])
    ->group(function () {
    Route::apiResource('prendas', PrendaController::class, ['only' => ['store', 'update', 'destroy']]);
    
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::post('/', [PedidoController::class, 'store'])
            ->name('crear');
        
        Route::patch('{id}/confirmar', [PedidoController::class, 'confirmar'])
            ->name('confirmar');
        
        Route::patch('{id}/actualizar-descripcion', [PedidoController::class, 'actualizarDescripcion'])
            ->name('actualizar-descripcion');
        
        Route::delete('{id}/cancelar', [PedidoController::class, 'cancelar'])
            ->name('cancelar');
    });
    
    // Rutas de cotizaciones
    Route::apiResource('cotizaciones', CotizacionPrendaController::class);
    
    // Rutas adicionales para cotizaciones
    Route::get('cotizaciones/{cotizacion_id}/prendas/{prenda_id}/telas-cotizacion', 
        [CotizacionPrendaController::class, 'obtenerTelasCotizacion'])
        ->name('cotizaciones.prendas.telas-cotizacion');
});

// Rutas de procesos (DDD)
Route::middleware('auth')->prefix('procesos')->name('procesos.')->group(function () {
    // Obtener tipos de procesos disponibles
    Route::get('tipos', [ProcesosController::class, 'tipos'])
            ->name('tipos');

        // Procesos de una prenda
        Route::prefix('prendas/{prendaId}')->name('prenda.')->group(function () {
            Route::get('/', [ProcesosController::class, 'obtenerPorPrenda'])
                ->name('listar');
            
            Route::post('/', [ProcesosController::class, 'crear'])
                ->name('crear');
        });

        // Operaciones en procesos específicos
        Route::prefix('{procesoId}')->name('proceso.')->group(function () {
            Route::put('/', [ProcesosController::class, 'actualizar'])
                ->name('actualizar');
            
            Route::delete('/', [ProcesosController::class, 'eliminar'])
                ->name('eliminar');
            
            // Cambios de estado
            Route::post('aprobar', [ProcesosController::class, 'aprobar'])
                ->name('aprobar');
            
            Route::post('rechazar', [ProcesosController::class, 'rechazar'])
                ->name('rechazar');

            // Activar/Desactivar recibo
            Route::post('activar-recibo', [ProcesosController::class, 'activarRecibo'])
                ->name('activar-recibo');

            // Gestión de imágenes
            Route::prefix('imagenes')->name('imagenes.')->group(function () {
                Route::get('/', [ProcesosController::class, 'obtenerImagenes'])
                    ->name('listar');
                
                Route::post('/', [ProcesosController::class, 'subirImagen'])
                    ->name('subir');
                
                Route::post('{imagenId}/principal', [ProcesosController::class, 'marcarComoPrincipal'])
                    ->name('principal');
                
                Route::delete('{imagenId}', [ProcesosController::class, 'eliminarImagen'])
                    ->name('eliminar');
            });
        });
});

// Ruta específica para activar recibos - con middleware api
Route::middleware(['api'])->prefix('api/procesos')->group(function () {
    Route::post('{procesoId}/activar-recibo', [ProcesosController::class, 'activarRecibo'])
        ->name('procesos.activar-recibo');
});

// Gestión de imágenes de EPP
Route::middleware('api')->prefix('epp/{eppId}/imagenes')->name('epp.imagenes.')->group(function () {
    Route::post('/', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'subirImagen'])
        ->name('subir');
});

// Subir imagen de EPP durante creación del pedido
Route::middleware('api')->post('epp/imagenes/upload', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'subirImagenEpp'])
    ->name('epp.imagenes.upload');

Route::middleware('api')->delete('epp/imagenes/{imagenId}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'eliminarImagen'])
    ->name('epp.imagenes.eliminar');

// Búsqueda y listado de EPP
Route::middleware('api')->get('epp', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'index'])
    ->name('epp.index');

// Buscar EPP por término
Route::middleware('api')->get('epps/buscar', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'buscar'])
    ->name('epp.buscar');

// Crear nuevo EPP (solo nombre_completo)
Route::middleware('api')->post('epp', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'crearEppSimple'])
    ->name('epp.store');


// Debug: Prueba simple de EPP
Route::middleware('api')->get('epp-debug', function() {
    try {
        $epps = \App\Models\Epp::where('activo', true)->limit(5)->get();
        return response()->json([
            'success' => true,
            'count' => $epps->count(),
            'data' => $epps->map(fn($e) => [
                'id' => $e->id,
                'codigo' => $e->codigo,
                'nombre_completo' => $e->nombre_completo,
                'activo' => $e->activo,
            ])->toArray(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
})->name('epp.debug');

Route::middleware('api')->get('epp/categorias/all', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'categorias'])
    ->name('epp.categorias');

// Ruta para crear EPP movida arriba (POST epp ahora usa crearEppSimple)

Route::middleware('api')->get('epp/{id}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'show'])
    ->name('epp.show');

// Gestión de EPP en pedidos - Rutas RESTful
Route::middleware('api')->prefix('pedidos/{pedido}/epps')->name('pedidos.epps.')->group(function () {
    Route::get('/', [PedidoEppController::class, 'index'])
        ->name('index');
    
    Route::post('/', [PedidoEppController::class, 'store'])
        ->name('store');
    
    Route::patch('{pedidoEpp}', [PedidoEppController::class, 'update'])
        ->name('update');
    
    Route::delete('{pedidoEpp}', [PedidoEppController::class, 'destroy'])
        ->name('destroy');
    
    Route::get('/exportar/json', [PedidoEppController::class, 'exportarJson'])
        ->name('exportar-json');
});

// Gestión de EPP en pedidos (rutas antiguas - mantenerlas para compatibilidad)
Route::middleware('api')->get('pedidos/{pedidoId}/epp', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'obtenerDelPedido'])
    ->name('pedidos.epp.obtener');

Route::middleware('api')->post('pedidos/{pedidoId}/epp/agregar', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'agregar'])
    ->name('pedidos.epp.agregar');

Route::middleware('api')->delete('pedidos/{pedidoId}/epp/{eppId}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'eliminar'])
    ->name('pedidos.epp.eliminar');

Route::middleware(['web'])->get('pedidos/{pedidoId}/epp/{pedidoEppId}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'obtenerEppDelPedidoPorId'])
    ->name('pedidos.epp.obtener-por-id');

Route::middleware(['web'])->patch('pedidos/{pedidoId}/epp/{pedidoEppId}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'actualizarEppDelPedido'])
    ->name('pedidos.epp.actualizar');

/**
 * API Routes for Pedidos Editables (DDD - Gestión de Ítems)
 * 
 * Prefix: /api/pedidos-editable
 * Auth: auth, role:asesor
 * Controller: App\Http\Controllers\Asesores\CrearPedidoEditableController
 */
require base_path('routes/api-pedidos-editable.php');

/**
 * API Routes for Operario (PUBLIC - Sin autenticación)
 */
Route::prefix('operario')->name('operario.')->middleware([])->group(function () {
    Route::get('pedido/{numeroPedido}', [\App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'getPedidoData'])
        ->name('pedido-data');
});

/**
 * API Routes for Personal (Gestión de Roles)
 */
Route::prefix('personal')->name('personal.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Http\Controllers\Api_temp\PersonalController::class, 'list'])
        ->name('list');
    
    Route::put('{id}/rol', [\App\Http\Controllers\Api_temp\PersonalController::class, 'updateRol'])
        ->name('update-rol');
});

/**
 * API Routes for Horarios (Gestión de Horarios por Roles)
 */
Route::prefix('horarios')->name('horarios.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Http\Controllers\Api_temp\HorarioController::class, 'list'])
        ->name('list');
    
    Route::get('roles-disponibles', [\App\Http\Controllers\Api_temp\HorarioController::class, 'rolesDisponibles'])
        ->name('roles-disponibles');
    
    Route::put('{id}', [\App\Http\Controllers\Api_temp\HorarioController::class, 'update'])
        ->name('update');
    
    Route::post('/', [\App\Http\Controllers\Api_temp\HorarioController::class, 'store'])
        ->name('store');
});

/**
 * API Routes for Asistencias Detalladas (Control de Horas y Marcas)
 */
Route::prefix('asistencias')->name('asistencias.')->middleware(['api'])->group(function () {
    // Obtener asistencias de un personal en un período
    Route::post('obtener', [\App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'obtenerAsistencias'])
        ->name('obtener');
    
    // Obtener asistencia de un día específico
    Route::post('dia', [\App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'obtenerAsistenciaDelDia'])
        ->name('dia');
    
    // Rellenar inteligentemente marcas faltantes
    Route::post('rellenar-inteligente', [\App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'rellenarInteligente'])
        ->name('rellenar-inteligente');
    
    // Guardar cambios de asistencia
    Route::post('guardar', [\App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'guardarCambios'])
        ->name('guardar');
    
    // Obtener resumen del mes
    Route::post('mes', [\App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'obtenerMes'])
        ->name('mes');
});

/**
 * Rutas para importación de artículos/EPP
 */
Route::prefix('articulos')->group(function () {
    Route::post('guardar', [\App\Http\Controllers\Api_temp\ArticulosImportController::class, 'guardarArticulos']);
    Route::get('/', [\App\Http\Controllers\Api_temp\ArticulosImportController::class, 'listar']);
    Route::get('{id}', [\App\Http\Controllers\Api_temp\ArticulosImportController::class, 'obtener']);
});

// Test endpoint para procesamiento de imágenes (sin autenticación por ahora)
Route::post('test-image', [\App\Http\Controllers\TestImageController::class, 'processImage'])
    ->middleware('web');

/**
 * API Routes for Cartera de Pedidos (Tiempo Real)
 */
Route::prefix('cartera')->name('cartera.')->middleware(['auth'])->group(function () {
    Route::get('pedidos', function () {
        return response()->json(['data' => []]);
    });
});

/**
 * API Routes for Prenda Editor (DDD Architecture)
 * 
 * Prefix: /api/prendas
 * Auth: middleware('auth')
 * Controller: App\Infrastructure\Http\Controllers\PrendaEditorController
 * 
 * Implementa DDD con separación de responsabilidades:
 * - Domain: ValueObjects, Entities, Services
 * - Application: DTOs, Services, Handlers
 * - Infrastructure: Controllers, Repositories
 */
Route::prefix('prendas')->name('prendas.')->middleware(['auth'])->group(function () {
    
    // Edición de prendas
    Route::get('{id}/editar', [PrendaEditorController::class, 'editar'])
        ->name('editar');
    
    // Preparar datos para guardar
    Route::post('preparar-guardar', [PrendaEditorController::class, 'prepararGuardar'])
        ->name('preparar-guardar');
    
    // Validación de prendas
    Route::post('validar', [PrendaEditorController::class, 'validar'])
        ->name('validar');
    
    // Tipos de manga disponibles
    Route::get('tipos-manga', [PrendaEditorController::class, 'tiposManga'])
        ->name('tipos-manga');
    
    // Debug endpoint
    Route::get('debug/{id}', [PrendaEditorController::class, 'debug'])
        ->name('debug');
});

/**
 * API Routes for Cotización Prenda (Reflectivo/Logo)
 */
Route::prefix('cotizaciones')->name('cotizaciones.')->middleware(['auth'])->group(function () {
    
    // Datos específicos de cotización para prendas
    Route::get('{cotizacionId}/prendas/{prendaId}/datos-cotizacion', [PrendaEditorController::class, 'datosCotizacion'])
        ->name('prendas.datos-cotizacion');
});

/**
 * API Routes for ColoresPorTalla System
 * Gestiona asignaciones de colores a tallas de prendas
 */
Route::prefix('colores-por-talla')->name('colores-por-talla.')->middleware(['auth'])->group(function () {
    
    // Obtener asignaciones existentes
    Route::get('asignaciones', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'index'])
        ->name('asignaciones.index');
    
    // Guardar asignación de colores
    Route::post('asignaciones', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'store'])
        ->name('asignaciones.store');
    
    // Actualizar asignación específica
    Route::patch('asignaciones/{id}', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'update'])
        ->name('asignaciones.update');
    
    // Eliminar asignación
    Route::delete('asignaciones/{id}', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'destroy'])
        ->name('asignaciones.destroy');
    
    // Obtener colores disponibles para talla
    Route::get('colores-disponibles/{genero}/{talla}', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'coloresDisponibles'])
        ->name('colores-disponibles');
    
    // Obtener tallas disponibles para género
    Route::get('tallas-disponibles/{genero}', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'tallasDisponibles'])
        ->name('tallas-disponibles');
    
    // Procesar asignación del wizard (múltiples tallas)
    Route::post('procesar-asignacion-wizard', [App\Infrastructure\Http\Controllers\ColoresPorTallaController::class, 'procesarAsignacionWizard'])
        ->name('procesar-asignacion-wizard');
});

/**
 * API Routes for Prenda Tallas Processing (DDD)
 */
Route::prefix('prendas')->name('prendas.')->middleware(['auth'])->group(function () {
    
    // Procesamiento de tallas con DDD
    Route::post('{id}/procesar-tallas', [PrendaEditorController::class, 'procesarTallas'])
        ->name('procesar-tallas');
    
    // Procesamiento de variaciones con DDD
    Route::post('{id}/procesar-variaciones', [PrendaEditorController::class, 'procesarVariaciones'])
        ->name('procesar-variaciones');
    
    // Procesamiento de procesos con DDD
    Route::post('{id}/procesar-procesos', [PrendaEditorController::class, 'procesarProcesos'])
        ->name('procesar-procesos');
});