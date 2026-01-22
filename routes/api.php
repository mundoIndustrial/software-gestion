<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrdenController;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\Api\ProcesosController;
use App\Http\Controllers\Api\PedidoController;
use App\Infrastructure\Http\Controllers\CotizacionPrendaController;
use App\Modules\Pedidos\Infrastructure\Http\Controllers\PedidoEppController;
use App\Infrastructure\Http\Controllers\AsistenciaPersonalController;

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
 * Auth: bearer token
 * Controller: App\Http\Controllers\PrendaController
 */
Route::middleware('api')->group(function () {
    // Rutas de prendas
    Route::apiResource('prendas', PrendaController::class);
    Route::get('prendas/search', [PrendaController::class, 'search'])->name('prendas.search');
    
    // Rutas de Pedidos (DDD)
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::post('/', [PedidoController::class, 'store'])
            ->name('crear');
        
        Route::get('{id}', [PedidoController::class, 'show'])
            ->name('mostrar');
        
        Route::patch('{id}/confirmar', [PedidoController::class, 'confirmar'])
            ->name('confirmar');
    });
    
    // Rutas de cotizaciones
    Route::apiResource('cotizaciones', CotizacionPrendaController::class);

    // Rutas de procesos (DDD)
    Route::prefix('procesos')->name('procesos.')->group(function () {
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

    // Gestión de imágenes de EPP
    Route::prefix('epp/{eppId}/imagenes')->name('epp.imagenes.')->group(function () {
        Route::post('/', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'subirImagen'])
            ->name('subir');
    });

    Route::delete('epp/imagenes/{imagenId}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'eliminarImagen'])
        ->name('epp.imagenes.eliminar');

    // Búsqueda y listado de EPP
    Route::get('epp', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'index'])
        ->name('epp.index');
    
    // Debug: Prueba simple de EPP
    Route::get('epp-debug', function() {
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

    Route::get('epp/categorias/all', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'categorias'])
        ->name('epp.categorias');

    Route::post('epp', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'store'])
        ->name('epp.store');

    Route::get('epp/{id}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'show'])
        ->name('epp.show');

    // Gestión de EPP en pedidos - Rutas RESTful
    Route::prefix('pedidos/{pedido}/epps')->name('pedidos.epps.')->group(function () {
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
    Route::get('pedidos/{pedidoId}/epp', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'obtenerDelPedido'])
        ->name('pedidos.epp.obtener');

    Route::post('pedidos/{pedidoId}/epp/agregar', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'agregar'])
        ->name('pedidos.epp.agregar');

    Route::delete('pedidos/{pedidoId}/epp/{eppId}', [\App\Infrastructure\Http\Controllers\Epp\EppController::class, 'eliminar'])
        ->name('pedidos.epp.eliminar');
});

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
    Route::get('list', [\App\Http\Controllers\API\PersonalController::class, 'list'])
        ->name('list');
    
    Route::put('{id}/rol', [\App\Http\Controllers\API\PersonalController::class, 'updateRol'])
        ->name('update-rol');
});

/**
 * API Routes for Horarios (Gestión de Horarios por Roles)
 */
Route::prefix('horarios')->name('horarios.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Http\Controllers\API\HorarioController::class, 'list'])
        ->name('list');
    
    Route::get('roles-disponibles', [\App\Http\Controllers\API\HorarioController::class, 'rolesDisponibles'])
        ->name('roles-disponibles');
    
    Route::put('{id}', [\App\Http\Controllers\API\HorarioController::class, 'update'])
        ->name('update');
    
    Route::post('/', [\App\Http\Controllers\API\HorarioController::class, 'store'])
        ->name('store');
});

/**
 * API Routes for Asistencias Detalladas (Control de Horas y Marcas)
 */
Route::prefix('asistencias')->name('asistencias.')->middleware(['api'])->group(function () {
    // Obtener asistencias de un personal en un período
    Route::post('obtener', [\App\Http\Controllers\API\AsistenciaDetalladaController::class, 'obtenerAsistencias'])
        ->name('obtener');
    
    // Obtener asistencia de un día específico
    Route::post('dia', [\App\Http\Controllers\API\AsistenciaDetalladaController::class, 'obtenerAsistenciaDelDia'])
        ->name('dia');
    
    // Rellenar inteligentemente marcas faltantes
    Route::post('rellenar-inteligente', [\App\Http\Controllers\API\AsistenciaDetalladaController::class, 'rellenarInteligente'])
        ->name('rellenar-inteligente');
    
    // Guardar cambios de asistencia
    Route::post('guardar', [\App\Http\Controllers\API\AsistenciaDetalladaController::class, 'guardarCambios'])
        ->name('guardar');
    
    // Obtener resumen del mes
    Route::post('mes', [\App\Http\Controllers\API\AsistenciaDetalladaController::class, 'obtenerMes'])
        ->name('mes');
});

/**
 * Rutas para importación de artículos/EPP
 */
Route::prefix('articulos')->group(function () {
    Route::post('guardar', [\App\Http\Controllers\Api\ArticulosImportController::class, 'guardarArticulos']);
    Route::get('/', [\App\Http\Controllers\Api\ArticulosImportController::class, 'listar']);
    Route::get('{id}', [\App\Http\Controllers\Api\ArticulosImportController::class, 'obtener']);
});

// Test endpoint para procesamiento de imágenes (sin autenticación por ahora)
Route::post('test-image', [\App\Http\Controllers\TestImageController::class, 'processImage'])
    ->middleware('web');