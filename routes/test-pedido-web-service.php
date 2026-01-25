// Rutas para TEST de PedidoWebService

// En archivo: routes/api.php o routes/web.php

Route::prefix('test')->group(function () {
    Route::get('/pedido-web-service/instancia', 'TestPedidoWebServiceController@testInstancia');
    Route::post('/pedido-web-service/crear-minimo', 'TestPedidoWebServiceController@testCrearPedidoMinimo');
    Route::get('/pedido-web-service/modelos', 'TestPedidoWebServiceController@testModelos');
});

/**
 * INSTRUCCIONES DE USO:
 * 
 * 1. Copiar estas rutas a tu archivo de rutas (web.php o api.php)
 * 
 * 2. Ejecutar en navegador o Postman:
 *    - GET  http://localhost/test/pedido-web-service/instancia
 *    - GET  http://localhost/test/pedido-web-service/modelos
 *    - POST http://localhost/test/pedido-web-service/crear-minimo
 * 
 * 3. Si todo funciona, verás:
 *    - Instancia: "success" con clase del service
 *    - Modelos: Lista de 12 modelos con status "ok"
 *    - Crear: Nuevo pedido con numero_pedido generado
 */
