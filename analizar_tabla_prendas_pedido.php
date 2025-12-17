<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANÁLISIS DE TABLA prendas_pedido ===\n\n";

// 1. Verificar estructura de la tabla
echo "1️⃣  ESTRUCTURA DE LA TABLA:\n";
$columns = DB::select("SHOW COLUMNS FROM prendas_pedido");
echo json_encode($columns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 2. Contar registros
$count = DB::table('prendas_pedido')->count();
echo "2️⃣  TOTAL DE REGISTROS: $count\n\n";

// 3. Ver último registro
echo "3️⃣  ÚLTIMO REGISTRO:\n";
$ultimoPrenda = DB::table('prendas_pedido')
    ->latest('id')
    ->first();
    
if ($ultimoPrenda) {
    echo json_encode($ultimoPrenda, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 4. Verificar qué campos tienen contenido
    echo "4️⃣  CAMPOS CON CONTENIDO:\n";
    foreach ((array)$ultimoPrenda as $field => $value) {
        if (!empty($value)) {
            echo "   ✓ $field: " . (is_string($value) && strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) . "\n";
        } else {
            echo "   ✗ $field: (VACÍO)\n";
        }
    }
    echo "\n";
    
    // 5. Verificar si existe el campo 'descripcion_prendas'
    echo "5️⃣  BÚSQUEDA DE CAMPO 'descripcion_prendas':\n";
    $hasDescripcionPrendas = false;
    foreach ((array)$ultimoPrenda as $field => $value) {
        if (strtolower($field) === 'descripcion_prendas') {
            $hasDescripcionPrendas = true;
            echo "   ✓ Campo 'descripcion_prendas' existe: " . (!empty($value) ? "CON DATOS" : "VACÍO") . "\n";
            echo "   Contenido: " . (strlen($value) > 200 ? substr($value, 0, 200) . '...' : $value) . "\n";
        }
    }
    if (!$hasDescripcionPrendas) {
        echo "   ✗ Campo 'descripcion_prendas' NO EXISTE\n";
    }
    echo "\n";
    
    // 6. Verificar el campo 'descripcion'
    echo "6️⃣  BÚSQUEDA DE CAMPO 'descripcion':\n";
    if (isset($ultimoPrenda->descripcion)) {
        echo "   ✓ Campo 'descripcion' existe: " . (!empty($ultimoPrenda->descripcion) ? "CON DATOS" : "VACÍO") . "\n";
        if (!empty($ultimoPrenda->descripcion)) {
            echo "   Primeros 300 caracteres:\n";
            echo "   " . substr($ultimoPrenda->descripcion, 0, 300) . (strlen($ultimoPrenda->descripcion) > 300 ? '...' : '') . "\n";
        }
    } else {
        echo "   ✗ Campo 'descripcion' NO EXISTE\n";
    }
    echo "\n";
    
} else {
    echo "   No hay registros en la tabla\n";
}

// 7. Listar todos los campos de la tabla
echo "7️⃣  LISTA COMPLETA DE CAMPOS:\n";
foreach ($columns as $col) {
    echo "   - {$col->Field} ({$col->Type})" . ($col->Null === 'NO' ? ' NOT NULL' : '') . "\n";
}
echo "\n";

// 8. Muestreo de datos
echo "8️⃣  MUESTREO DE 5 ÚLTIMOS REGISTROS:\n";
$muestra = DB::table('prendas_pedido')
    ->latest('id')
    ->limit(5)
    ->get();

foreach ($muestra as $idx => $prenda) {
    echo "\n--- Prenda $idx ---\n";
    echo "ID: {$prenda->id}\n";
    echo "Nombre: {$prenda->nombre_prenda}\n";
    echo "Descripción: " . (strlen($prenda->descripcion ?? '') > 100 ? substr($prenda->descripcion, 0, 100) . '...' : $prenda->descripcion) . "\n";
}
