<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'procesos_prenda' AND COLUMN_NAME = 'prenda_pedido_id'");

echo "Foreign keys en procesos_prenda:\n";
foreach($constraints as $c) {
    echo "  - " . $c->CONSTRAINT_NAME . "\n";
}
?>
