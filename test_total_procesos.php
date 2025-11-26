<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$total = DB::table('procesos_prenda')->count();
$para_45395 = DB::table('procesos_prenda')->where('numero_pedido', '45395')->count();

echo "Total procesos en BD: $total\n";
echo "Procesos para orden 45395: $para_45395\n";
echo "\n";

// Mostrar qué procesos hay
$procesos = DB::table('procesos_prenda')
    ->where('numero_pedido', '45395')
    ->select('numero_pedido', 'proceso', 'fecha_inicio', 'fecha_fin')
    ->orderBy('fecha_inicio', 'asc')
    ->get();

echo "Detalles de procesos:\n";
echo "─────────────────────────────────────────\n";
foreach ($procesos as $p) {
    echo "Proceso: {$p->proceso}\n";
    echo "  Inicio: {$p->fecha_inicio}\n";
    echo "  Fin: {$p->fecha_fin}\n";
    echo "\n";
}
?>
