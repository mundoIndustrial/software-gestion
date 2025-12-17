<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANÁLISIS: TABLA prenda_telas_cot ===\n\n";

// 1. Contar registros
$totalTelasCot = DB::table('prenda_telas_cot')->count();
echo "1️⃣  Total registros en prenda_telas_cot: $totalTelasCot\n\n";

// 2. Contar cuántos tienen tela_id
$conTelaId = DB::table('prenda_telas_cot')->whereNotNull('tela_id')->count();
$sinTelaId = DB::table('prenda_telas_cot')->whereNull('tela_id')->count();

echo "2️⃣  ESTADÍSTICAS DE tela_id:\n";
echo "   Con tela_id: $conTelaId\n";
echo "   Sin tela_id: $sinTelaId\n";
echo "   Porcentaje sin tela_id: " . round(($sinTelaId/$totalTelasCot)*100, 2) . "%\n\n";

// 3. Ver últimas 5 prendas de cotización con sus telas
echo "3️⃣  ÚLTIMAS 5 PRENDAS DE COTIZACIÓN CON TELAS:\n";
$prendas = DB::table('prendas_cot')
    ->latest('id')
    ->limit(5)
    ->get();

foreach ($prendas as $prenda) {
    echo "\n--- Prenda COT ID: {$prenda->id} ({$prenda->nombre_producto}) ---\n";
    
    $telas = DB::table('prenda_telas_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    
    echo "Total telas: " . count($telas) . "\n";
    
    foreach ($telas as $tela) {
        echo "  - Tela ID: " . ($tela->tela_id ?? "NULL") . ", Color ID: " . ($tela->color_id ?? "NULL") . "\n";
    }
}

// 4. Ver estructura de prenda_telas_cot
echo "\n\n4️⃣  ESTRUCTURA DE TABLA prenda_telas_cot:\n";
$columns = DB::select("SHOW COLUMNS FROM prenda_telas_cot");
foreach ($columns as $col) {
    echo "   - {$col->Field} ({$col->Type})" . ($col->Null === 'NO' ? ' NOT NULL' : '') . "\n";
}

// 5. Ejemplo de un registro
echo "\n\n5️⃣  EJEMPLO DE REGISTRO EN prenda_telas_cot:\n";
$ejemplo = DB::table('prenda_telas_cot')->latest('id')->first();
if ($ejemplo) {
    echo json_encode($ejemplo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "No hay registros\n";
}

// 6. Ver todas las cotizaciones con sus prendas
echo "\n\n6️⃣  RESUMEN: COTIZACIONES → PRENDAS → TELAS:\n";
$cotizaciones = DB::table('cotizaciones')->latest('id')->limit(5)->get();

foreach ($cotizaciones as $cot) {
    echo "\nCotización #{$cot->numero_cotizacion} (ID: {$cot->id}):\n";
    
    $prendas = DB::table('prendas_cot')
        ->where('cotizacion_id', $cot->id)
        ->get();
    
    echo "   Prendas: " . count($prendas) . "\n";
    
    foreach ($prendas as $prenda) {
        $telas = DB::table('prenda_telas_cot')
            ->where('prenda_cot_id', $prenda->id)
            ->get();
        
        $telaIdCount = 0;
        foreach ($telas as $tela) {
            if ($tela->tela_id) $telaIdCount++;
        }
        
        echo "   - {$prenda->nombre_producto}: " . count($telas) . " telas (" . $telaIdCount . " con tela_id)\n";
    }
}
