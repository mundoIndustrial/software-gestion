<?php

/**
 * TEST: Verificar valores de manga, broche, bolsillos
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Verificar campos de manga, broche, bolsillos ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];

echo "PRENDA: {$prenda['nombre']}\n\n";

echo "Campos principales:\n";
echo "- manga: " . ($prenda['manga'] ?? 'NULL') . "\n";
echo "- obs_manga: " . ($prenda['obs_manga'] ?? 'NULL') . "\n";
echo "- broche: " . ($prenda['broche'] ?? 'NULL') . "\n";
echo "- obs_broche: " . ($prenda['obs_broche'] ?? 'NULL') . "\n";
echo "- tiene_bolsillos: " . ($prenda['tiene_bolsillos'] ? 'true' : 'false') . "\n";
echo "- obs_bolsillos: " . ($prenda['obs_bolsillos'] ?? 'NULL') . "\n";

echo "\nVariantes[0]:\n";
if (isset($prenda['variantes'][0])) {
    $var = $prenda['variantes'][0];
    echo "- manga: " . ($var['manga'] ?? 'NULL') . "\n";
    echo "- manga_obs: " . ($var['manga_obs'] ?? 'NULL') . "\n";
    echo "- broche: " . ($var['broche'] ?? 'NULL') . "\n";
    echo "- broche_obs: " . ($var['broche_obs'] ?? 'NULL') . "\n";
    echo "- bolsillos: " . ($var['bolsillos'] ? 'true' : 'false') . "\n";
    echo "- bolsillos_obs: " . ($var['bolsillos_obs'] ?? 'NULL') . "\n";
}

echo "\n✅ TEST COMPLETADO\n";
