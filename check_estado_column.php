<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::select("DESCRIBE pedidos_produccion estado");
echo json_encode($result, JSON_PRETTY_PRINT);
