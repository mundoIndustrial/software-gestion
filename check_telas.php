<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANÁLISIS COMPLETO DE ESTRUCTURAS ===\n\n";

// 1. Verificar si hay ALGUNA cotización con telas en estructura DDD
echo "1️⃣ Buscando cotizaciones con telas en estructura DDD (prenda_telas_cot):\n";
$cotizacionesConTelas = \DB::table('prenda_telas_cot')
    ->join('prendas_cot', 'prenda_telas_cot.prenda_cot_id', '=', 'prendas_cot.id')
    ->join('cotizaciones', 'prendas_cot.cotizacion_id', '=', 'cotizaciones.id')
    ->select('cotizaciones.id as cot_id', 'prendas_cot.id as prenda_id', 'prendas_cot.nombre_producto', \DB::raw('COUNT(*) as telas_count'))
    ->groupBy('cotizaciones.id', 'prendas_cot.id', 'prendas_cot.nombre_producto')
    ->get();

echo "Cotizaciones con telas en estructura DDD: " . count($cotizacionesConTelas) . "\n";
foreach($cotizacionesConTelas as $cot) {
    echo "  - Cotización ID: " . $cot->cot_id . ", Prenda: " . $cot->nombre_producto . ", Telas: " . $cot->telas_count . "\n";
}

// 2. Verificar cotización 46 específicamente
echo "\n2️⃣ Verificando Cotización 46 - Estructura DDD:\n";
$cot46Telas = \DB::table('prenda_telas_cot')
    ->join('prendas_cot', 'prenda_telas_cot.prenda_cot_id', '=', 'prendas_cot.id')
    ->where('prendas_cot.cotizacion_id', 46)
    ->get();
echo "Telas en prenda_telas_cot para cotización 46: " . count($cot46Telas) . "\n";

// 3. Verificar si cotización 46 tiene datos en estructura antigua
echo "\n3️⃣ Verificando Cotización 46 - Estructura ANTIGUA:\n";
$cot46Antigua = \DB::table('prendas_cotizaciones')->where('cotizacion_id', 46)->get();
echo "Prendas en prendas_cotizaciones para cotización 46: " . count($cot46Antigua) . "\n";

// 4. Si hay cotizaciones con telas en DDD, mostrar detalles
if(count($cotizacionesConTelas) > 0) {
    echo "\n4️⃣ Detalles de primera cotización con telas (DDD):\n";
    $primeraCot = $cotizacionesConTelas[0];
    $telaDetalle = \DB::table('prenda_telas_cot')
        ->where('prenda_cot_id', $primeraCot->prenda_id)
        ->get();
    
    foreach($telaDetalle as $t) {
        echo "  - Tela ID: " . $t->id . ", tela_id: " . $t->tela_id . ", color_id: " . $t->color_id . "\n";
    }
}

// 5. Resumen
echo "\n5️⃣ RESUMEN:\n";
$totalTelasEnDDD = \DB::table('prenda_telas_cot')->count();
$totalTelasEnAntigua = \DB::table('prendas_telas')->count();
echo "Total telas en estructura DDD: " . $totalTelasEnDDD . "\n";
echo "Total telas en estructura ANTIGUA: " . $totalTelasEnAntigua . "\n";
?>
