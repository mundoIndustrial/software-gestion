<?php

/**
 * Test para verificar que el endpoint obtenerFotosPrendaPedido funciona correctamente
 * 
 * Ejecutar: php artisan tinker < test_fotos_prenda_pedido.php
 * O directamente desde la terminal de tinker
 */

// Simulación para verificar el flujo de datos

// 1. Obtener un pedido con prendas
$pedido = \App\Models\PedidoProduccion::with('prendas.fotos')->first();

if (!$pedido) {
    echo "❌ No hay pedidos en la base de datos\n";
    return;
}

echo "📋 Pedido encontrado: #{$pedido->numero_pedido}\n";

// 2. Obtener una prenda del pedido
$prenda = $pedido->prendas()->first();

if (!$prenda) {
    echo "❌ El pedido no tiene prendas\n";
    return;
}

echo "👕 Prenda encontrada: {$prenda->nombre_prenda} (ID: {$prenda->id})\n";

// 3. Verificar fotos de la prenda
$fotos = \DB::table('prenda_fotos_pedido')
    ->where('prenda_pedido_id', $prenda->id)
    ->whereNull('deleted_at')
    ->orderBy('orden', 'asc')
    ->select('ruta_webp', 'ruta_original')
    ->get();

echo "📸 Fotos encontradas: " . count($fotos) . "\n";

if (count($fotos) > 0) {
    echo "✅ OK - El endpoint debería retornar:\n";
    echo json_encode([
        'success' => true,
        'fotos' => $fotos->map(function($f) {
            return [
                'ruta_webp' => $f->ruta_webp,
                'ruta_original' => $f->ruta_original,
            ];
        })->toArray(),
    ], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "⚠️  No hay fotos para esta prenda\n";
}

// 4. Verificar el flujo de datos del recibo
$datos = app(\App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository::class)->obtenerDatosRecibos($pedido->id);

if (isset($datos['prendas'][0])) {
    $prendaDelRecibo = $datos['prendas'][0];
    echo "\n📦 Datos del recibo para la primera prenda:\n";
    echo "   - prenda_pedido_id: " . ($prendaDelRecibo['prenda_pedido_id'] ?? 'NO ENCONTRADO') . "\n";
    echo "   - id: " . ($prendaDelRecibo['id'] ?? 'NO ENCONTRADO') . "\n";
}
