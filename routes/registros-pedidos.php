<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\RegistroOrdenController;
use App\Infrastructure\Http\Controllers\RegistroOrdenQueryController;
use App\Infrastructure\Http\Controllers\Bodega\RegistroBodegaController;
use App\Infrastructure\Http\Controllers\Bodega\ReciboCorteBodegaController;
use App\Infrastructure\Http\Controllers\Asesores\ProcesosPedidoController;
use App\Infrastructure\Http\Controllers\Facturacion\InvoiceController;

// ========================================
// RUTAS PARA REGISTROS Y PEDIDOS
// Consolidation de RegistroOrdenController y RegistroBodegaController
// ========================================
Route::middleware(['auth', 'supervisor-readonly'])->group(function () {
    
    // ========================================
    // QUERY/SEARCH ROUTES (RegistroOrdenQueryController)
    // ========================================
    Route::get('/registros', [RegistroOrdenQueryController::class, 'index'])->name('registros.index');
    
    // ========================================
    // GET ROUTES (Sin parametro primero)
    // ========================================
    Route::get('/registros/next-pedido', [RegistroOrdenController::class, 'getNextPedido'])->name('registros.next-pedido');
    Route::get('/registros/filter-options', [RegistroOrdenController::class, 'getFilterOptions'])->name('registros.filter-options');
    Route::get('/registros/filter-column-options/{column}', [RegistroOrdenController::class, 'getColumnFilterOptions'])->name('registros.filter-column-options');
    Route::get('/recibos-costura', [RegistroOrdenController::class, 'recibosCostura'])->name('registros.recibos-costura');
    Route::get('/recibos-costura/search', [RegistroOrdenController::class, 'searchRecibosCostura'])->name('registros.recibos-costura-search');
    Route::get('/recibos-bodega', [RegistroOrdenController::class, 'recibosBodega'])->name('registros.recibos-bodega');
    Route::get('/recibos-reflectivo', [RegistroOrdenController::class, 'recibosReflectivo'])->name('registros.recibos-reflectivo');
    Route::get('/recibos-bordado-estampado', [RegistroOrdenController::class, 'recibosBordadoEstampado'])->name('registros.recibos-bordado-estampado');
    Route::get('/recibos-bordado-estampado/search', [RegistroOrdenController::class, 'searchRecibosBordadoEstampado'])->name('registros.recibos-bordado-estampado-search');
    Route::get('/recibos-reflectivo/search', [RegistroOrdenController::class, 'searchRecibosReflectivo'])->name('registros.recibos-reflectivo-search');
    Route::match(['get', 'post'], '/recibos-logo/reporte', [RegistroOrdenController::class, 'reporteRecibosLogo'])->name('recibos-logo.reporte');
    Route::match(['get', 'post'], '/recibos-reflectivo/reporte', [RegistroOrdenController::class, 'reporteRecibosReflectivo'])->name('recibos-reflectivo.reporte');
    Route::get('/bodega/next-pedido', [RegistroBodegaController::class, 'getNextPedido'])->name('bodega.next-pedido');
    Route::get('/bodega', [RegistroBodegaController::class, 'index'])->name('bodega.index');
    
    // ========================================
    // POST ROUTES (Filter y Search)
    // ========================================
    Route::post('/registros/filter-orders', [RegistroOrdenController::class, 'filterOrders'])->name('registros.filter-orders');
    Route::post('/registros/search', [RegistroOrdenController::class, 'searchOrders'])->name('registros.search');
    Route::post('/bodega/search', [RegistroBodegaController::class, 'searchOrders'])->name('bodega.search');
    
    // ========================================
    // RECIBOS ROUTES
    // ========================================
    Route::get('/recibos-costura/recibo/{reciboId}', [RegistroOrdenController::class, 'getReciboJson'])->name('registros.recibo-json');
    Route::get('/api/recibos-costura/ejecutando-corte', [RegistroOrdenController::class, 'contarRecibosEjecutandoCostura'])->name('api.recibos-costura.ejecutando-corte');
    Route::post('/api/recibos-costura/{id}/marcar-visto-corte', [RegistroOrdenController::class, 'marcarReciboVistoCostura'])->name('api.recibos-costura.marcar-visto-corte');
    Route::post('/api/recibos-bordado-estampado/toggle-check-logo', [RegistroOrdenController::class, 'toggleCheckReciboLogo'])->name('api.recibos-bordado-estampado.toggle-check-logo');
    Route::get('/recibos-reflectivo/recibo/{reciboId}', [RegistroOrdenController::class, 'getReciboReflectivoJson'])->name('registros.recibo-reflectivo-json');
    
    // ========================================
    // API ROUTES
    // ========================================
    Route::get('/registros/{id}/recibos-datos', [RegistroOrdenQueryController::class, 'getRecibosDatos'])->name('registros.recibos-datos');
    Route::get('/registros/{id}/consecutivo-costura', [RegistroOrdenQueryController::class, 'getConsecutivoCostura'])->name('registros.consecutivo-costura');
    Route::get('/registros/{id}/seguimiento-prenda', [RegistroOrdenQueryController::class, 'getSeguimientoPorPrenda'])->name('registros.seguimiento-prenda');
    Route::get('/registros/{id}/novedades', [RegistroOrdenQueryController::class, 'getNovedades'])->name('registros.novedades');
    Route::get('/api/pedido/{id}/area-reciente', [RegistroOrdenController::class, 'getAreaReciente'])->name('api.pedido.area-reciente');
    Route::post('/api/registros/dias-batch', [RegistroOrdenQueryController::class, 'calcularDiasBatchAPI'])->name('api.registros.dias-batch');
    Route::post('/api/registros/{id}/calcular-fecha-estimada', [RegistroOrdenQueryController::class, 'calcularFechaEstimada'])->name('api.registros.calcular-fecha-estimada');
    Route::post('/api/registros/calcular-fecha-estimada-preview', [RegistroOrdenQueryController::class, 'calcularFechaEstimadaPreview'])->name('api.registros.calcular-fecha-estimada-preview');
    Route::get('/api/bodega/{numero_pedido}/dias', [RegistroBodegaController::class, 'calcularDiasAPI'])->name('api.bodega.dias');
    Route::get('/api/registros/{numero_pedido}/dias', [RegistroOrdenQueryController::class, 'calcularDiasAPI'])->name('api.registros.dias');
    Route::get('/api/tabla-original/{numeroPedido}/procesos', [RegistroOrdenController::class, 'getProcesosTablaOriginal'])->name('api.tabla-original.procesos');
    Route::get('/api/tabla-original-bodega/{numeroPedido}/procesos', [RegistroBodegaController::class, 'getProcesosTablaOriginal'])->name('api.tabla-original-bodega.procesos');
    Route::get('/api/registros-por-orden/{pedido}', [RegistroOrdenController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden');
    Route::get('/api/registros-por-orden-bodega/{pedido}', [RegistroBodegaController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden-bodega');
    Route::get('/api/ordenes/{id}/procesos', [ProcesosPedidoController::class, 'getProcesos'])->name('api.ordenes.procesos');
    Route::get('/api/recibos-bodega/{numeroRecibo}/procesos', [ProcesosPedidoController::class, 'getProcesosPorNumeroReciboBodega'])->name('api.recibos-bodega.procesos');
    Route::get('/api/recibos-bodega/{numeroRecibo}/distribucion', [ProcesosPedidoController::class, 'obtenerDistribucionReciboBodega'])->name('api.recibos-bodega.distribucion');
    Route::post('/api/ordenes/{numero_pedido}/novedades', [RegistroOrdenController::class, 'updateNovedades'])->name('api.ordenes.novedades');
    Route::post('/api/ordenes/{numero_pedido}/novedades/add', [RegistroOrdenController::class, 'addNovedad'])->name('api.ordenes.novedades.add');
    Route::post('/api/bodega/{pedido}/novedades', [RegistroBodegaController::class, 'updateNovedadesBodega'])->name('api.bodega.novedades');
    Route::post('/api/bodega/{pedido}/novedades/add', [RegistroBodegaController::class, 'addNovedadBodega'])->name('api.bodega.novedades.add');

    // ========================================
    // RECIBO CORTE BODEGA ROUTES
    // ========================================
    Route::post('/api/recibo-corte-bodega', [ReciboCorteBodegaController::class, 'store'])->name('api.recibo-corte-bodega.store');
    Route::get('/api/recibo-corte-bodega', [ReciboCorteBodegaController::class, 'index'])->name('api.recibo-corte-bodega.index');
    Route::get('/api/recibo-corte-bodega/parcial/{id}', [ReciboCorteBodegaController::class, 'showParcial'])->name('api.recibo-corte-bodega.parcial.show');
    Route::get('/api/recibo-corte-bodega/{id}', [ReciboCorteBodegaController::class, 'show'])->name('api.recibo-corte-bodega.show');
    Route::put('/api/recibos-bodega/procesos/{id}/encargado', [ProcesosPedidoController::class, 'actualizarEncargadoBodega'])->name('api.recibos-bodega.procesos.encargado');

    Route::put('/api/procesos/{id}/editar', [ProcesosPedidoController::class, 'editarProceso'])->name('api.procesos.editar');
    Route::delete('/api/procesos/{id}/eliminar', [ProcesosPedidoController::class, 'eliminarProceso'])->name('api.procesos.eliminar');
    
    // ========================================
    // GET ROUTES (Con parametro)
    // ========================================
    Route::get('/registros/{pedido}', [RegistroOrdenQueryController::class, 'show'])->name('registros.show');
    Route::get('/registros/{pedido}/images', [RegistroOrdenQueryController::class, 'getOrderImages'])->name('registros.images');
    Route::get('/registros/{pedido}/descripcion-prendas', [RegistroOrdenQueryController::class, 'getDescripcionPrendas'])->name('registros.descripcion-prendas');
    Route::get('/registros/{pedido}/entregas', [RegistroOrdenController::class, 'getEntregas'])->name('registros.entregas');
    Route::get('/bodega/{pedido}', [RegistroBodegaController::class, 'show'])->name('bodega.show');
    Route::get('/bodega/{pedido}/prendas', [RegistroBodegaController::class, 'getPrendas'])->name('bodega.prendas');
    Route::get('/bodega/{pedido}/entregas', [RegistroBodegaController::class, 'getEntregas'])->name('bodega.entregas');
    Route::get('/orders/{numero_pedido}', [RegistroOrdenController::class, 'show'])->name('orders.show');
    
    // ========================================
    // INVOICES ROUTES
    // ========================================
    Route::get('/facturas/{numeroPedido}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/facturas/{numeroPedido}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/facturas/{numeroPedido}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    
    // ========================================
    // POST ROUTES (CRUD Operations)
    // ========================================
    // NOTA: La creacion de pedidos se centraliza en /asesores/pedidos/*
    Route::post('/registros/validate-pedido', [RegistroOrdenController::class, 'validatePedido'])->name('registros.validatePedido');
    Route::post('/registros/update-pedido', [RegistroOrdenController::class, 'updatePedido'])->name('registros.updatePedido');
    Route::post('/registros/update-descripcion-prendas', [RegistroOrdenController::class, 'updateDescripcionPrendas'])->name('registros.updateDescripcionPrendas');
    Route::post('/registros/update-status', [RegistroOrdenController::class, 'updateStatus'])->name('registros.updateStatus');
    Route::post('/registros/{pedido}/dia-entrega', [RegistroOrdenController::class, 'saveDiaEntrega'])->name('registros.saveDiaEntrega');
    Route::post('/registros/{pedido}/edit-full', [RegistroOrdenController::class, 'editFullOrder'])->name('registros.editFull');
    Route::post('/bodega', [RegistroBodegaController::class, 'store'])->name('bodega.store');
    Route::post('/bodega/validate-pedido', [RegistroBodegaController::class, 'validatePedido'])->name('bodega.validatePedido');
    Route::post('/bodega/update-pedido', [RegistroBodegaController::class, 'updatePedido'])->name('bodega.updatePedido');
    Route::post('/bodega/update-descripcion-prendas', [RegistroBodegaController::class, 'updateDescripcionPrendas'])->name('bodega.updateDescripcionPrendas');
    Route::post('/bodega/{pedido}/edit-full', [RegistroBodegaController::class, 'editFullOrder'])->name('bodega.editFull');
    
    // ========================================
    // PATCH ROUTES (Update operations)
    // ========================================
    Route::patch('/registros/{pedido}', [RegistroOrdenController::class, 'update'])->name('registros.update');
    Route::patch('/bodega/{pedido}', [RegistroBodegaController::class, 'update'])->name('bodega.update');
    
    // ========================================
    // DELETE ROUTES
    // ========================================
    Route::delete('/registros/{pedido}', [RegistroOrdenController::class, 'destroy'])->name('registros.destroy');
});
