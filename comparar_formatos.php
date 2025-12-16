<?php
/**
 * Comparar 45452 con el pedido más reciente para verificar formato exacto
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

$pedido45452 = PedidoProduccion::where('numero_pedido', '45452')->first();
$pedidoNuevo = PedidoProduccion::latest('id')->first();

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "FORMATO DEL 45452 vs FORMATO DEL NUEVO PEDIDO\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

$prenda45452 = $pedido45452->prendas()->first();
$prendaNueva = $pedidoNuevo->prendas()->first();

echo "📋 PEDIDO 45452 - PRENDA #1:\n";
echo "─────────────────────────────\n";
echo $prenda45452->descripcion . "\n";

echo "\n\n";
echo "📋 PEDIDO " . $pedidoNuevo->numero_pedido . " - PRENDA #1:\n";
echo "─────────────────────────────\n";
echo $prendaNueva->descripcion . "\n";

echo "\n\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "COMPARACIÓN ESTRUCTURA\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

// Verificar que ambos tengan la misma estructura de líneas
$lineas45452 = explode("\n", $prenda45452->descripcion);
$lineasNueva = explode("\n", $prendaNueva->descripcion);

echo "LÍNEAS EN 45452: " . count($lineas45452) . "\n";
echo "LÍNEAS EN " . $pedidoNuevo->numero_pedido . ": " . count($lineasNueva) . "\n\n";

// Mostrar línea por línea
echo "LÍNEAS DEL 45452:\n";
foreach ($lineas45452 as $i => $linea) {
    echo "  [$i] " . substr($linea, 0, 80) . (strlen($linea) > 80 ? "..." : "") . "\n";
}

echo "\n\nLÍNEAS DEL " . $pedidoNuevo->numero_pedido . ":\n";
foreach ($lineasNueva as $i => $linea) {
    echo "  [$i] " . substr($linea, 0, 80) . (strlen($linea) > 80 ? "..." : "") . "\n";
}

echo "\n\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n";

// Verificar estructura de campos
$campos45452 = [];
$camposNueva = [];

foreach ($lineas45452 as $linea) {
    if (preg_match('/^([^:]+):/', $linea, $m)) {
        $campos45452[] = trim($m[1]);
    }
}

foreach ($lineasNueva as $linea) {
    if (preg_match('/^([^:]+):/', $linea, $m)) {
        $camposNueva[] = trim($m[1]);
    }
}

echo "CAMPOS EN 45452: " . implode(", ", $campos45452) . "\n";
echo "CAMPOS EN " . $pedidoNuevo->numero_pedido . ": " . implode(", ", $camposNueva) . "\n";

// Verificar si los campos coinciden
$mismosCampos = $campos45452 === $camposNueva;
echo "\n✅ ¿Estructura idéntica?: " . ($mismosCampos ? "SÍ" : "NO") . "\n";

if (!$mismosCampos) {
    echo "\n⚠️ Diferencias encontradas:\n";
    $faltantes = array_diff($campos45452, $camposNueva);
    $extras = array_diff($camposNueva, $campos45452);
    if (!empty($faltantes)) {
        echo "   Faltantes: " . implode(", ", $faltantes) . "\n";
    }
    if (!empty($extras)) {
        echo "   Extras: " . implode(", ", $extras) . "\n";
    }
}
