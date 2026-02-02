<?php

/**
 * TEST: Dump completo de estructura de prendas
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Estructura completa de prendas ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];

echo "Estructura de la prenda:\n";
echo json_encode($prenda, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
