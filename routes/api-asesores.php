<?php

use App\Infrastructure\Http\Controllers\Asesores\AsesoresDashboardController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresNotificacionesController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosCommandController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosQueryController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresRealtimePedidosController;
use App\Infrastructure\Http\Controllers\Asesores\EppsPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\ObtenerPrendasAutocompleteController;
use App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController;
use App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\TelasColoresApiController;
use App\Infrastructure\Http\Controllers\Asesores\VariantesPrendaController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:web', 'role:asesor,admin,supervisor_pedidos'])
    ->prefix('asesores')
    ->name('api.asesores.')
    ->group(function () {
        Route::get('/dashboard-data', [AsesoresDashboardController::class, 'getDashboardData'])
            ->name('dashboard-data');

        Route::get('/pendientes-asesor', [AsesoresPedidosQueryController::class, 'obtenerPendientesAsesor'])
            ->name('pendientes.index');
        Route::get('/conteo-pendientes-asesor', [AsesoresPedidosQueryController::class, 'contarPendientesAsesor'])
            ->name('pendientes.count');
        Route::get('/pendientes/{id}/notas', [AsesoresPedidosQueryController::class, 'obtenerNotasPedido'])
            ->whereNumber('id')
            ->name('pendientes.notas');
        Route::get('/pedidos/next-pedido', [AsesoresPedidosQueryController::class, 'getNextPedido'])
            ->name('pedidos.next');
        Route::get('/pedidos/{id}/editar-datos', [PedidoQueryController::class, 'obtenerDatosEdicion'])
            ->whereNumber('id')
            ->name('pedidos.editar-datos');
        Route::get('/pedidos/{id}/recibos-datos', [ReciboController::class, 'datos'])
            ->whereNumber('id')
            ->name('pedidos.recibos-datos');
        Route::get('/pedidos/listar', [AsesoresPedidosQueryController::class, 'apiListar'])
            ->name('pedidos.listar');
        Route::get('/pedidos-api-listar', [AsesoresPedidosQueryController::class, 'apiListar'])
            ->name('pedidos.api-listar');
        Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', [PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])
            ->whereNumber('pedidoId')
            ->whereNumber('prendaId')
            ->name('pedidos-produccion.prenda-datos');
        Route::get('/pedidos-produccion/{pedidoId}/datos-edicion', [PrendasPedidoController::class, 'obtenerDatosEdicion'])
            ->whereNumber('pedidoId')
            ->name('pedidos-produccion.datos-edicion');

        Route::delete('/pedidos/{id}', [AsesoresPedidosCommandController::class, 'destroy'])
            ->whereNumber('id')
            ->name('pedidos.destroy');
        Route::post('/pedidos/{pedidoId}/agregar-prenda-simple', [AsesoresPedidosCommandController::class, 'agregarPrendaSimple'])
            ->whereNumber('pedidoId')
            ->name('pedidos.agregar-prenda-simple');
        Route::post('/pedidos/{id}/agregar-prenda', [PrendasPedidoController::class, 'agregarPrendaCompleta'])
            ->whereNumber('id')
            ->name('pedidos.agregar-prenda');
        Route::post('/pedidos/{id}/actualizar-prenda', [PrendasPedidoController::class, 'actualizarPrendaCompleta'])
            ->whereNumber('id')
            ->name('pedidos.actualizar-prenda');
        Route::post('/pedidos/{id}/eliminar-prenda', [PrendasPedidoController::class, 'eliminarPrenda'])
            ->whereNumber('id')
            ->name('pedidos.eliminar-prenda');
        Route::post('/pedidos/{id}/eliminar-epp', [EppsPedidoController::class, 'eliminarEpp'])
            ->whereNumber('id')
            ->name('pedidos.eliminar-epp');
        Route::post('/pedidos/{id}/homologar-epp', [EppsPedidoController::class, 'homologarEpp'])
            ->whereNumber('id')
            ->name('pedidos.homologar-epp');
        Route::put('/pedidos/{pedidoId}/prendas/{prendaId}/variante', [VariantesPrendaController::class, 'actualizarVariantePrend'])
            ->whereNumber('pedidoId')
            ->whereNumber('prendaId')
            ->name('pedidos.actualizar-variante-prenda');
        Route::get('/telas', [TelasColoresApiController::class, 'getTelas'])
            ->name('catalogos.telas');
        Route::get('/colores', [TelasColoresApiController::class, 'getColores'])
            ->name('catalogos.colores');
        Route::get('/prendas/autocomplete', [ObtenerPrendasAutocompleteController::class, 'obtenerPrendas'])
            ->name('prendas.autocomplete');

        Route::get('/notificaciones', [AsesoresNotificacionesController::class, 'getNotifications'])
            ->name('notificaciones.index');
        Route::post('/notificaciones/marcar-todas-leidas', [AsesoresNotificacionesController::class, 'markAllAsRead'])
            ->name('notificaciones.mark-all-read');
        Route::post('/notificaciones/{notificationId}/marcar-leida', [AsesoresNotificacionesController::class, 'markNotificationAsRead'])
            ->whereNumber('notificationId')
            ->name('notificaciones.mark-read');

        Route::post('/pedidos/observaciones-despacho/resumen', [ObservacionesDespachoController::class, 'resumen'])
            ->name('observaciones.resumen');
        Route::get('/pedidos/{id}/observaciones-despacho', [ObservacionesDespachoController::class, 'obtener'])
            ->whereNumber('id')
            ->name('observaciones.obtener');
        Route::post('/pedidos/{id}/observaciones-despacho', [ObservacionesDespachoController::class, 'guardar'])
            ->whereNumber('id')
            ->name('observaciones.guardar');
        Route::put('/pedidos/{id}/observaciones-despacho/{observacionId}', [ObservacionesDespachoController::class, 'actualizar'])
            ->whereNumber('id')
            ->name('observaciones.actualizar');
        Route::delete('/pedidos/{id}/observaciones-despacho/{observacionId}', [ObservacionesDespachoController::class, 'eliminar'])
            ->whereNumber('id')
            ->name('observaciones.eliminar');
        Route::post('/pedidos/{id}/observaciones-despacho/marcar-leidas', [ObservacionesDespachoController::class, 'marcarLeidas'])
            ->whereNumber('id')
            ->name('observaciones.marcar-leidas');
        Route::post('/pedidos/{id}/observaciones-despacho/marcar-bodega-vistas', [ObservacionesDespachoController::class, 'marcarBodegaVistas'])
            ->whereNumber('id')
            ->name('observaciones.marcar-bodega-vistas');

        Route::get('/realtime/pedidos', [AsesoresRealtimePedidosController::class, 'listar'])
            ->name('realtime.pedidos.listar');
    });
