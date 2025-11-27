<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== VERIFICACIÓN FINAL: ENCARGADOS EN 'CREACIÓN DE ORDEN' ===\n\n";

// Consultar encargados del proceso "Creación de Orden"
$encargados = DB::table('procesos_prenda')
    ->where('proceso', 'Creación de Orden')
    ->whereNotNull('encargado')
    ->where('encargado', '!=', '')
    ->select('numero_pedido', 'encargado')
    ->limit(20)
    ->get();

echo "Primeros 20 pedidos con encargado de 'Creación de Orden':\n";
foreach ($encargados as $enc) {
    echo "  Pedido {$enc->numero_pedido}: {$enc->encargado}\n";
}

// Contar
$totalConEncargado = DB::table('procesos_prenda')
    ->where('proceso', 'Creación de Orden')
    ->whereNotNull('encargado')
    ->where('encargado', '!=', '')
    ->count();

$totalSinEncargado = DB::table('procesos_prenda')
    ->where('proceso', 'Creación de Orden')
    ->where(function($q) {
        $q->whereNull('encargado')
          ->orWhere('encargado', '=', '');
    })
    ->count();

echo "\n=== RESUMEN ===\n";
echo "Total 'Creación de Orden' CON encargado: $totalConEncargado\n";
echo "Total 'Creación de Orden' SIN encargado: $totalSinEncargado\n";
echo "Total general: " . ($totalConEncargado + $totalSinEncargado) . "\n";

echo "\n✅ LISTO PARA USAR EN LA TABLA\n";
?>
