<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== COLUMNAS DE REGISTROS_POR_ORDEN ===\n";
$columns = Schema::getColumnListing('registros_por_orden');
echo implode(", ", $columns) . "\n\n";

echo "=== PRIMER REGISTRO ===\n";
$registro = DB::table('registros_por_orden')->first();
if ($registro) {
    echo json_encode($registro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
