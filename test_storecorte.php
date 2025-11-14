<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test directo de storeCorte
$request = new \Illuminate\Http\Request(
    $_GET,
    [
        'fecha' => '2025-11-14',
        'orden_produccion' => 'TEST123',
        'tela_id' => 1,
        'hora_id' => 1,
        'operario_id' => 1,
        'actividad' => 'Corte',
        'maquina_id' => 1,
        'tiempo_ciclo' => 50,
        'porcion_tiempo' => 1.0,
        'cantidad_producida' => 30,
        'paradas_programadas' => 'NINGUNA',
        'paradas_no_programadas' => 'PRUEBA',
        'tiempo_parada_no_programada' => 1800,
        'tipo_extendido' => 'Ninguna',
        'numero_capas' => 0,
        'trazado' => 'NINGUNA',
        'tiempo_trazado' => 0,
    ],
    [],
    [],
    [],
    ['REQUEST_METHOD' => 'POST'],
    json_encode([])
);

$request->setUserResolver(function () {
    return \App\Models\User::find(1);
});

$controller = new \App\Http\Controllers\TablerosController();

echo "\n=== TEST DIRECTO storeCorte ===\n";
$response = $controller->storeCorte($request);
echo "Status: " . $response->status() . "\n";
echo "Data: " . $response->getContent() . "\n";
echo "\n";
