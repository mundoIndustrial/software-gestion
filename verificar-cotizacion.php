<?php

/**
 * Script para verificar datos de cotización
 * Uso: php verificar-cotizacion.php [id]
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cotizacionId = $argv[1] ?? 3;

echo "\n";
echo "============================================================\n";
echo "📊 VERIFICACIÓN DE COTIZACIÓN ID: {$cotizacionId}\n";
echo "============================================================\n\n";

$cotizacion = \App\Models\Cotizacion::find($cotizacionId);

if (!$cotizacion) {
    echo "❌ Cotización no encontrada\n\n";
    exit(1);
}

echo "✅ Cotización encontrada\n";
echo "   Cliente: {$cotizacion->cliente}\n";
echo "   Tipo: {$cotizacion->tipo_cotizacion_id}\n";
echo "   Borrador: " . ($cotizacion->es_borrador ? 'Sí' : 'No') . "\n";
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
    echo "   Descripción: " . ($prenda->descripcion ?? '(vacío)') . "\n";
    
    // Fotos de prenda
    $fotos = $prenda->fotos;
    echo "\n   📸 FOTOS DE PRENDA: {$fotos->count()}\n";
    foreach ($fotos as $i => $foto) {
        echo "      " . ($i + 1) . ". {$foto->ruta_original}\n";
    }
    
    // Variantes
    $variantes = $prenda->variantes;
    echo "\n   🎨 VARIANTES: {$variantes->count()}\n";
    foreach ($variantes as $v => $variante) {
        echo "      Variante " . ($v + 1) . ":\n";
        if ($variante->telas_multiples) {
            $telas = is_string($variante->telas_multiples) 
                ? json_decode($variante->telas_multiples, true) 
                : $variante->telas_multiples;
            echo "         telas_multiples: " . count($telas ?? []) . " telas\n";
            foreach (($telas ?? []) as $t => $tela) {
                echo "            " . ($t + 1) . ". {$tela['color']} - {$tela['tela']} ({$tela['referencia']})\n";
            }
        }
    }
    
    // Verificar que la prenda pertenece a esta cotización
    $prendaVerificada = DB::table('prendas_cot')
        ->where('id', $prenda->id)
        ->where('cotizacion_id', $cotizacionId)
        ->first();
    
    if (!$prendaVerificada) {
        echo "\n   ⚠️  ADVERTENCIA: Esta prenda NO pertenece a la cotización {$cotizacionId}\n";
        echo "      Cotización real: {$prenda->cotizacion_id}\n";
    }
    
    // Telas en prenda_telas_cot
    $telasCot = DB::table('prenda_telas_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    
    echo "\n   🧵 TELAS EN prenda_telas_cot: {$telasCot->count()}\n";
    if ($telasCot->count() > 0) {
        foreach ($telasCot as $i => $tela) {
            $color = DB::table('colores_prenda')->find($tela->color_id);
            $telaInfo = DB::table('telas_prenda')->find($tela->tela_id);
            echo "      " . ($i + 1) . ". Color: " . ($color->nombre ?? 'N/A') . " (ID: {$tela->color_id})\n";
            echo "         Tela: " . ($telaInfo->nombre ?? 'N/A') . " (ID: {$tela->tela_id})\n";
            echo "         Variante ID: {$tela->variante_prenda_cot_id}\n";
            echo "         Created: {$tela->created_at}\n";
        }
    } else {
        echo "      ❌ No hay telas guardadas\n";
    }
    
    // Fotos de telas en prenda_tela_fotos_cot
    $fotosTelas = DB::table('prenda_tela_fotos_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    
    echo "\n   📸 FOTOS DE TELAS EN prenda_tela_fotos_cot: {$fotosTelas->count()}\n";
    if ($fotosTelas->count() > 0) {
        foreach ($fotosTelas as $i => $foto) {
            echo "      " . ($i + 1) . ". {$foto->ruta_original}\n";
            echo "         Orden: {$foto->orden}\n";
            echo "         Created: {$foto->created_at}\n";
        }
    } else {
        echo "      ❌ No hay fotos de telas guardadas\n";
    }
    
    echo "\n";
}

echo "============================================================\n";
echo "✅ VERIFICACIÓN COMPLETADA\n";
echo "============================================================\n\n";
