<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANÁLISIS: CAMPO telas_multiples EN prenda_variantes_cot ===\n\n";

// 1. Ver estructura
echo "1️⃣  ESTRUCTURA DE TABLA prenda_variantes_cot:\n";
$columns = DB::select("SHOW COLUMNS FROM prenda_variantes_cot");
foreach ($columns as $col) {
    echo "   - {$col->Field} ({$col->Type})" . ($col->Null === 'NO' ? ' NOT NULL' : '') . "\n";
}

// 2. Contar registros
$total = DB::table('prenda_variantes_cot')->count();
echo "\n2️⃣  TOTAL REGISTROS: $total\n\n";

// 3. Ver últimas 5 variantes
echo "3️⃣  ÚLTIMAS 5 VARIANTES CON CAMPO telas_multiples:\n";
$variantes = DB::table('prenda_variantes_cot')
    ->latest('id')
    ->limit(5)
    ->get();

foreach ($variantes as $idx => $variante) {
    echo "\n--- Variante ID: {$variante->id} ---\n";
    echo "Prenda COT ID: {$variante->prenda_cot_id}\n";
    echo "Tipo Prenda: {$variante->tipo_prenda}\n";
    echo "Telas Múltiples (raw):\n";
    echo "   " . $variante->telas_multiples . "\n";
    
    // Decodificar JSON
    if (!empty($variante->telas_multiples)) {
        $telas = json_decode($variante->telas_multiples, true);
        echo "Telas Múltiples (decoded):\n";
        echo json_encode($telas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

// 4. Analizar estructura del JSON
echo "\n\n4️⃣  ESTRUCTURA DEL JSON telas_multiples:\n";
$conTelas = DB::table('prenda_variantes_cot')
    ->whereNotNull('telas_multiples')
    ->where('telas_multiples', '!=', '')
    ->where('telas_multiples', '!=', 'null')
    ->count();

$sinTelas = DB::table('prenda_variantes_cot')
    ->where(function($query) {
        $query->whereNull('telas_multiples')
            ->orWhere('telas_multiples', '')
            ->orWhere('telas_multiples', 'null');
    })
    ->count();

echo "   Con telas_multiples: $conTelas\n";
echo "   Sin telas_multiples: $sinTelas\n\n";

// 5. Ver un ejemplo completo con TODA la información de cotización
echo "5️⃣  EJEMPLO COMPLETO: COTIZACIÓN → PRENDA → VARIANTE → TELAS:\n";
$cotizacion = DB::table('cotizaciones')
    ->latest('id')
    ->first();

if ($cotizacion) {
    echo "\nCotización #{$cotizacion->numero_cotizacion} (ID: {$cotizacion->id}):\n";
    
    $prendas = DB::table('prendas_cot')
        ->where('cotizacion_id', $cotizacion->id)
        ->get();
    
    echo "Prendas: " . count($prendas) . "\n";
    
    foreach ($prendas as $prenda) {
        echo "\n  Prenda: {$prenda->nombre_producto} (ID: {$prenda->id})\n";
        
        $variantes = DB::table('prenda_variantes_cot')
            ->where('prenda_cot_id', $prenda->id)
            ->get();
        
        echo "  Variantes: " . count($variantes) . "\n";
        
        foreach ($variantes as $variante) {
            echo "\n    Variante ID: {$variante->id}\n";
            echo "    Tipo Prenda: {$variante->tipo_prenda}\n";
            echo "    Tipo Manga ID: {$variante->tipo_manga_id}\n";
            echo "    Tipo Broche ID: {$variante->tipo_broche_id}\n";
            
            if (!empty($variante->telas_multiples) && $variante->telas_multiples !== 'null') {
                $telas = json_decode($variante->telas_multiples, true);
                echo "    Telas:\n";
                if (is_array($telas)) {
                    foreach ($telas as $tela) {
                        echo "      - ID: " . ($tela['id'] ?? $tela['tela_id'] ?? 'N/A') . ", ";
                        echo "Nombre: " . ($tela['nombre'] ?? $tela['nombre_tela'] ?? 'N/A') . ", ";
                        echo "Ref: " . ($tela['referencia'] ?? $tela['ref'] ?? 'N/A') . ", ";
                        echo "Color: " . ($tela['color'] ?? $tela['color_id'] ?? 'N/A') . "\n";
                    }
                } else {
                    echo "      ⚠ NO ES ARRAY: " . json_encode($telas) . "\n";
                }
            } else {
                echo "    Telas: VACÍO\n";
            }
        }
    }
}

// 6. Buscar qué hay EN EL JSON - estructura exacta
echo "\n\n6️⃣  ANÁLISIS DE ESTRUCTURA DEL JSON:\n";
$ejemplo = DB::table('prenda_variantes_cot')
    ->whereNotNull('telas_multiples')
    ->where('telas_multiples', '!=', '')
    ->where('telas_multiples', '!=', 'null')
    ->first();

if ($ejemplo) {
    $telasDecoded = json_decode($ejemplo->telas_multiples, true);
    echo "Estructura encontrada:\n";
    
    if (is_array($telasDecoded) && count($telasDecoded) > 0) {
        $primeraTela = $telasDecoded[0];
        echo "Campos en la primera tela:\n";
        foreach ($primeraTela as $key => $value) {
            echo "   - $key: " . (is_scalar($value) ? $value : json_encode($value)) . "\n";
        }
    }
}
