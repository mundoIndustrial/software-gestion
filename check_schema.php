<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$schema = DB::select('DESCRIBE prenda_tela_fotos_cot');

echo "\n📋 SCHEMA: prenda_tela_fotos_cot\n";
echo "─────────────────────────────────────────────\n";
foreach ($schema as $col) {
    echo str_pad($col->Field, 25) . " | " . str_pad($col->Type, 20) . " | " . $col->Null . "\n";
}
echo "\n";
