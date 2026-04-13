<?php
/**
 * Script de debug para diagnosticar el problema de variantes
 * Verificar qué está retornando construirVariantesArray
 */

require __DIR__ . '/vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    
    // Bind into container to enable database access
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

use App\Models\PedidoProduccion;
use App\Infrastructure\Services\Pedidos\FacturaPedidoService;

// ID del pedido a debuggear
$pedidoId = 176;
$prendaId = 161;

echo "=== DEBUG VARIANTES ===\n";
echo "Pedido ID: $pedidoId\n";
echo "Prenda ID: $prendaId\n\n";

// 1. Cargar con relaciones
$pedido = PedidoProduccion::with([
    'prendas.tallas',
    'prendas.variantes',
])->find($pedidoId);

if (!$pedido) {
    echo "❌ Pedido no encontrado\n";
    exit(1);
}

echo "✅ Pedido encontrado\n\n";

// 2. Buscar la prenda
$prenda = $pedido->prendas->where('id', $prendaId)->first();

if (!$prenda) {
    echo "❌ Prenda no encontrada\n";
    exit(1);
}

echo "✅ Prenda encontrada\n";
echo "Nombre: " . $prenda->nombre_prenda . "\n\n";

// 3. Verificar tallas
echo "--- TALLAS EN BD ---\n";
if ($prenda->tallas && $prenda->tallas->count() > 0) {
    echo "✅ Tallas encontradas: " . $prenda->tallas->count() . "\n\n";
    foreach ($prenda->tallas as $idx => $talla) {
        echo "Talla[$idx]:\n";
        echo "  - ID: " . $talla->id . "\n";
        echo "  - Genero: " . $talla->genero . "\n";
        echo "  - Talla: " . $talla->talla . "\n";
        echo "  - Cantidad: " . $talla->cantidad . "\n";
        echo "  - Es Sobremedida: " . ($talla->es_sobremedida ? 'Si' : 'No') . "\n";
        echo "\n";
    }
} else {
    echo "❌ NO hay tallas\n\n";
}

// 4. Verificar variantes de BD
echo "--- VARIANTES EN BD ---\n";
if ($prenda->variantes && $prenda->variantes->count() > 0) {
    echo "✅ Variantes encontradas: " . $prenda->variantes->count() . "\n\n";
    foreach ($prenda->variantes as $idx => $variante) {
        echo "Variante[$idx]:\n";
        echo "  - ID: " . $variante->id . "\n";
        echo "  - Talla (DB): " . ($variante->talla ?? 'NULL') . "\n";
        echo "  - Genero (DB): " . ($variante->genero ?? 'NULL') . "\n";
        echo "  - Cantidad (DB): " . ($variante->cantidad ?? 'NULL') . "\n";
        echo "\n";
    }
} else {
    echo "❌ NO hay variantes\n\n";
}

// 5. Simular construirVariantesArray
echo "--- SIMULANDO construirVariantesArray ---\n";
$variantesArray = [];

if ($prenda->tallas && $prenda->tallas->count() > 0) {
    echo "✅ Usando TALLAS para construir variantes\n\n";
    
    foreach ($prenda->tallas as $idx => $talla) {
        $item = [
            'talla_id' => $talla->id,
            'talla' => $talla->talla ?? 'N/A',
            'genero' => $talla->genero ?? 'N/A',
            'cantidad' => $talla->cantidad ?? 0,
            'es_sobremedida' => (bool)($talla->es_sobremedida ?? false),
        ];
        
        $variantesArray[] = $item;
        
        echo "Agregada variante[$idx]:\n";
        echo "  - Talla: " . $item['talla'] . "\n";
        echo "  - Genero: " . $item['genero'] . "\n";
        echo "  - Cantidad: " . $item['cantidad'] . "\n";
        echo "\n";
    }
} else {
    echo "❌ NO hay tallas - creando desde variantes BD\n\n";
    
    if ($prenda->variantes && $prenda->variantes->count() > 0) {
        foreach ($prenda->variantes as $idx => $variante) {
            $item = [
                'talla' => $variante->talla ?? 'N/A',
                'genero' => $variante->genero ?? 'N/A',
                'cantidad' => $variante->cantidad ?? 0,
            ];
            
            $variantesArray[] = $item;
            
            echo "Agregada variante[$idx]:\n";
            echo "  - Talla: " . $item['talla'] . "\n";
            echo "  - Genero: " . $item['genero'] . "\n";
            echo "  - Cantidad: " . $item['cantidad'] . "\n";
            echo "\n";
        }
    }
}

// 6. Resultado final
echo "--- RESULTADO FINAL ---\n";
echo "Total variantes construidas: " . count($variantesArray) . "\n\n";

echo "Array final:\n";
echo json_encode($variantesArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n✅ Debug completado\n";
