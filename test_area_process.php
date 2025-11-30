<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$pedido = 45445;
echo "=== Procesos para pedido $pedido ===\n";

$procesos = DB::table('procesos_prenda')
    ->where('numero_pedido', $pedido)
    ->orderBy('updated_at', 'desc')
    ->get(['proceso', 'fecha_inicio', 'updated_at']);

foreach ($procesos as $p) {
    echo "  - {$p->proceso} (actualizado: {$p->updated_at})\n";
}

echo "\n=== Último proceso (debería ser el que se muestra) ===\n";
$ultimo = DB::table('procesos_prenda')
    ->where('numero_pedido', $pedido)
    ->orderBy('updated_at', 'desc')
    ->first(['proceso', 'fecha_inicio', 'updated_at']);

if ($ultimo) {
    echo "Proceso: {$ultimo->proceso}\n";
    echo "Actualizado: {$ultimo->updated_at}\n";
} else {
    echo "No hay procesos\n";
}
