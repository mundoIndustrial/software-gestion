<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\CotizacionPrendaController;
use App\Infrastructure\Http\Controllers\CotizacionBordadoController;
use App\Infrastructure\Http\Controllers\Cotizaciones\CotizacionEstadoController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\Contador\CotizacionDetalleController;
use App\Infrastructure\Http\Controllers\Contador\CotizacionCostosController;
use App\Infrastructure\Http\Controllers\Pdf\CotizacionPdfController;
use App\Infrastructure\Http\Controllers\Pdf\PrendaPdfController;
use App\Infrastructure\Http\Controllers\Pdf\CombinadaPdfController;
use App\Infrastructure\Http\Controllers\Pdf\EppPdfController;

// ========================================
// RUTAS PARA COTIZACIONES - PRENDA (DDD REFACTORIZADO)
// ========================================
Route::middleware(['auth', 'role:asesor,admin,lider_produccion,supervisor_produccion,supervisor_pedidos'])->group(function () {
    // Cotizaciones tipo PRENDA
    Route::get('/cotizaciones-prenda/crear', [CotizacionPrendaController::class, 'create'])->name('cotizaciones-prenda.create');
    Route::post('/cotizaciones-prenda', [CotizacionPrendaController::class, 'store'])->name('cotizaciones-prenda.store');
    Route::get('/cotizaciones-prenda', [CotizacionPrendaController::class, 'lista'])->name('cotizaciones-prenda.lista');
    Route::get('/cotizaciones-prenda/{cotizacion}/editar', [CotizacionPrendaController::class, 'edit'])->name('cotizaciones-prenda.edit');
    Route::put('/cotizaciones-prenda/{cotizacion}', [CotizacionPrendaController::class, 'update'])->name('cotizaciones-prenda.update');
    Route::post('/cotizaciones-prenda/{cotizacion}/enviar', [CotizacionPrendaController::class, 'enviar'])->name('cotizaciones-prenda.enviar');
    Route::delete('/cotizaciones-prenda/{cotizacion}', [CotizacionPrendaController::class, 'destroy'])->name('cotizaciones-prenda.destroy');
    
    // Rutas para borrar IMAGENES de cotizaciones (Prenda)
    Route::post('/cotizaciones/{id}/borrar-imagen-prenda', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'borrarImagenPrenda'])->name('cotizaciones.borrar-imagen-prenda');
    Route::post('/cotizaciones/{id}/borrar-imagen-tela', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'borrarImagenTela'])->name('cotizaciones.borrar-imagen-tela');
});

// ========================================
// RUTAS PARA COTIZACIONES - BORDADO (DDD REFACTORIZADO)
// ========================================
Route::middleware(['auth', 'role:asesor,admin,lider_produccion,supervisor_produccion,supervisor_pedidos'])->group(function () {
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
    
    // RUTAS PARA TELAS DE PRENDAS EN COTIZACION DE LOGO
    Route::post('/cotizaciones/{cotizacion_id}/logo/telas-prenda', [CotizacionBordadoController::class, 'guardarTelaPrenda'])->name('cotizaciones-bordado.guardar-tela-prenda');
    Route::get('/cotizaciones/{cotizacion_id}/logo/telas-prenda', [CotizacionBordadoController::class, 'obtenerTelasPrenda'])->name('cotizaciones-bordado.obtener-telas-prenda');
    Route::delete('/cotizaciones/{cotizacion_id}/logo/telas-prenda/{tela_id}', [CotizacionBordadoController::class, 'eliminarTelaPrenda'])->name('cotizaciones-bordado.eliminar-tela-prenda');
});


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
            abort(403, 'No tienes permiso para acceder a esta seccion.');
        }
        
        // Obtener cotizaciones pendientes de aprobacion (estado APROBADA_CONTADOR)
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

    // Obtener datos de cotizacion para modal (AJAX)
    Route::get('/cotizaciones/{cotizacion}/datos', [CotizacionesViewController::class, 'getDatosForModal'])
        ->name('cotizaciones.obtener-datos');

    // Obtener costos de cotizacion (AJAX)
    Route::get('/cotizaciones/{cotizacion}/costos', [CotizacionCostosController::class, 'show'])
        ->name('cotizaciones.obtener-costos');

    // Obtener contador de cotizaciones pendientes para aprobador (AJAX)
    Route::get('/pendientes-count', [CotizacionesViewController::class, 'cotizacionesPendientesAprobadorCount'])
        ->name('cotizaciones.pendientes-count');

    // Acceso a modal de ver cotizacion desde aprobador de cotizaciones - RUTA ACCESIBLE PARA APROBADOR, CONTADOR Y ADMIN
    Route::get('/contador/cotizacion/{id}', [CotizacionDetalleController::class, 'show'])
        ->middleware('auth')
        ->name('aprobador.cotizacion.detail');

    // Acceso a costos de cotizacion desde aprobador de cotizaciones - RUTA ACCESIBLE PARA APROBADOR, CONTADOR Y ADMIN
    Route::get('/contador/cotizacion/{cotizacion}/costos', [CotizacionCostosController::class, 'show'])
        ->name('aprobador.cotizacion.costos');
});

