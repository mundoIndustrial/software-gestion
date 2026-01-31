<?php
/**
 * DIAGNÓSTICO: Contenido de PRENDA_TELAS_COT para COT-00016
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "🔍 DIAGNÓSTICO: PRENDA_TELAS_COT\n";
echo "========================================\n\n";

// 1. Verificar si hay telas para prenda_cot_id = 5
echo "📌 PASO 1: ¿Cuántas telas hay en prenda_telas_cot para prenda_cot_id = 5?\n";
echo str_repeat("─", 50) . "\n";

$totalTelas = DB::table('prenda_telas_cot')
    ->where('prenda_cot_id', 5)
    ->count();

echo "Total de telas: " . $totalTelas . "\n\n";

if ($totalTelas == 0) {
    echo "⚠️ NO HAY TELAS REGISTRADAS\n\n";
} else {
    echo "✓ HAY " . $totalTelas . " TELAS\n\n";
    
    // 2. Ver todos los datos de telas
    echo "📌 PASO 2: Detalle de telas para prenda_cot_id = 5\n";
    echo str_repeat("─", 50) . "\n";
    
    $telas = DB::table('prenda_telas_cot as pt')
        ->leftJoin('telas_prendas as t', 't.id', '=', 'pt.tela_id')
        ->leftJoin('colores_prendas as c', 'c.id', '=', 'pt.color_id')
        ->select(
            'pt.id',
            'pt.prenda_cot_id',
            'pt.variante_prenda_cot_id',
            'pt.tela_id',
            'pt.color_id',
            't.nombre as tela_nombre',
            't.referencia as tela_referencia',
            'c.nombre as color_nombre',
            'c.codigo as color_codigo',
            'pt.created_at',
            'pt.updated_at'
        )
        ->where('pt.prenda_cot_id', 5)
        ->get();
    
    foreach ($telas as $idx => $tela) {
        echo "\n🧵 TELA #" . ($idx + 1) . ":\n";
        echo "   ID Tabla: " . $tela->id . "\n";
        echo "   Prenda CotID: " . $tela->prenda_cot_id . "\n";
        echo "   Variante CotID: " . $tela->variante_prenda_cot_id . "\n";
        echo "   Tela ID: " . $tela->tela_id . " → " . ($tela->tela_nombre ?? 'SIN NOMBRE') . "\n";
        echo "   Color ID: " . $tela->color_id . " → " . ($tela->color_nombre ?? 'SIN NOMBRE') . "\n";
        echo "   Referencia: " . ($tela->tela_referencia ?? 'N/A') . "\n";
        echo "   Creado: " . $tela->created_at . "\n";
    }
}

// 3. Ver si hay fotos de telas
echo "\n\n📌 PASO 3: ¿Hay fotos de telas para prenda_cot_id = 5?\n";
echo str_repeat("─", 50) . "\n";

$totalFotos = DB::table('prenda_tela_fotos_cot')
    ->where('prenda_cot_id', 5)
    ->count();

echo "Total de fotos de telas: " . $totalFotos . "\n";

if ($totalFotos > 0) {
    $fotos = DB::table('prenda_tela_fotos_cot')
        ->where('prenda_cot_id', 5)
        ->get();
    
    foreach ($fotos as $idx => $foto) {
        echo "\n📸 FOTO #" . ($idx + 1) . ":\n";
        echo "   ID: " . $foto->id . "\n";
        echo "   Prenda CotID: " . $foto->prenda_cot_id . "\n";
        echo "   Prenda Tela CotID: " . $foto->prenda_tela_cot_id . "\n";
        echo "   Ruta: " . $foto->ruta_original . "\n";
        echo "   WebP: " . ($foto->ruta_webp ?? 'N/A') . "\n";
    }
}

// 4. Ver toda la estructura de prenda_cot
echo "\n\n📌 PASO 4: Datos de prenda_cot (id = 5)\n";
echo str_repeat("─", 50) . "\n";

$prenda = DB::table('prendas_cot')
    ->where('id', 5)
    ->first();

if ($prenda) {
    echo "✓ Prenda encontrada:\n";
    echo "   ID: " . $prenda->id . "\n";
    echo "   Nombre: " . $prenda->nombre_producto . "\n";
    echo "   Cotización ID: " . $prenda->cotizacion_id . "\n";
    echo "   Cantidad: " . $prenda->cantidad . "\n";
} else {
    echo "❌ Prenda no encontrada\n";
}

echo "\n========================================\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo "========================================\n\n";
?>
