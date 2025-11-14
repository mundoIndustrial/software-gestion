<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$registros = \App\Models\RegistroPisoCorte::latest()->limit(10)->get(['id', 'fecha', 'tiempo_disponible', 'meta', 'eficiencia', 'cantidad', 'created_at']);

echo "\n=== ÚLTIMOS 10 REGISTROS DE CORTE ===\n";
echo "ID\tTD\tMeta\tEfi\tCantidad\tCreated\n";
echo "---\t---\t---\t---\t---\t---\n";
foreach($registros as $r) { 
    echo $r->id . "\t" . $r->tiempo_disponible . "\t" . $r->meta . "\t" . $r->eficiencia . "\t" . $r->cantidad . "\t" . $r->created_at->format('H:i:s') . "\n"; 
}
echo "\n";
