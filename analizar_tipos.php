<?php
/**
 * Script para verificar la columna 'tipo' en cotizaciones
 */

use Illuminate\Support\Facades\DB;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🔍 ANALIZANDO COLUMNA 'tipo' EN COTIZACIONES\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Ver valores únicos de tipo
$tiposUnicos = DB::table('cotizaciones')
    ->select('tipo', DB::raw('COUNT(*) as total'))
    ->groupBy('tipo')
    ->get();

echo "Valores únicos en columna 'tipo':\n\n";
foreach ($tiposUnicos as $tipo) {
    echo "  Tipo: " . ($tipo->tipo ?? 'NULL') . " | Total: {$tipo->total}\n";
}

// Comparar tipo vs tipo_cotizacion_id
echo "\n\n📊 COMPARATIVA: 'tipo' vs 'tipo_cotizacion_id'\n";
echo "════════════════════════════════════════════════════════════\n\n";

$cotizaciones = DB::table('cotizaciones')
    ->select('id', 'tipo', 'tipo_cotizacion_id', 'estado', 'es_borrador')
    ->orderBy('created_at', 'desc')
    ->limit(15)
    ->get();

echo "ID | tipo | tipo_cot_id | Estado | Borrador\n";
echo "─────────────────────────────────────────────────────────────\n";
foreach ($cotizaciones as $cot) {
    $tipo = $cot->tipo ?? 'NULL';
    $tipoCotId = $cot->tipo_cotizacion_id ?? 'NULL';
    $estado = $cot->estado;
    $borrador = $cot->es_borrador ? '✅ SÍ' : '❌ NO';
    echo "{$cot->id} | {$tipo} | {$tipoCotId} | {$estado} | {$borrador}\n";
}

echo "\n✅ Análisis completado\n";
