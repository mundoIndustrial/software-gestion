<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNÓSTICO DE COSTURA PENDIENTE ===\n\n";

// 1. Contar registros con area=Costura y estado_bodega=Pendiente
$costura = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->count();
echo "Registros con area=Costura y estado_bodega=Pendiente: " . $costura . "\n\n";

// 2. Ver todas las áreas disponibles
$todosLosAreas = DB::table('bodega_detalles_talla')->distinct('area')->pluck('area');
echo "Áreas disponibles: " . $todosLosAreas->implode(', ') . "\n\n";

// 3. Ver todos los estados disponibles
$todosLosEstados = DB::table('bodega_detalles_talla')->distinct('estado_bodega')->pluck('estado_bodega');
echo "Estados disponibles: " . $todosLosEstados->implode(', ') . "\n\n";

// 4. Mostra un ejemplo de registro
$ejemplo = DB::table('bodega_detalles_talla')->first();
if ($ejemplo) {
    echo "Ejemplo de registro:\n";
    foreach ($ejemplo as $key => $value) {
        echo "  - $key: " . json_encode($value) . "\n";
    }
}

echo "\n";

// 5. Ver distribución por área
echo "Distribución por área:\n";
$distribucion = DB::table('bodega_detalles_talla')
    ->select('area', DB::raw('count(*) as total'))
    ->groupBy('area')
    ->get();
foreach ($distribucion as $row) {
    echo "  - {$row->area}: {$row->total}\n";
}

echo "\n";

// 6. Ver distribución por estado para Costura
echo "Distribución de estados para área=Costura:\n";
$distribucionCostura = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->select('estado_bodega', DB::raw('count(*) as total'))
    ->groupBy('estado_bodega')
    ->get();
foreach ($distribucionCostura as $row) {
    echo "  - {$row->estado_bodega}: {$row->total}\n";
}

echo "\n";

// 7. Ver si hay prendas con de_bodega=true en los pedidos
echo "Verificando prendas con de_bodega=true:\n";
$prendasBodega = DB::table('prendas_pedido')
    ->where('de_bodega', true)
    ->distinct('pedido_produccion_id')
    ->count();
echo "Pedidos con prendas de_bodega=true: " . $prendasBodega . "\n";
