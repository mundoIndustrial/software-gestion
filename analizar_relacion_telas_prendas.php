<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANÁLISIS: RELACIÓN prendas_pedido → telas_prenda ===\n\n";

// 1. Contar cuántos prendas_pedido tienen tela_id
echo "1️⃣  ESTADÍSTICAS GENERALES:\n";
$conTela = DB::table('prendas_pedido')->whereNotNull('tela_id')->count();
$sinTela = DB::table('prendas_pedido')->whereNull('tela_id')->count();
$total = DB::table('prendas_pedido')->count();

echo "   Total prendas: $total\n";
echo "   Con tela_id: $conTela\n";
echo "   Sin tela_id: $sinTela\n";
echo "   Porcentaje: " . round(($conTela/$total)*100, 2) . "% tienen tela_id\n\n";

// 2. Ver últimos 10 prendas con su relación de tela
echo "2️⃣  ÚLTIMAS 10 PRENDAS CON TELA:\n";
$prendas = DB::table('prendas_pedido')
    ->leftJoin('telas_prenda', 'prendas_pedido.tela_id', '=', 'telas_prenda.id')
    ->select(
        'prendas_pedido.id',
        'prendas_pedido.nombre_prenda',
        'prendas_pedido.tela_id',
        'prendas_pedido.descripcion',
        'telas_prenda.nombre as tela_nombre',
        'telas_prenda.referencia as tela_referencia'
    )
    ->latest('prendas_pedido.id')
    ->limit(10)
    ->get();

foreach ($prendas as $prenda) {
    echo "\n--- Prenda ID: {$prenda->id} ---\n";
    echo "Nombre: {$prenda->nombre_prenda}\n";
    echo "Tela ID: " . ($prenda->tela_id ?? "NULL") . "\n";
    echo "Nombre Tela en BD: " . ($prenda->tela_nombre ?? "NULL (no existe relación)") . "\n";
    echo "Referencia Tela: " . ($prenda->tela_referencia ?? "NULL") . "\n";
    
    // Extraer el nombre de la tela de la descripción
    if (preg_match('/Tela:\s*([^\|]+)/i', $prenda->descripcion, $matches)) {
        $telaEnDescripcion = trim($matches[1]);
        echo "Nombre Tela en Descripción: $telaEnDescripcion\n";
        
        // Comparar
        if ($prenda->tela_nombre && strpos($telaEnDescripcion, $prenda->tela_nombre) !== false) {
            echo "✓ Coincide\n";
        } elseif ($prenda->tela_id && !$prenda->tela_nombre) {
            echo "✗ ERROR: Hay tela_id pero no existe en telas_prenda\n";
        } else {
            echo "⚠ Diferente: Descripción dice '$telaEnDescripcion' pero BD tiene '{$prenda->tela_nombre}'\n";
        }
    }
}

// 3. Buscar inconsistencias: tela_id que no existen en telas_prenda
echo "\n\n3️⃣  VERIFICACIÓN DE INTEGRIDAD:\n";
$orfanos = DB::table('prendas_pedido')
    ->whereNotNull('tela_id')
    ->leftJoin('telas_prenda', 'prendas_pedido.tela_id', '=', 'telas_prenda.id')
    ->where('telas_prenda.id', null)
    ->count();

echo "   Prendas con tela_id huérfano (no existe en telas_prenda): $orfanos\n";

if ($orfanos > 0) {
    echo "\n   Ejemplos:\n";
    $ejemplos = DB::table('prendas_pedido')
        ->whereNotNull('tela_id')
        ->leftJoin('telas_prenda', 'prendas_pedido.tela_id', '=', 'telas_prenda.id')
        ->where('telas_prenda.id', null)
        ->select('prendas_pedido.id', 'prendas_pedido.tela_id', 'prendas_pedido.nombre_prenda')
        ->limit(5)
        ->get();
    
    foreach ($ejemplos as $ej) {
        echo "   - ID Prenda: {$ej->id}, tela_id: {$ej->tela_id}, Prenda: {$ej->nombre_prenda}\n";
    }
}

// 4. Listar todas las telas disponibles en BD
echo "\n\n4️⃣  TELAS DISPONIBLES EN BD:\n";
$telas = DB::table('telas_prenda')->get();
echo "   Total: " . count($telas) . "\n";
foreach ($telas as $tela) {
    echo "   - ID: {$tela->id}, Nombre: {$tela->nombre}, Ref: {$tela->referencia}, Activo: {$tela->activo}\n";
}

// 5. Analizar problema específico: "DRILL Descripci"
echo "\n\n5️⃣  BÚSQUEDA DEL PROBLEMA 'DRILL Descripci':\n";
$problema = DB::table('prendas_pedido')
    ->where('descripcion', 'like', '%DRILL%Descripci%')
    ->orWhere('descripcion', 'like', '%Descripci%')
    ->limit(5)
    ->get();

if (count($problema) > 0) {
    echo "   Encontrados " . count($problema) . " registros con 'Descripci'\n";
    foreach ($problema as $p) {
        echo "\n   ID: {$p->id}, Prenda: {$p->nombre_prenda}\n";
        echo "   Descripción:\n";
        echo "   " . substr($p->descripcion, 0, 300) . "...\n";
    }
} else {
    echo "   ✓ NO SE ENCONTRÓ EL PROBLEMA 'DRILL Descripci'\n";
}
