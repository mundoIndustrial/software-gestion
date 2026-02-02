<?php

/**
 * TEST: Buscar CUALQUIER CAMPO que tenga ruta_webp o url
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Buscar todas las URLs de fotos en la respuesta ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

function buscarFotos($data, $path = '') {
    $fotos = [];
    
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $newPath = $path ? "$path.$key" : $key;
            
            if ($key === 'ruta_webp' && is_string($value) && strpos($value, '/storage/') !== false) {
                $fotos[] = $newPath . ': ' . $value;
            } elseif ($key === 'url' && is_string($value) && strpos($value, '/storage/') !== false) {
                $fotos[] = $newPath . ': ' . $value;
            } elseif (is_array($value) || is_object($value)) {
                $fotos = array_merge($fotos, buscarFotos($value, $newPath));
            }
        }
    }
    
    return $fotos;
}

$fotosEncontradas = buscarFotos($data['data']);

echo "Total URLs encontradas: " . count($fotosEncontradas) . "\n\n";

foreach ($fotosEncontradas as $i => $info) {
    echo ($i + 1) . ". " . $info . "\n";
}

echo "\n✅ TEST COMPLETADO\n";
