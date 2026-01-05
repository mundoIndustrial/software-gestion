<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM prendas_pedido');
echo "Columnas de prendas_pedido:\n";
foreach($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}
