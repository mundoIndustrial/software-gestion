<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         🎨 VERIFICACIÓN DE COLORES Y TELAS                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "=== 🎨 COLORES DISPONIBLES ===\n";
$colores = DB::table('colores_prenda')->get();
echo "Total en BD: " . count($colores) . "\n\n";

if(count($colores) > 0) {
    foreach($colores as $color) {
        $estado = $color->activo ? "✅ ACTIVO" : "❌ INACTIVO";
        echo "ID: {$color->id} | Nombre: {$color->nombre} | Código: {$color->codigo} | {$estado}\n";
    }
} else {
    echo "❌ No hay colores registrados en la BD\n";
}

echo "\n=== 🧵 TELAS DISPONIBLES ===\n";
$telas = DB::table('telas_prenda')->get();
echo "Total en BD: " . count($telas) . "\n\n";

if(count($telas) > 0) {
    foreach($telas as $tela) {
        $estado = $tela->activo ? "✅ ACTIVO" : "❌ INACTIVO";
        echo "ID: {$tela->id} | Nombre: {$tela->nombre} | Referencia: {$tela->referencia} | {$estado}\n";
    }
} else {
    echo "❌ No hay telas registradas en la BD\n";
}

echo "\n=== 📊 RESUMEN ===\n";
$colores_activos = DB::table('colores_prenda')->where('activo', 1)->count();
$telas_activas = DB::table('telas_prenda')->where('activo', 1)->count();
echo "Colores Activos: {$colores_activos}\n";
echo "Telas Activas: {$telas_activas}\n";

echo "\n¿CÓMO SE USA?\n";
echo "En el frontend, estas listas se cargan para que el usuario SELECCIONE\n";
echo "No se crean nuevas colores/telas en el formulario de prendas\n";
echo "Se deben usar las que ya existen en las tablas\n";
