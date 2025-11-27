<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR EXACTAMENTE QUÉ ESTÁ RETORNANDO ===\n\n";

// Obtener descripciones armadas que contienen "napole"
echo "Descripciones ARMADAS únicas con 'napole':\n";
$descArmadas = DB::table('prendas_pedido')
    ->where('descripcion_armada', 'LIKE', '%napole%')
    ->distinct()
    ->pluck('descripcion_armada')
    ->toArray();

echo "Total de descripciones únicas: " . count($descArmadas) . "\n\n";

// Ahora simular el filtro: si el usuario selecciona TODAS esas descripciones
$pedidosDelFiltro = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->whereIn('prendas_pedido.descripcion_armada', $descArmadas)
    ->pluck('id')
    ->toArray();

sort($pedidosDelFiltro);

echo "Si seleccionas TODAS las descripciones armadas con 'napole':\n";
echo "Total de pedidos: " . count($pedidosDelFiltro) . "\n";
echo "Pedidos: " . implode(', ', $pedidosDelFiltro) . "\n\n";

// Comparar con solo descripcion
$pedidosPorDescripcion = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where('prendas_pedido.descripcion', 'LIKE', '%napole%')
    ->pluck('id')
    ->toArray();

sort($pedidosPorDescripcion);

echo "Si filtras solo por descripcion LIKE '%napole%':\n";
echo "Total de pedidos: " . count($pedidosPorDescripcion) . "\n";
echo "Pedidos: " . implode(', ', $pedidosPorDescripcion) . "\n\n";

// Diferencia
$extra = array_diff($pedidosDelFiltro, $pedidosPorDescripcion);
$faltan = array_diff($pedidosPorDescripcion, $pedidosDelFiltro);

echo "=== ANÁLISIS ===\n";
if (!empty($extra)) {
    echo "❌ EXTRA (en armada pero NO en descripcion): " . implode(', ', $extra) . "\n";
}
if (!empty($faltan)) {
    echo "⚠️  FALTAN (en descripcion pero NO en armada): " . implode(', ', $faltan) . "\n";
}
if (empty($extra) && empty($faltan)) {
    echo "✅ Son exactamente iguales\n";
}
?>
