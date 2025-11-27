<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR TRUNCACION EN LA BD ===\n\n";

$result = DB::select("DESCRIBE prendas_pedido");
foreach ($result as $col) {
    if ($col->Field === 'descripcion') {
        echo "Campo 'descripcion': " . $col->Type . "\n";
        echo "Null: " . $col->Null . "\n";
        echo "Key: " . $col->Key . "\n";
        echo "Default: " . $col->Default . "\n";
        echo "Extra: " . $col->Extra . "\n\n";
    }
}

echo "=== LONGITUD DE DESCRIPCIONES ===\n\n";

$descriptions = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->select(DB::raw('LENGTH(descripcion) as len, descripcion'))
    ->get()
    ->sortBy('len')
    ->reverse();

foreach ($descriptions as $row) {
    echo "Length: " . $row->len . " chars - " . substr($row->descripcion, 0, 90) . "...\n";
}

echo "\n=== FULL DESCRIPTIONS (FIRST 5) ===\n\n";

$fullDesc = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->limit(5)
    ->pluck('descripcion');

foreach ($fullDesc as $i => $desc) {
    echo "Description " . ($i+1) . ":\n";
    echo $desc . "\n";
    echo "---\n";
}
?>
