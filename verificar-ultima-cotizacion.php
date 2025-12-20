<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "============================================================\n";
echo "📊 ÚLTIMA COTIZACIÓN CREADA\n";
echo "============================================================\n\n";

$cotizacion = \App\Models\Cotizacion::orderBy('id', 'desc')->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones\n\n";
    exit(1);
}

echo "✅ Última cotización: ID {$cotizacion->id}\n";
echo "   Cliente: {$cotizacion->cliente}\n";
echo "   Creada: {$cotizacion->created_at}\n";
echo "   Prendas: {$cotizacion->prendas->count()}\n\n";

if ($cotizacion->prendas->isEmpty()) {
    echo "⚠️  No hay prendas en esta cotización\n\n";
    exit(0);
}

foreach ($cotizacion->prendas as $index => $prenda) {
    $num = $index + 1;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📦 PRENDA #{$num} (ID: {$prenda->id})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   Nombre: {$prenda->nombre_producto}\n";
    echo "   Cotización ID: {$prenda->cotizacion_id}\n";
    
    // Variantes
    $variantes = $prenda->variantes;
    echo "\n   🎨 VARIANTES: {$variantes->count()}\n";
    foreach ($variantes as $v => $variante) {
        echo "      Variante ID: {$variante->id}\n";
        if ($variante->telas_multiples) {
            $telas = is_string($variante->telas_multiples) 
                ? json_decode($variante->telas_multiples, true) 
                : $variante->telas_multiples;
            echo "      telas_multiples JSON: " . count($telas ?? []) . " telas\n";
            foreach (($telas ?? []) as $t => $tela) {
                echo "         " . ($t + 1) . ". {$tela['color']} - {$tela['tela']} ({$tela['referencia']})\n";
            }
        } else {
            echo "      ⚠️ NO tiene telas_multiples\n";
        }
    }
    
    // Verificar en prenda_telas_cot
    $telasCot = DB::table('prenda_telas_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    
    echo "\n   🧵 TELAS EN prenda_telas_cot: {$telasCot->count()}\n";
    if ($telasCot->count() > 0) {
        foreach ($telasCot as $i => $tela) {
            $color = DB::table('colores_prenda')->find($tela->color_id);
            $telaInfo = DB::table('telas_prenda')->find($tela->tela_id);
            echo "      " . ($i + 1) . ". {$color->nombre} - {$telaInfo->nombre}\n";
        }
    } else {
        echo "      ❌ NO HAY REGISTROS - PROBLEMA DETECTADO\n";
    }
    
    echo "\n";
}

echo "============================================================\n\n";

// Mostrar TODAS las telas en la tabla
echo "📊 TODOS LOS REGISTROS EN prenda_telas_cot:\n";
echo "============================================================\n";
$todasTelas = DB::table('prenda_telas_cot')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

foreach ($todasTelas as $t) {
    $prenda = DB::table('prendas_cot')->find($t->prenda_cot_id);
    echo "ID: {$t->id} | Prenda: {$t->prenda_cot_id} (Cot: " . ($prenda->cotizacion_id ?? 'N/A') . ") | Created: {$t->created_at}\n";
}

echo "\n";
