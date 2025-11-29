<?php

/**
 * Script de Test - Módulo de Insumos
 * Verifica que la migración a pedidos_produccion funcione correctamente
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\MaterialesOrdenInsumos;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST - MÓDULO DE INSUMOS (pedidos_produccion)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// TEST 1: Verificar que la tabla materiales_orden_insumos tiene pedido_produccion_id
echo "✓ TEST 1: Verificar estructura de tabla materiales_orden_insumos\n";
try {
    $columns = DB::select("SHOW COLUMNS FROM materiales_orden_insumos");
    $columnNames = array_map(fn($col) => $col->Field, $columns);
    
    if (in_array('pedido_produccion_id', $columnNames)) {
        echo "  ✅ Columna 'pedido_produccion_id' existe\n";
    } else {
        echo "  ❌ Columna 'pedido_produccion_id' NO existe\n";
    }
    
    if (!in_array('tabla_original_pedido', $columnNames)) {
        echo "  ✅ Columna 'tabla_original_pedido' fue eliminada\n";
    } else {
        echo "  ❌ Columna 'tabla_original_pedido' aún existe\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 2: Contar pedidos_produccion
echo "\n✓ TEST 2: Contar registros en pedidos_produccion\n";
try {
    $count = PedidoProduccion::count();
    echo "  ✅ Total de pedidos: $count\n";
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 3: Contar prendas_pedido
echo "\n✓ TEST 3: Contar registros en prendas_pedido\n";
try {
    $count = PrendaPedido::count();
    echo "  ✅ Total de prendas: $count\n";
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 4: Contar materiales_orden_insumos
echo "\n✓ TEST 4: Contar registros en materiales_orden_insumos\n";
try {
    $count = MaterialesOrdenInsumos::count();
    echo "  ✅ Total de materiales: $count\n";
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 5: Verificar relación MaterialesOrdenInsumos -> PedidoProduccion
echo "\n✓ TEST 5: Verificar relación MaterialesOrdenInsumos -> PedidoProduccion\n";
try {
    $material = MaterialesOrdenInsumos::first();
    if ($material) {
        $pedido = $material->pedido;
        if ($pedido) {
            echo "  ✅ Relación funciona: Material ID {$material->id} -> Pedido ID {$pedido->id}\n";
        } else {
            echo "  ❌ Relación no devuelve pedido\n";
        }
    } else {
        echo "  ⚠️  No hay materiales para probar\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 6: Verificar que los materiales tienen pedido_produccion_id
echo "\n✓ TEST 6: Verificar que materiales tienen pedido_produccion_id\n";
try {
    $materialesConPedido = MaterialesOrdenInsumos::whereNotNull('pedido_produccion_id')->count();
    $materialesTotal = MaterialesOrdenInsumos::count();
    echo "  ✅ Materiales con pedido_produccion_id: $materialesConPedido de $materialesTotal\n";
    
    if ($materialesConPedido === $materialesTotal && $materialesTotal > 0) {
        echo "  ✅ Todos los materiales tienen pedido_produccion_id\n";
    } elseif ($materialesTotal === 0) {
        echo "  ⚠️  No hay materiales en la BD\n";
    } else {
        echo "  ❌ Algunos materiales no tienen pedido_produccion_id\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 7: Verificar que NO hay materiales con tabla_original_pedido
echo "\n✓ TEST 7: Verificar que NO hay materiales con tabla_original_pedido\n";
try {
    $materialesAntiguos = DB::table('materiales_orden_insumos')
        ->whereNotNull('tabla_original_pedido')
        ->count();
    
    if ($materialesAntiguos === 0) {
        echo "  ✅ No hay materiales con tabla_original_pedido (migración exitosa)\n";
    } else {
        echo "  ❌ Hay $materialesAntiguos materiales con tabla_original_pedido\n";
    }
} catch (\Exception $e) {
    echo "  ⚠️  Columna tabla_original_pedido no existe (esperado)\n";
}

// TEST 8: Verificar descripción_prendas en un pedido
echo "\n✓ TEST 8: Verificar descripción_prendas en un pedido\n";
try {
    $pedido = PedidoProduccion::with('prendas')->first();
    if ($pedido) {
        echo "  Pedido ID: {$pedido->id}\n";
        echo "  Número Pedido: {$pedido->numero_pedido}\n";
        echo "  Prendas: {$pedido->prendas->count()}\n";
        
        if ($pedido->prendas->isNotEmpty()) {
            $descripcionesArray = $pedido->prendas->map(function($prenda) {
                $desc = $prenda->nombre_prenda;
                if ($prenda->cantidad) {
                    $desc .= " (Cant: {$prenda->cantidad})";
                }
                return $desc;
            })->toArray();
            $descripcion = implode(' | ', $descripcionesArray);
            echo "  ✅ Descripción armada: $descripcion\n";
        } else {
            echo "  ⚠️  El pedido no tiene prendas\n";
        }
    } else {
        echo "  ⚠️  No hay pedidos para probar\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 9: Verificar que el filtro de numero_pedido funciona
echo "\n✓ TEST 9: Verificar que el filtro de numero_pedido funciona\n";
try {
    $pedido = PedidoProduccion::first();
    if ($pedido) {
        $filtrado = PedidoProduccion::where('numero_pedido', $pedido->numero_pedido)->first();
        if ($filtrado) {
            echo "  ✅ Filtro por numero_pedido funciona: {$filtrado->numero_pedido}\n";
        } else {
            echo "  ❌ Filtro por numero_pedido no funciona\n";
        }
    } else {
        echo "  ⚠️  No hay pedidos para probar\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// TEST 10: Verificar que el filtro de cliente funciona
echo "\n✓ TEST 10: Verificar que el filtro de cliente funciona\n";
try {
    $pedido = PedidoProduccion::whereNotNull('cliente')->first();
    if ($pedido) {
        $filtrado = PedidoProduccion::where('cliente', 'LIKE', "%{$pedido->cliente}%")->first();
        if ($filtrado) {
            echo "  ✅ Filtro por cliente funciona: {$filtrado->cliente}\n";
        } else {
            echo "  ❌ Filtro por cliente no funciona\n";
        }
    } else {
        echo "  ⚠️  No hay pedidos con cliente para probar\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TESTS COMPLETADOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
