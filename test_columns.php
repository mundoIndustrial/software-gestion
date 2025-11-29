<?php

// Test simple para verificar columnas

$host = 'localhost';
$db = 'mundoindustrial';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die('❌ Error de conexión: ' . $conn->connect_error);
    }
    
    // Consultar columnas
    $result = $conn->query("DESCRIBE materiales_orden_insumos");
    
    if (!$result) {
        die('❌ Error en query: ' . $conn->error);
    }
    
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  VERIFICACIÓN DE COLUMNAS - materiales_orden_insumos           ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    $nuevasColumnas = ['fecha_orden', 'fecha_pago', 'fecha_despacho', 'observaciones', 'dias_demora'];
    $encontradas = [];
    
    while ($row = $result->fetch_assoc()) {
        $field = $row['Field'];
        
        if (in_array($field, $nuevasColumnas)) {
            $encontradas[] = $field;
            echo "✅ " . str_pad($field, 25) . " | Tipo: " . str_pad($row['Type'], 15) . " | Nulo: " . ($row['Null'] === 'YES' ? 'SÍ' : 'NO') . "\n";
        }
    }
    
    echo "\n" . str_repeat("─", 66) . "\n";
    echo "📊 RESUMEN:\n";
    echo "   Columnas encontradas: " . count($encontradas) . " / " . count($nuevasColumnas) . "\n\n";
    
    if (count($encontradas) === count($nuevasColumnas)) {
        echo "✅ ¡TODAS LAS COLUMNAS SE CREARON CORRECTAMENTE!\n\n";
        echo "📋 COLUMNAS CREADAS:\n";
        foreach ($encontradas as $col) {
            echo "   ✅ " . $col . "\n";
        }
    } else {
        echo "⚠️  Columnas faltantes:\n";
        foreach ($nuevasColumnas as $col) {
            if (!in_array($col, $encontradas)) {
                echo "   ❌ " . $col . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("═", 66) . "\n";
    echo "✅ Verificación completada\n";
    echo str_repeat("═", 66) . "\n\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
