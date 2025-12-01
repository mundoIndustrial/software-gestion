<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel');

try {
    echo "\n=== DIAGNÓSTICO DE BODEGA ===\n\n";
    
    // Obtener conexión directa
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );
    
    // Test 1: Contar registros en tabla_original_bodega
    $result1 = $pdo->query("SELECT COUNT(*) as count FROM tabla_original_bodega");
    $count1 = $result1->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ tabla_original_bodega: $count1 registros\n";
    
    // Test 2: Si hay registros, mostrar muestra
    if ($count1 > 0) {
        echo "\n✓ PRIMEROS 3 REGISTROS:\n";
        $result = $pdo->query("SELECT pedido, cliente, estado, area FROM tabla_original_bodega LIMIT 3");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "  - Pedido: {$row['pedido']}, Cliente: {$row['cliente']}, Estado: {$row['estado']}, Area: {$row['area']}\n";
        }
    } else {
        echo "⚠️  tabla_original_bodega ESTÁ VACÍA\n";
        
        // Verificar tabla_original
        $result2 = $pdo->query("SELECT COUNT(*) as count FROM tabla_original");
        $count2 = $result2->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n✓ tabla_original: $count2 registros\n";
        
        if ($count2 > 0) {
            echo "\n✓ PRIMEROS 3 REGISTROS DE tabla_original:\n";
            $result = $pdo->query("SELECT pedido, cliente, estado, area FROM tabla_original LIMIT 3");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                echo "  - Pedido: {$row['pedido']}, Cliente: {$row['cliente']}, Estado: {$row['estado']}, Area: {$row['area']}\n";
            }
            
            echo "\n📌 NECESARIO: Copiar datos de tabla_original a tabla_original_bodega\n";
        }
    }
    
    // Test 3: Verificar estructura
    echo "\n=== ESTRUCTURA DE tabla_original_bodega ===\n";
    $result = $pdo->query("SHOW COLUMNS FROM tabla_original_bodega");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Total columnas: " . count($columns) . "\n";
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
