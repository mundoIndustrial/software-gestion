<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::select("DESCRIBE pedidos_produccion");
foreach($result as $col) {
    echo $col->Field . " | " . $col->Type . " | " . $col->Null . " | " . $col->Default . "\n";
}
