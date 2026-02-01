#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\EntregaPrendaPedido;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🧪 TEST DE ENTREGA DE COSTURA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Buscar un pedido
echo "1️⃣  Buscando un pedido de prueba...\n";
$pedido = PedidoProduccion::where('numero_pedido', 45806)->first();

if (!$pedido) {
    echo "   ❌ No se encontró pedido 45806\n";
    $pedido = PedidoProduccion::first();
    if ($pedido) {
        echo "   ✓ Usando pedido: " . $pedido->numero_pedido . "\n";
    } else {
        echo "   ❌ No hay pedidos en la BD\n";
        exit(1);
    }
} else {
    echo "   ✓ Pedido encontrado: " . $pedido->numero_pedido . "\n";
}

// 2. Buscar una prenda
echo "\n2️⃣  Buscando prendas del pedido...\n";
$prendas = $pedido->prendas()->get();

if ($prendas->isEmpty()) {
    echo "   ❌ El pedido no tiene prendas\n";
    exit(1);
} else {
    echo "   ✓ Encontradas " . $prendas->count() . " prenda(s)\n";
    foreach ($prendas as $p) {
        echo "      • {$p->nombre_prenda} (ID: {$p->id})\n";
    }
}

$prenda = $prendas->first();

// 3. Buscar tallas
echo "\n3️⃣  Buscando tallas de la prenda...\n";
$tallas = $prenda->tallas()->get();

if ($tallas->isEmpty()) {
    echo "   ❌ La prenda no tiene tallas\n";
    exit(1);
} else {
    echo "   ✓ Encontradas " . $tallas->count() . " talla(s)\n";
    foreach ($tallas as $t) {
        echo "      • {$t->talla} - Cantidad: {$t->cantidad}\n";
    }
}

$talla = $tallas->first();

// 4. Crear entrega de prueba
echo "\n4️⃣  Intentando crear una entrega...\n";

$entrega = [
    'numero_pedido' => $pedido->numero_pedido,
    'nombre_prenda' => $prenda->nombre_prenda,
    'talla' => $talla->talla,
    'cantidad_original' => $talla->cantidad,
    'costurero' => 'TEST-COSTURERO',
    'total_producido_por_talla' => 5,
    'total_pendiente_por_talla' => $talla->cantidad - 5,
    'fecha_completado' => null,
];

echo "   Datos de entrega:\n";
foreach ($entrega as $key => $value) {
    echo "      • $key: $value\n";
}

try {
    $nuevaEntrega = EntregaPrendaPedido::create($entrega);
    echo "\n   ✓ Entrega creada exitosamente (ID: {$nuevaEntrega->id})\n";
    
    // Verificar que se guardó correctamente
    $verificar = EntregaPrendaPedido::find($nuevaEntrega->id);
    echo "   ✓ Entrega verificada en BD\n";
    
} catch (\Exception $e) {
    echo "\n   ❌ Error al crear entrega:\n";
    echo "      Mensaje: " . $e->getMessage() . "\n";
    echo "      Archivo: " . $e->getFile() . "\n";
    echo "      Línea: " . $e->getLine() . "\n";
    
    if (env('APP_DEBUG')) {
        echo "\n      Stack trace:\n";
        foreach (explode("\n", $e->getTraceAsString()) as $line) {
            echo "      $line\n";
        }
    }
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Test completado exitosamente\n";
echo "═══════════════════════════════════════════════════════════════\n";
