<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PrendaTelaCotizacion;
use App\Models\PrendaTelaFotoCot;

// Obtener fotos de tela de la cotización 79
$cotizacionId = 79;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ANÁLISIS DE FOTOS DE TELA DUPLICADAS - COTIZACIÓN: $cotizacionId\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Obtener todas las prendas de la cotización
$prendas = DB::table('prendas_cot')
    ->where('cotizacion_id', $cotizacionId)
    ->get();

echo "📦 Prendas encontradas: " . $prendas->count() . "\n\n";

foreach ($prendas as $prenda) {
    echo "─────────────────────────────────────────────────────────────\n";
    echo "📍 Prenda ID: {$prenda->id} - {$prenda->nombre_producto}\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    // 2. Obtener fotos de tela para esta prenda
    $fotosTelaDB = DB::table('prenda_tela_fotos_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->orderBy('tela_index')
        ->orderBy('orden')
        ->get();
    
    echo "🖼️  Total fotos de tela en BD: " . $fotosTelaDB->count() . "\n\n";
    
    // 3. Agrupar por tela_index
    $fotosPorTela = $fotosTelaDB->groupBy('tela_index');
    
    foreach ($fotosPorTela as $telaIndex => $fotos) {
        echo "   🧵 TELA INDEX: {$telaIndex}\n";
        echo "      └─ Fotos en esta tela: {$fotos->count()}\n";
        
        // Detectar duplicados
        $rutasAgruapadas = [];
        foreach ($fotos as $foto) {
            $ruta = $foto->ruta_original;
            if (!isset($rutasAgruapadas[$ruta])) {
                $rutasAgruapadas[$ruta] = [];
            }
            $rutasAgruapadas[$ruta][] = $foto;
        }
        
        // Mostrar rutas y contar cuántas veces aparecen
        foreach ($rutasAgruapadas as $ruta => $fotosDeRuta) {
            $cantidad = count($fotosDeRuta);
            $duplicado = $cantidad > 1 ? "❌ DUPLICADA ($cantidad veces)" : "✅";
            echo "         │\n";
            echo "         └─ $duplicado\n";
            echo "            Ruta: {$ruta}\n";
            
            if ($cantidad > 1) {
                foreach ($fotosDeRuta as $idx => $foto) {
                    echo "            └─ ID BD: {$foto->id} | Orden: {$foto->orden}\n";
                }
            }
        }
        echo "\n";
    }
}

// 4. Resumen general
echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 RESUMEN GENERAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$totalFotosDB = DB::table('prenda_tela_fotos_cot')
    ->whereIn('prenda_cot_id', $prendas->pluck('id')->toArray())
    ->count();

echo "✓ Total fotos de tela en BD: $totalFotosDB\n";

// Detectar rutas que se repiten
$rutasRepetidas = DB::table('prenda_tela_fotos_cot')
    ->whereIn('prenda_cot_id', $prendas->pluck('id')->toArray())
    ->select('ruta_original', DB::raw('COUNT(*) as cantidad'))
    ->groupBy('ruta_original')
    ->having('cantidad', '>', 1)
    ->get();

if ($rutasRepetidas->count() > 0) {
    echo "\n❌ DUPLICADOS ENCONTRADOS:\n\n";
    foreach ($rutasRepetidas as $item) {
        echo "   • Ruta: {$item->ruta_original}\n";
        echo "     Aparece: {$item->cantidad} veces\n\n";
    }
} else {
    echo "\n✅ No hay fotos duplicadas en la BD\n";
}

echo "\n";
?>
