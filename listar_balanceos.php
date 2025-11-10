<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Balanceo;

echo "=== LISTA DE BALANCEOS ===\n\n";

$balanceos = Balanceo::with('prenda')->get();

foreach ($balanceos as $balanceo) {
    $prendaNombre = $balanceo->prenda ? $balanceo->prenda->nombre : 'Sin prenda';
    echo sprintf("ID: %-3d | %-40s | SAM: %8.1f | Ops: %3d\n", 
        $balanceo->id, 
        substr($prendaNombre, 0, 40),
        $balanceo->sam_total,
        $balanceo->operaciones()->count()
    );
}
