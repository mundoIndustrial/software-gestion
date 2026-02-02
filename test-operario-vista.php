<?php

/**
 * TEST: Verificar que /operario/pedido/45807 obtiene datos del API correcto
 * 
 * Simula lo que hace el JavaScript en la página ver-pedido.blade.php
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;

echo "=== TEST: Operario Pedido 45807 ===\n\n";

// Simular llamada al nuevo endpoint
$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);

echo "1️⃣  Llamando a /api/operario/pedido/45807\n";
$response = $controller->getPedidoData(45807);
$statusCode = $response->getStatusCode();
$data = json_decode($response->getContent(), true);

echo "   Status: $statusCode\n";
echo "   Success: " . ($data['success'] ? '✅' : '❌') . "\n\n";

if (!$data['success']) {
    echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$pedido = $data['data'];

echo "2️⃣  Información del Pedido\n";
echo "   - Número: " . ($pedido['numero_pedido'] ?? 'N/A') . "\n";
echo "   - Cliente: " . ($pedido['cliente'] ?? 'N/A') . "\n";
echo "   - Asesor: " . ($pedido['asesor'] ?? 'N/A') . "\n";
echo "   - Total prendas: " . count($pedido['prendas'] ?? []) . "\n\n";

if (!empty($pedido['prendas'])) {
    $prenda = $pedido['prendas'][0];
    echo "3️⃣  Primera Prenda\n";
    echo "   - Nombre: " . ($prenda['nombre'] ?? 'N/A') . "\n";
    echo "   - Telas: " . count($prenda['colores_telas'] ?? []) . "\n";
    echo "   - Tallas: ";
    if (isset($prenda['tallas']) && is_array($prenda['tallas'])) {
        foreach ($prenda['tallas'] as $genero => $tallas) {
            $cantidad = array_sum((array)$tallas);
            echo "$genero ($cantidad), ";
        }
        echo "\n";
    }
    
    echo "   - Variantes: " . count($prenda['variantes'] ?? []) . "\n";
    echo "   - Procesos: " . count($prenda['procesos'] ?? []) . "\n";
    echo "   - Recibos: ";
    if (isset($prenda['recibos'])) {
        $recibosActivos = array_filter($prenda['recibos']);
        echo json_encode($prenda['recibos']) . "\n";
    }
    
    echo "\n4️⃣  Verificar Descripción Formateada\n";
    
    // Simular lo que hace el JavaScript
    $descripcionFormateada = '';
    foreach ($pedido['prendas'] as $idx => $p) {
        if ($idx === 0) {  // Solo primera prenda
            $descripcionFormateada .= "<strong>PRENDA " . ($idx + 1) . ": " . ($p['nombre'] ?? 'N/A') . "</strong><br>";
            
            // Telas
            if (!empty($p['colores_telas'])) {
                $telas = array_map(function($ct) {
                    return ($ct['tela_nombre'] ?? '') . ' / ' . ($ct['color_nombre'] ?? '') . ' | REF: ' . ($ct['referencia'] ?? '');
                }, $p['colores_telas']);
                $descripcionFormateada .= "<strong>TELAS:</strong> " . implode(' | ', $telas) . "<br>";
            }
            
            // Manga
            if ($p['manga']) {
                $descripcionFormateada .= "<strong>MANGA:</strong> " . strtoupper($p['manga']) . "<br>";
            }
            
            // Bolsillos
            if ($p['obs_bolsillos']) {
                $descripcionFormateada .= "• <strong>BOLSILLOS:</strong> " . ($p['obs_bolsillos'] ?? '') . "<br>";
            }
            
            // Broche
            if ($p['obs_broche']) {
                $descripcionFormateada .= "• <strong>BOTÓN/BROCHE:</strong> " . ($p['obs_broche'] ?? '') . "<br>";
            }
            
            // Tallas
            if (!empty($p['tallas'])) {
                $descripcionFormateada .= "<br><strong>TALLAS</strong><br>";
                foreach ($p['tallas'] as $genero => $tallasData) {
                    $tallasCantidades = [];
                    foreach ($tallasData as $talla => $cantidad) {
                        $tallasCantidades[] = $talla . ': <span style="color: red;"><strong>' . $cantidad . '</strong></span>';
                    }
                    if (!empty($tallasCantidades)) {
                        $descripcionFormateada .= strtoupper($genero) . ': ' . implode(', ', $tallasCantidades) . "<br>";
                    }
                }
            }
        }
    }
    
    echo "   Descripción generada:\n";
    echo "   " . str_replace('<br>', "\n   ", strip_tags($descripcionFormateada, '<br><strong><span>')) . "\n";
    
    if (!empty($descripcionFormateada)) {
        echo "\n   ✅ Descripción formateada correctamente\n";
    } else {
        echo "\n   ❌ Descripción vacía\n";
    }
}

echo "\n✅ TEST COMPLETADO\n";
