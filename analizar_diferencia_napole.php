<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE DIFERENCIA ===\n\n";

// 1. Obtener los 19 con "napole" en descripcion
echo "1️⃣  Registros con 'napole' en descripcion:\n";
$napoleEnDescripcion = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->select('id', 'nombre_prenda', 'descripcion')
    ->get();

echo "Total: " . $napoleEnDescripcion->count() . "\n";
$napoleIds = $napoleEnDescripcion->pluck('id')->toArray();
sort($napoleIds);
echo "IDs: " . implode(', ', $napoleIds) . "\n\n";

// 2. Obtener registros con "napole" en descripcion_armada
echo "2️⃣  Registros con 'napole' en descripcion_armada:\n";
$napoleEnArmada = DB::table('prendas_pedido')
    ->where('descripcion_armada', 'LIKE', '%napole%')
    ->select('id', 'nombre_prenda', 'descripcion_armada')
    ->get();

echo "Total: " . $napoleEnArmada->count() . "\n";
$armadaIds = $napoleEnArmada->pluck('id')->toArray();
sort($armadaIds);
echo "IDs: " . implode(', ', $armadaIds) . "\n\n";

// 3. Encontrar la diferencia
echo "3️⃣  ANÁLISIS DE DIFERENCIA\n\n";

$soloEnArmada = array_diff($armadaIds, $napoleIds);
$soloEnDescripcion = array_diff($napoleIds, $armadaIds);

if (!empty($soloEnArmada)) {
    echo "❌ EXTRA EN ARMADA (no en descripcion):\n";
    foreach ($soloEnArmada as $id) {
        $prenda = DB::table('prendas_pedido')
            ->where('id', $id)
            ->first();
        echo "  ID: $id\n";
        echo "  Nombre: {$prenda->nombre_prenda}\n";
        echo "  Descripcion: " . substr($prenda->descripcion, 0, 70) . "...\n";
        echo "  Descripcion_armada: " . substr($prenda->descripcion_armada, 0, 70) . "...\n";
        echo "  ---\n";
    }
}

if (!empty($soloEnDescripcion)) {
    echo "⚠️  FALTAN EN ARMADA (sí en descripcion):\n";
    foreach ($soloEnDescripcion as $id) {
        $prenda = DB::table('prendas_pedido')
            ->where('id', $id)
            ->first();
        echo "  ID: $id\n";
        echo "  Nombre: {$prenda->nombre_prenda}\n";
        echo "  Descripcion: " . substr($prenda->descripcion, 0, 70) . "...\n";
        echo "  Descripcion_armada: " . (empty($prenda->descripcion_armada) ? "[VACIA]" : substr($prenda->descripcion_armada, 0, 70)) . "...\n";
        echo "  ---\n";
    }
}

if (empty($soloEnArmada) && empty($soloEnDescripcion)) {
    echo "✅ Los IDs son idénticos\n";
}
?>
