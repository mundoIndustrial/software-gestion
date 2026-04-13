<?php
/**
 * Script simple de debug - usa Artisan
 */

// Bootstrap Laravel minimalmente
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Iniciar la BD
try {
    $db = $app['db'];
    
    $pedidoId = 176;
    $prendaId = 161;
    
    echo "=== CHECK TALLAS Y VARIANTES ===\n";
    echo "Pedido: $pedidoId, Prenda: $prendaId\n\n";
    
    // Query 1: Tallas en prenda_pedido_tallas
    echo "--- TALLAS EN BD (prenda_pedido_tallas) ---\n";
    $tallas = $db->table('prenda_pedido_tallas')
        ->where('prenda_pedido_id', $prendaId)
        ->get();
    
    echo "Total tallas: " . count($tallas) . "\n";
    if (count($tallas) > 0) {
        foreach ($tallas as $talla) {
            echo "  - Genero: " . $talla->genero . ", Talla: " . $talla->talla . ", Cantidad: " . $talla->cantidad . "\n";
        }
    } else {
        echo "  ❌ No hay tallas\n";
    }
    
    echo "\n--- VARIANTES GUARDADOS EN BD (prenda_pedido_variantes) ---\n";
    $variantes = $db->table('prenda_pedido_variantes')
        ->where('prenda_pedido_id', $prendaId)
        ->get();
    
    echo "Total variantes: " . count($variantes) . "\n";
    if (count($variantes) > 0) {
        foreach ($variantes as $var) {
            echo "  - Talla: " . $var->talla . ", Cantidad: " . $var->cantidad . ", Genero: " . ($var->genero ?? 'N/A') . "\n";
        }
    } else {
        echo "  ❌ No hay variantes\n";
    }
    
    echo "\n--- DIAGNOSTICO ---\n";
    if (count($tallas) > 0) {
        $totalTallas = array_sum(array_column((array)$tallas, 'cantidad'));
        $totalVariantes = array_sum(array_column((array)$variantes, 'cantidad'));
        echo "Total cantidad en tallas: $totalTallas\n";
        echo "Total cantidad en variantes: $totalVariantes\n";
        
        if ($totalVariantes > $totalTallas) {
            echo "⚠️ PROBLEMA: Variantes tiene más cantidad que tallas (sumadas)\n";
        } elseif ($totalVariantes === $totalTallas && count($variantes) === 1) {
            echo "⚠️ PROBLEMA: Variantes está sumado en UN SOLO REGISTRO\n";
            echo "  Debería haber " . count($tallas) . " registros, hay " . count($variantes) . "\n";
        } else {
            echo "✅ OK: Cantidades coinciden\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