// ========================================
// RUTA DE PDF COMPARTIDA (Accesible para asesores, contador, visualizador_cotizaciones_logo y admin)
// ========================================
Route::middleware(['auth'])->group(function () {
    Route::get('/cotizacion/{id}/pdf', [CotizacionPdfController::class, 'show'])->name('cotizacion.pdf');
});

// ========================================
// PDF - RUTAS GLOBALES (PARA OTROS MODULOS)
// ========================================
// Necesarias para que modulos como Contador puedan descargar PDFs sin depender de /asesores.
Route::middleware(['auth', 'role:asesor,admin,lider_produccion,supervisor_produccion,supervisor_pedidos,despacho,contador,aprobador_cotizaciones'])->group(function () {
    Route::get('/cotizacion/{id}/pdf/prenda', [PrendaPdfController::class, 'show']);
    Route::get('/cotizacion/{id}/pdf/combinada', [CombinadaPdfController::class, 'show']);
    Route::get('/cotizacion/{id}/pdf/epp', [EppPdfController::class, 'show']);
});

// ========================================
// RUTAS PARA MODULO BORDADO
// ========================================
Route::middleware(['auth', 'role:bordado,admin'])->prefix('bordado')->name('bordado.')->group(function () {
    // Listar pedidos del modulo Bordado
    Route::get('/', function () {
        return view('bordado.index');
    })->name('index');

    // Ruta legada de cotizaciones (redireccionar a lista)
    Route::get('/cotizaciones', function () {
        return redirect()->route('bordado.cotizaciones.lista');
    })->name('cotizaciones');

    // Cotizaciones - SUBMENU
    Route::prefix('cotizaciones')->name('cotizaciones.')->group(function () {
        // Lista de cotizaciones
        Route::get('/lista', function () {
            return view('bordado.cotizaciones.lista');
        })->name('lista');

        // Medidas
        Route::get('/medidas', function () {
            return view('bordado.cotizaciones.medidas');
        })->name('medidas');
    });
});

// ========================================
// RUTAS PARA ESTADOS DE COTIZACIONES
// ========================================
Route::middleware(['auth', 'verified'])->name('cotizaciones.estado.')->group(function () {
    // Asesor: Enviar cotizacion a contador
    Route::post('/cotizaciones/{cotizacion}/enviar', [CotizacionEstadoController::class, 'enviar'])->name('enviar');
    
    // Contador: Aprobar cotizacion
    Route::post('/cotizaciones/{cotizacion}/aprobar-contador', [CotizacionEstadoController::class, 'aprobarContador'])->name('aprobar-contador');
    
    // Contador: Aprobar cotizacion para pedido (APROBADA_COTIZACIONES -> APROBADO_PARA_PEDIDO)
    Route::post('/cotizaciones/{cotizacion}/aprobar-para-pedido', [CotizacionEstadoController::class, 'aprobarParaPedido'])->name('aprobar-para-pedido');
    
    // Aprobador de Cotizaciones: Aprobar cotizacion
    Route::post('/cotizaciones/{cotizacion}/aprobar-aprobador', [CotizacionEstadoController::class, 'aprobarAprobador'])->name('aprobar-aprobador');
    
    // Aprobador de Cotizaciones: Rechazar y enviar a correccion
    Route::post('/cotizaciones/{cotizacion}/rechazar', [CotizacionEstadoController::class, 'rechazar'])->name('rechazar');
    
    // Ver historial de cambios
    Route::get('/cotizaciones/{cotizacion}/historial', [CotizacionEstadoController::class, 'historial'])->name('historial');
    
    // Ver seguimiento de cotizacion
    Route::get('/cotizaciones/{cotizacion}/seguimiento', [CotizacionEstadoController::class, 'seguimiento'])->name('seguimiento');
});

// ========================================
// API ROUTES - COTIZACIONES (DDD)
// ========================================
// NOTA: Las rutas específicas ya están definidas en las secciones anteriores
// No usar apiResource aquí para evitar conflictos con rutas explícitas

// Datos especificos para prendas en cotizaciones
Route::prefix('cotizacion')->name('cotizacion.')->middleware(['auth'])->group(function () {
    
    // Guardar prenda nueva en cotizacion
    Route::post('prendas', [\App\Infrastructure\Http\Controllers\CotizacionPrendaController::class, 'guardarPrenda'])
        ->name('prendas.guardar');
    
    // Obtener prendas de una cotizacion
    Route::get('{cotizacionId}/prendas', [\App\Infrastructure\Http\Controllers\CotizacionPrendaController::class, 'obtenerPrendas'])
        ->name('prendas.obtener');
    
    // Eliminar una prenda de cotizacion
    Route::delete('prendas/{prendaId}', [\App\Infrastructure\Http\Controllers\CotizacionPrendaController::class, 'eliminarPrenda'])
        ->name('prendas.eliminar');
});
