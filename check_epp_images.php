<?php
/**
 * Verificar Imágenes EPP del Pedido 90148
 */
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pedido = \App\Models\PedidoProduccion::where('numero_pedido', 90148)->first();

if (!$pedido) {
    echo "❌ Pedido 90148 no encontrado\n";
    exit;
}

echo "=== Pedido #" . $pedido->numero_pedido . " ===\n";
echo "ID: " . $pedido->id . "\n";
echo "Estado: " . $pedido->estado . "\n\n";

$pedidosEpp = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedido->id)
    ->with(['epp', 'imagenes'])
    ->get();

echo "EPP encontrados: " . $pedidosEpp->count() . "\n";
echo "─────────────────────────────────────────────────────\n\n";

if ($pedidosEpp->isEmpty()) {
    echo "⚠️  No hay EPP en este pedido\n";
    exit;
}

foreach ($pedidosEpp as $idx => $pe) {
    echo "EPP #" . ($idx + 1) . ":\n";
    echo "  ├─ ID: " . $pe->id . "\n";
    echo "  ├─ Nombre: " . ($pe->epp?->nombre ?? 'N/A') . "\n";
    echo "  ├─ Cantidad: " . $pe->cantidad . "\n";
    echo "  ├─ Talla: " . ($pe->tallas_medidas ?? 'N/A') . "\n";
    
    if ($pe->imagenes->isEmpty()) {
        echo "  └─ 📷 Imágenes: NINGUNA ❌\n";
    } else {
        echo "  └─ 📷 Imágenes: " . $pe->imagenes->count() . "\n";
        foreach ($pe->imagenes as $imgIdx => $img) {
            $marca = $img->principal ? '🌟' : '  ';
            echo "      └─ [$imgIdx] $marca " . $img->archivo . " (Orden: {$img->orden})\n";
        }
    }
    echo "\n";
}
