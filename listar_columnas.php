<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::getSchemaBuilder()->getColumnListing('prendas_pedido');
echo "Columnas en prendas_pedido:\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}
