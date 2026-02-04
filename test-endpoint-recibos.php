<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

try {
    echo "=== Probando endpoint /pedidos-public/1/recibos-datos ===\n\n";
    
    // Simular request GET
    $response = Http::get('http://localhost:8000/pedidos-public/1/recibos-datos');
    
    if (!$response->successful()) {
        echo "✗ Error HTTP: " . $response->status() . "\n";
        exit(1);
    }
    
    $data = $response->json();
    
    if (!isset($data['data']['prendas']) || empty($data['data']['prendas'])) {
        echo "✗ No hay prendas en la respuesta\n";
        exit(1);
    }
    
    $prenda = $data['data']['prendas'][0];
    $recibos = $prenda['recibos'] ?? null;
    
    echo "✓ Prendas encontradas: " . count($data['data']['prendas']) . "\n\n";
    echo "Recibos disponibles:\n";
    
    if (is_array($recibos)) {
        foreach ($recibos as $tipo => $consecutivo) {
            $status = $consecutivo !== null ? "✓" : "✗";
            $valor = $consecutivo !== null ? $consecutivo : "null";
            echo "   {$status} {$tipo}: {$valor}\n";
        }
        
        if (isset($recibos['COSTURA-BODEGA'])) {
            echo "\n✅ COSTURA-BODEGA PRESENTE en respuesta\n";
            echo "   Valor: " . ($recibos['COSTURA-BODEGA'] !== null ? $recibos['COSTURA-BODEGA'] : "null") . "\n";
        } else {
            echo "\n✗ COSTURA-BODEGA NO en respuesta\n";
        }
    } else {
        echo "   Recibos: " . json_encode($recibos) . "\n";
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
