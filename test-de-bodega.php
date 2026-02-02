#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

echo "\n🔍 VERIFICANDO CAMPO de_bodega EN PRENDAS\n";
echo "===========================================\n\n";

// Test 1: Consultar una prenda y verificar que de_bodega existe
$prenda = PrendaPedido::first();

if ($prenda) {
    echo "✅ Prenda encontrada: {$prenda->nombre_prenda} (ID: {$prenda->id})\n\n";
    
    // Verificar atributo directo
    echo "📦 Atributos de la prenda:\n";
    echo "  - nombre_prenda: {$prenda->nombre_prenda}\n";
    echo "  - de_bodega: " . var_export($prenda->de_bodega, true) . " (tipo: " . gettype($prenda->de_bodega) . ")\n";
    echo "  - descripcion: {$prenda->descripcion}\n\n";
    
    // Verificar en array
    $array = $prenda->toArray();
    echo "📋 Array de la prenda:\n";
    echo json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Verificar claves
    echo "🔑 Claves disponibles en el array:\n";
    foreach (array_keys($array) as $key) {
        echo "  - $key\n";
    }
    
    if (!isset($array['de_bodega'])) {
        echo "\n⚠️  PROBLEMA: El campo 'de_bodega' NO está en el array!\n";
    } else {
        echo "\n✅ El campo 'de_bodega' está presente en el array: " . var_export($array['de_bodega'], true) . "\n";
    }
    
    // Verificar en la consulta SQL directa
    echo "\n📊 Verificando columnas en la tabla prendas_pedido:\n";
    $columns = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'prendas_pedido' AND TABLE_SCHEMA = DATABASE()");
    foreach ($columns as $col) {
        echo "  - {$col->COLUMN_NAME} ({$col->DATA_TYPE})\n";
    }
} else {
    echo "❌ No hay prendas en la base de datos\n";
}

echo "\n";
