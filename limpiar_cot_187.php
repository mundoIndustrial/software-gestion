<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

use Illuminate\Support\Facades\DB;

// Eliminar registros de pedidos_produccion para cotización 187
$deleted = DB::table('pedidos_produccion')
    ->where('cotizacion_id', 187)
    ->delete();

echo "✅ Se eliminaron $deleted registros de pedidos_produccion para cotización 187\n";

// Mostrar registros en logo_pedidos para esa cotización
$logos = DB::table('logo_pedidos')
    ->where('cotizacion_id', 187)
    ->get();

echo "\n📝 Registros en logo_pedidos para cotización 187:\n";
foreach ($logos as $logo) {
    echo "  - ID: {$logo->id}, Número: {$logo->numero_pedido}, Estado: {$logo->estado}\n";
}

echo "\n✅ Listo para probar de nuevo\n";
?>
