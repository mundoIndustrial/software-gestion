<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA ACTUAL DE PROCESOS_PRENDA ===\n\n";

$columns = DB::select("DESCRIBE procesos_prenda");
foreach ($columns as $col) {
    echo "{$col->Field}: {$col->Type}";
    if ($col->Null === 'NO') echo " [NOT NULL]";
    if ($col->Default !== null) echo " [DEFAULT: {$col->Default}]";
    if ($col->Key) echo " [KEY: {$col->Key}]";
    echo "\n";
}

echo "\n=== COLUMNAS QUE CONTIENEN 'id' ===\n\n";
foreach ($columns as $col) {
    if (strpos(strtolower($col->Field), 'id') !== false) {
        echo "  - {$col->Field}\n";
    }
}

echo "\n=== VERIFICAR SI EXISTE prenda_pedido_id ===\n\n";
$existe = array_filter($columns, function($col) {
    return $col->Field === 'prenda_pedido_id';
});

if (empty($existe)) {
    echo "❌ NO EXISTE prenda_pedido_id\n\n";
    echo "Necesitas AGREGAR esta columna:\n";
    echo "ALTER TABLE procesos_prenda ADD COLUMN prenda_pedido_id BIGINT UNSIGNED AFTER numero_pedido;\n\n";
    echo "O si quieres una relación, agregar también la foreign key:\n";
    echo "ALTER TABLE procesos_prenda \n";
    echo "ADD CONSTRAINT fk_procesos_prenda_prendas_pedido \n";
    echo "FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id) ON DELETE CASCADE;\n";
} else {
    echo "✅ SÍ EXISTE prenda_pedido_id\n";
}
?>
