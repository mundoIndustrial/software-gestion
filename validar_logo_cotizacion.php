<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LogoCotizacion;

// Verificar que LogoCotizacion tiene los IDs
$logoCotizaciones = LogoCotizacion::take(5)->get();

echo "=== VALIDACIÓN DE LOGO COTIZACIONES ===\n\n";

foreach ($logoCotizaciones as $logo) {
    echo "ID: " . $logo->id . "\n";
    echo "  - Descripción: " . substr($logo->descripcion ?? '', 0, 50) . "...\n";
    $tecnicasCount = is_array($logo->tecnicas) ? count($logo->tecnicas) : count(json_decode($logo->tecnicas ?? '[]', true));
    echo "  - Tecnicas: " . $tecnicasCount . "\n";
    echo "  - Creada: " . $logo->created_at . "\n\n";
}

// Verificar que LogoPedido tiene la relación correcta
echo "\n=== VALIDACIÓN DE LOGO PEDIDOS (si existen) ===\n\n";

$logoPedidos = \App\Models\LogoPedido::take(3)->get();

foreach ($logoPedidos as $pedido) {
    echo "ID Pedido: " . $pedido->id . "\n";
    echo "  - Logo Cotización ID: " . $pedido->logo_cotizacion_id . "\n";
    echo "  - Pedido ID: " . $pedido->pedido_id . "\n";
    echo "  - Número Pedido: " . $pedido->numero_pedido . "\n\n";
}

echo "\n✅ Validación completada\n";
