<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE COTIZACIONES ===\n\n";

// Verificar estructura
echo "1️⃣  Estructura de tabla 'cotizaciones':\n";
$columns = DB::select("DESCRIBE cotizaciones");
foreach ($columns as $col) {
    if (strpos($col->Field, 'numero') !== false) {
        echo "  - {$col->Field}: {$col->Type}";
        if ($col->Key === 'UNI') echo " [UNIQUE]";
        echo "\n";
    }
}

echo "\n2️⃣  Total de cotizaciones: ";
$count = DB::table('cotizaciones')->count();
echo $count . "\n\n";

echo "3️⃣  Números de cotización (primeros 20):\n";
$cotizaciones = DB::table('cotizaciones')
    ->select('id', 'numero_cotizacion', 'fecha_inicio', 'estado')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($cotizaciones as $cot) {
    echo "  ID: {$cot->id} | Num: {$cot->numero_cotizacion} | Estado: {$cot->estado}\n";
}

echo "\n4️⃣  Verificar duplicados:\n";
$duplicados = DB::table('cotizaciones')
    ->select('numero_cotizacion', DB::raw('COUNT(*) as cantidad'))
    ->groupBy('numero_cotizacion')
    ->having(DB::raw('COUNT(*)'), '>', 1)
    ->get();

if ($duplicados->count() > 0) {
    echo "  ⚠️  ENCONTRADOS DUPLICADOS:\n";
    foreach ($duplicados as $dup) {
        echo "    - {$dup->numero_cotizacion}: {$dup->cantidad} veces\n";
    }
} else {
    echo "  ✅ No hay duplicados\n";
}

echo "\n5️⃣  Último número de cotización:\n";
$ultima = DB::table('cotizaciones')
    ->select('numero_cotizacion')
    ->orderBy('id', 'desc')
    ->first();

if ($ultima) {
    echo "  {$ultima->numero_cotizacion}\n";
} else {
    echo "  No hay cotizaciones\n";
}

echo "\n6️⃣  COT-00001 existe?\n";
$existe = DB::table('cotizaciones')
    ->where('numero_cotizacion', 'COT-00001')
    ->count();

if ($existe > 0) {
    echo "  ✅ Sí existe (aparece $existe vez/veces)\n";
    $cot = DB::table('cotizaciones')
        ->where('numero_cotizacion', 'COT-00001')
        ->first();
    echo "  ID: {$cot->id} | Estado: {$cot->estado} | Fecha: {$cot->fecha_inicio}\n";
} else {
    echo "  ❌ No existe\n";
}
?>
