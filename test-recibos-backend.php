<?php

require 'vendor/autoload.php';
use App\Http\Controllers\Api_temp\PedidoController;

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$app->make('Illuminate\Contracts\Console\Kernel');

// Obtener el controlador
$controller = app(PedidoController::class);

// Simular la solicitud
$response = $controller->obtenerDetalleCompleto(45808, false);

// Obtener datos
$data = json_decode($response->getContent(), true);

echo "=== RESPUESTA DE obtenerDetalleCompleto(45808) ===\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Mostrar estructura
if (isset($data['data'])) {
    echo "=== CAMPOS EN data ===\n";
    echo json_encode(array_keys($data['data']), JSON_PRETTY_PRINT);
    echo "\n\n";
    
    // Mostrar solo los campos principales
    echo "=== VALORES PRINCIPALES (primeros 10 campos) ===\n";
    $count = 0;
    foreach ($data['data'] as $key => $value) {
        if ($count >= 10) break;
        if (is_array($value) || is_object($value)) {
            echo "$key: [" . (is_array($value) ? "array, " . count($value) . " items" : "object") . "]\n";
        } else {
            echo "$key: " . (is_null($value) ? "null" : $value) . "\n";
        }
        $count++;
    }
}
