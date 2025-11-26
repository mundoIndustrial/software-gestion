<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$procesos = DB::table('procesos_prenda')
    ->where('numero_pedido', '45395')
    ->orderBy('fecha_inicio', 'asc')
    ->select('numero_pedido', 'proceso', 'fecha_inicio')
    ->get();

echo "Procesos para orden 45395: " . $procesos->count() . "\n\n";
foreach ($procesos as $p) {
    echo $p->proceso . " | " . $p->fecha_inicio . "\n";
}
?>
