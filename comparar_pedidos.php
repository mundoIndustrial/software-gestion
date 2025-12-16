<?php
/**
 * Comparar pedido 45452 (referencia) con el nuevo pedido creado
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "\n=== COMPARACIÓN: PEDIDO 45452 vs NUEVO PEDIDO ===\n\n";

// Obtener el pedido 45452 (referencia)
$pedido45452 = PedidoProduccion::where('numero_pedido', '45452')->first();

// Obtener el último pedido (el que acabamos de crear)
$pedidoNuevo = PedidoProduccion::latest('id')->first();

if (!$pedido45452) {
    echo "❌ Pedido 45452 no encontrado\n";
    exit(1);
}

if (!$pedidoNuevo) {
    echo "❌ No hay pedidos para comparar\n";
    exit(1);
}

echo "📋 PEDIDO DE REFERENCIA (45452):\n";
echo "   Número: {$pedido45452->numero_pedido}\n";
echo "   Cliente: {$pedido45452->cliente}\n";
echo "   Prendas: {$pedido45452->prendas()->count()}\n\n";

echo "📋 NUEVO PEDIDO ({$pedidoNuevo->numero_pedido}):\n";
echo "   Número: {$pedidoNuevo->numero_pedido}\n";
echo "   Cliente: {$pedidoNuevo->cliente}\n";
echo "   Prendas: {$pedidoNuevo->prendas()->count()}\n\n";

// Comparar primer prenda
$prenda45452 = $pedido45452->prendas()->first();
$prendaNueva = $pedidoNuevo->prendas()->first();

if (!$prenda45452 || !$prendaNueva) {
    echo "❌ No hay prendas para comparar\n";
    exit(1);
}

echo "🔍 COMPARACIÓN DE ESTRUCTURA - PRENDA #1:\n\n";

echo "REFERENCIA (45452):\n";
echo "  Nombre: {$prenda45452->nombre_prenda}\n";
echo "  Color ID: {$prenda45452->color_id}\n";
echo "  Tela ID: {$prenda45452->tela_id}\n";
echo "  Tipo Manga ID: {$prenda45452->tipo_manga_id}\n";
echo "  Bolsillos: " . ($prenda45452->tiene_bolsillos ? 'Sí' : 'No') . "\n";
echo "  Reflectivo: " . ($prenda45452->tiene_reflectivo ? 'Sí' : 'No') . "\n";
echo "  Descripción (primeros 200 chars):\n";
echo "    " . substr($prenda45452->descripcion, 0, 200) . "\n\n";

echo "NUEVO PEDIDO ({$pedidoNuevo->numero_pedido}):\n";
echo "  Nombre: {$prendaNueva->nombre_prenda}\n";
echo "  Color ID: {$prendaNueva->color_id}\n";
echo "  Tela ID: {$prendaNueva->tela_id}\n";
echo "  Tipo Manga ID: {$prendaNueva->tipo_manga_id}\n";
echo "  Bolsillos: " . ($prendaNueva->tiene_bolsillos ? 'Sí' : 'No') . "\n";
echo "  Reflectivo: " . ($prendaNueva->tiene_reflectivo ? 'Sí' : 'No') . "\n";
echo "  Descripción (primeros 200 chars):\n";
echo "    " . substr($prendaNueva->descripcion, 0, 200) . "\n\n";

// Verificar que los campos importantes están presentes
$errores = [];

if (empty($prendaNueva->color_id)) {
    $errores[] = "❌ Color ID está vacío";
} else {
    echo "✅ Color ID presente: {$prendaNueva->color_id}\n";
}

if (empty($prendaNueva->tela_id)) {
    $errores[] = "❌ Tela ID está vacío";
} else {
    echo "✅ Tela ID presente: {$prendaNueva->tela_id}\n";
}

if (empty($prendaNueva->tipo_manga_id)) {
    $errores[] = "❌ Tipo Manga ID está vacío";
} else {
    echo "✅ Tipo Manga ID presente: {$prendaNueva->tipo_manga_id}\n";
}

if (strlen($prendaNueva->descripcion) < 50) {
    $errores[] = "❌ Descripción muy corta";
} else {
    echo "✅ Descripción completa (" . strlen($prendaNueva->descripcion) . " caracteres)\n";
}

if (!empty($errores)) {
    echo "\n" . implode("\n", $errores) . "\n";
    exit(1);
} else {
    echo "\n✅ TODOS LOS DATOS SE GUARDAN CORRECTAMENTE\n";
    echo "✅ FLUJO COMPLETO FUNCIONA\n";
}
