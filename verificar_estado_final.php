<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN FINAL ANTES DE PRUEBA ===\n\n";

// 1. Tipos de prenda
echo "📋 TIPOS DE PRENDA:\n";
$tipos = DB::table('tipos_prenda')->get();
echo "   Total: " . count($tipos) . " tipos\n";
foreach ($tipos as $tipo) {
    echo "   - {$tipo->nombre} (ID: {$tipo->id}, Código: {$tipo->codigo})\n";
}

echo "\n📦 ÚLTIMO ESTADO DE COTIZACIÓN:\n";
$cotizacion = DB::table('cotizaciones')->latest('id')->first();
if ($cotizacion) {
    echo "   Cotización ID: {$cotizacion->id}\n";
    echo "   Cliente: {$cotizacion->cliente}\n";
    echo "   Tipo: {$cotizacion->tipo}\n";
    
    $prendas = DB::table('prendas_cotizaciones')->where('cotizacion_id', $cotizacion->id)->get();
    echo "   Prendas: " . count($prendas) . "\n";
    
    foreach ($prendas as $prenda) {
        $variantes = DB::table('variantes_prenda')->where('prenda_cotizacion_id', $prenda->id)->get();
        echo "   - Prenda '{$prenda->nombre_producto}' (ID: {$prenda->id}): " . count($variantes) . " variantes\n";
        
        foreach ($variantes as $var) {
            echo "     └─ Variante ID {$var->id}: manga_id={$var->tipo_manga_id}, bolsillos={$var->tiene_bolsillos}, reflectivo={$var->tiene_reflectivo}\n";
        }
    }
}

echo "\n✅ Sistema listo para prueba.\n";
