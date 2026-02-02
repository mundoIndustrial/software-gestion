<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\LogoCotizacionTelasPrenda;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICANDO TABLA: logo_cotizacion_telas_prenda\n\n";

// Obtener todos los registros
$telas = LogoCotizacionTelasPrenda::all();

echo "📊 Total de registros: " . $telas->count() . "\n\n";

if ($telas->count() > 0) {
    echo "📋 REGISTROS:\n";
    echo str_repeat("=", 120) . "\n";
    
    foreach ($telas as $index => $tela) {
        echo "\n[$index] ID: {$tela->id}\n";
        echo "    Logo Cotización ID: {$tela->logo_cotizacion_id}\n";
        echo "    Prenda Cot ID: {$tela->prenda_cot_id}\n";
        echo "    Tela: {$tela->tela}\n";
        echo "    Color: {$tela->color}\n";
        echo "    Ref: {$tela->ref}\n";
        echo "    Img (RAW): {$tela->img}\n";
        
        // Verificar si es una ruta completa o parcial
        if (strpos($tela->img, '/storage/') === 0) {
            echo "    ✅ RUTA ABSOLUTA (comienza con /storage/)\n";
        } elseif (strpos($tela->img, 'storage/') === 0) {
            echo "    ⚠️ RUTA RELATIVA (comienza con storage/)\n";
            echo "    → Debe convertirse a: /{$tela->img}\n";
        } else {
            echo "    ❌ RUTA NO ESTÁNDAR\n";
        }
    }
    
    echo "\n" . str_repeat("=", 120) . "\n";
    
    // Query directa SQL
    echo "\n🔍 QUERY DIRECTA SQL:\n";
    $resultado = DB::select("SELECT id, logo_cotizacion_id, prenda_cot_id, tela, color, ref, img FROM logo_cotizacion_telas_prenda");
    foreach ($resultado as $row) {
        echo "\nID: {$row->id} | Prenda: {$row->prenda_cot_id} | Tela: {$row->tela} | Color: {$row->color}\n";
        echo "  img: {$row->img}\n";
    }
} else {
    echo "⚠️ No hay registros en la tabla\n";
}

echo "\n✅ Script completado\n";
