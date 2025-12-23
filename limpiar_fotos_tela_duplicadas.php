<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ID de cotización a limpiar
$cotizacionId = 79;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  LIMPIAR FOTOS DE TELA DUPLICADAS - COTIZACIÓN: $cotizacionId\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Obtener todas las prendas
$prendas = DB::table('prendas_cot')
    ->where('cotizacion_id', $cotizacionId)
    ->get();

$totalEliminadas = 0;

foreach ($prendas as $prenda) {
    echo "📍 Prenda ID: {$prenda->id} - {$prenda->nombre_producto}\n";
    
    // Obtener fotos de tela agrupadas por ruta
    $fotosPorRuta = DB::table('prenda_tela_fotos_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->select('ruta_original', 'tela_index', DB::raw('COUNT(*) as cantidad'), DB::raw('GROUP_CONCAT(id) as ids'))
        ->groupBy('ruta_original', 'tela_index')
        ->having('cantidad', '>', 1)
        ->get();
    
    if ($fotosPorRuta->count() === 0) {
        echo "   ✅ No hay duplicados\n\n";
        continue;
    }
    
    foreach ($fotosPorRuta as $item) {
        echo "   🧵 Tela Index: {$item->tela_index}\n";
        echo "      Ruta: {$item->ruta_original}\n";
        echo "      Aparece: {$item->cantidad} veces\n";
        
        // Obtener IDs de las fotos duplicadas
        $ids = explode(',', $item->ids);
        
        // Mantener la PRIMERA ocurrencia, eliminar el resto
        $idsParaEliminar = array_slice($ids, 1);
        
        foreach ($idsParaEliminar as $idEliminar) {
            DB::table('prenda_tela_fotos_cot')->where('id', $idEliminar)->delete();
            echo "      └─ ❌ ID BD: $idEliminar eliminado\n";
            $totalEliminadas++;
        }
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ RESUMEN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "Total filas eliminadas: $totalEliminadas\n";

// Verificar resultado
$fotosRestantes = DB::table('prenda_tela_fotos_cot')
    ->whereIn('prenda_cot_id', $prendas->pluck('id')->toArray())
    ->count();

echo "Total fotos de tela restantes: $fotosRestantes\n";

$duplicadosRestantes = DB::table('prenda_tela_fotos_cot')
    ->whereIn('prenda_cot_id', $prendas->pluck('id')->toArray())
    ->select('ruta_original', DB::raw('COUNT(*) as cantidad'))
    ->groupBy('ruta_original')
    ->having('cantidad', '>', 1)
    ->count();

if ($duplicadosRestantes === 0) {
    echo "\n✅ ¡No hay más duplicados!\n";
} else {
    echo "\n⚠️ Aún hay $duplicadosRestantes rutas duplicadas\n";
}

echo "\n";
?>
