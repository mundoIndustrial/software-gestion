#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\EntregaPedidoCostura;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🧪 TEST DE ENTREGA_PEDIDO_COSTURA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Buscar un pedido
echo "Buscando un pedido...\n";
$pedido = PedidoProduccion::where('numero_pedido', 45806)->first();

if (!$pedido) {
    $pedido = PedidoProduccion::first();
}

if (!$pedido) {
    echo "❌ No hay pedidos\n";
    exit(1);
}

$prenda = $pedido->prendas()->first();
if (!$prenda) {
    echo "❌ El pedido no tiene prendas\n";
    exit(1);
}

$talla = $prenda->tallas()->first();
if (!$talla) {
    echo "❌ La prenda no tiene tallas\n";
    exit(1);
}

// Test 1: Crear con todos los campos
echo "\n📝 Test 1: Crear entrega con descripción nula\n";
$data1 = [
    'pedido' => $pedido->numero_pedido,
    'cliente' => $pedido->cliente,
    'prenda' => $prenda->nombre_prenda,
    'descripcion' => null,
    'talla' => $talla->talla,
    'cantidad_entregada' => 5,
    'fecha_entrega' => now()->toDateString(),
    'costurero' => 'COSTURERO-1',
    'mes_ano' => 'febrero 2026',
];

echo "Datos:\n";
foreach ($data1 as $key => $value) {
    echo "  • $key: " . ($value === null ? 'null' : $value) . "\n";
}

try {
    $entrega1 = EntregaPedidoCostura::create($data1);
    echo "✓ Entrega 1 creada (ID: {$entrega1->id})\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Test 2: Crear con descripción vacía
echo "\n📝 Test 2: Crear entrega con descripción vacía\n";
$data2 = [
    'pedido' => $pedido->numero_pedido,
    'cliente' => $pedido->cliente,
    'prenda' => $prenda->nombre_prenda,
    'descripcion' => '',
    'talla' => $talla->talla,
    'cantidad_entregada' => 5,
    'fecha_entrega' => now()->toDateString(),
    'costurero' => 'COSTURERO-2',
    'mes_ano' => null,
];

try {
    $entrega2 = EntregaPedidoCostura::create($data2);
    echo "✓ Entrega 2 creada (ID: {$entrega2->id})\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Crear con descripción desde prenda
echo "\n📝 Test 3: Crear entrega con descripción de prenda\n";
$data3 = [
    'pedido' => $pedido->numero_pedido,
    'cliente' => $pedido->cliente,
    'prenda' => $prenda->nombre_prenda,
    'descripcion' => $prenda->descripcion ?? 'Sin descripción',
    'talla' => $talla->talla,
    'cantidad_entregada' => 5,
    'fecha_entrega' => now()->toDateString(),
    'costurero' => 'COSTURERO-3',
    'mes_ano' => 'febrero 2026',
];

try {
    $entrega3 = EntregaPedidoCostura::create($data3);
    echo "✓ Entrega 3 creada (ID: {$entrega3->id})\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Mostrar registros creados
echo "\n📊 Registros en entregas_pedido_costura:\n";
$registros = EntregaPedidoCostura::orderBy('id', 'desc')->limit(5)->get();
foreach ($registros as $reg) {
    echo "  • ID: {$reg->id} | Pedido: {$reg->pedido} | Prenda: {$reg->prenda} | Costurero: {$reg->costurero}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Tests completados\n";
echo "═══════════════════════════════════════════════════════════════\n";
