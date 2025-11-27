<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE TABLA PROCESOS_PRENDA ===\n\n";

// Estructura
echo "1️⃣  Estructura de tabla 'procesos_prenda':\n";
$columns = DB::select("DESCRIBE procesos_prenda");
foreach ($columns as $col) {
    echo "  - {$col->Field}: {$col->Type}";
    if ($col->Null === 'NO') echo " [NOT NULL]";
    if ($col->Default !== null) echo " [DEFAULT: {$col->Default}]";
    if ($col->Key === 'PRI') echo " [PRIMARY KEY]";
    if ($col->Key === 'MUL') echo " [INDEX]";
    echo "\n";
}

echo "\n2️⃣  Campos obligatorios (NOT NULL):\n";
$obligatorios = array_filter($columns, function($col) {
    return $col->Null === 'NO' && $col->Default === null;
});

foreach ($obligatorios as $col) {
    echo "  ⚠️  {$col->Field}\n";
}

echo "\n3️⃣  Datos en procesos_prenda (últimos 5):\n";
$procesos = DB::table('procesos_prenda')
    ->select('*')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($procesos->count() > 0) {
    foreach ($procesos as $proc) {
        echo "  ID: {$proc->id} | Proceso: {$proc->proceso}";
        if (isset($proc->numero_pedido)) {
            echo " | Num Pedido: {$proc->numero_pedido}";
        }
        echo "\n";
    }
} else {
    echo "  No hay registros\n";
}

echo "\n4️⃣  ¿Cuál debería ser el valor de numero_pedido?\n";
echo "  Opciones:\n";
echo "  A) Debería ser NULL (no obligatorio)\n";
echo "  B) Debería ser un número de pedido específico\n";
echo "  C) Debería relacionarse con otra tabla (pedidos_produccion)\n";
?>
