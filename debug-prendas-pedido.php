<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "===========================================\n";
echo "DEBUG PEDIDO 1 - ESTRUCTURA COMPLETA\n";
echo "===========================================\n\n";

$pedido = \App\Models\PedidoProduccion::where('numero_pedido', '1')->first();

if (!$pedido) {
    echo "❌ No se encontró el pedido\n";
    exit;
}

echo "✅ Pedido encontrado (ID: {$pedido->id})\n\n";

echo "=== PRENDAS DE LA TABLA prendas_pedido ===\n";
$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido->id)
    ->whereNull('deleted_at')
    ->get();

if ($prendas->isEmpty()) {
    echo "⚠️ No hay prendas en la tabla prendas_pedido\n";
} else {
    echo "✅ Encontradas {$prendas->count()} prendas:\n\n";
    
    foreach ($prendas as $prenda) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "PRENDA ID: {$prenda->id}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "nombre_prenda: " . ($prenda->nombre_prenda ?? 'NULL') . "\n";
        echo "de_bodega: " . ($prenda->de_bodega ?? '0') . "\n";
        
        // Obtener tallas de esta prenda
        $tallas = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->get();
        
        echo "\nTallas (prenda_pedido_tallas):\n";
        if ($tallas->isEmpty()) {
            echo "  ⚠️ No hay tallas registradas\n";
        } else {
            $totalCantidad = 0;
            foreach ($tallas as $talla) {
                echo "  - Talla: {$talla->talla} | Género: {$talla->genero} | Cantidad: {$talla->cantidad}\n";
                $totalCantidad += $talla->cantidad;
            }
            echo "  TOTAL: {$totalCantidad} unidades\n";
        }
        
        // Mostrar descripcion si existe
        if (!empty($prenda->descripcion)) {
            echo "\ndescripcion (JSON):\n";
            $descripcionData = json_decode($prenda->descripcion, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "  ✅ JSON válido:\n";
                print_r($descripcionData);
            } else {
                echo "  Texto plano: " . substr($prenda->descripcion, 0, 200) . "...\n";
            }
        }
        echo "\n";
    }
}

echo "\n\n=== COMPARACIÓN CON TABLAS DE BODEGA ===\n";

foreach ($prendas as $prenda) {
    $nombrePrenda = $prenda->nombre_prenda ?? 'SIN NOMBRE';
    
    // Obtener tallas de esta prenda
    $tallas = DB::table('prenda_pedido_tallas')
        ->where('prenda_pedido_id', $prenda->id)
        ->get();
    
    if ($tallas->isEmpty()) continue;
    
    echo "\n━━━ {$nombrePrenda} ━━━\n";
    
    // Calcular cantidad total de la prenda
    $cantidadTotal = $tallas->sum('cantidad');
    
    foreach ($tallas as $tallaRow) {
        $talla = $tallaRow->talla;
        $cantidad = $tallaRow->cantidad;
        
        echo "\n  Talla: {$talla}, Cantidad: {$cantidad}\n";
        
        // Buscar en bodega_detalles_talla con los 4 criterios
        $bodegaBase = DB::table('bodega_detalles_talla')
            ->where('numero_pedido', '1')
            ->where('talla', $talla)
            ->where('prenda_nombre', $nombrePrenda)
            ->where('cantidad', $cantidadTotal)  // Usa cantidad total de la prenda
            ->first();
        
        echo "    bodega_detalles_talla: ";
        if ($bodegaBase) {
            echo "✅ ID {$bodegaBase->id} - Estado: {$bodegaBase->estado_bodega}, Área: {$bodegaBase->area}\n";
        } else {
            echo "❌ NO ENCONTRADO con cantidad={$cantidadTotal}\n";
            
            // Mostrar qué hay en bodega con esa talla
            $alternativas = DB::table('bodega_detalles_talla')
                ->where('numero_pedido', '1')
                ->where('talla', $talla)
                ->get();
            
            if ($alternativas->count() > 0) {
                echo "       Registros existentes con talla {$talla}:\n";
                foreach ($alternativas as $alt) {
                    echo "         - ID {$alt->id}: prenda='{$alt->prenda_nombre}', cant={$alt->cantidad}, estado={$alt->estado_bodega}\n";
                }
            }
        }
        
        // Buscar en costura_bodega_detalles con los 4 criterios
        $costura = DB::table('costura_bodega_detalles')
            ->where('numero_pedido', '1')
            ->where('talla', $talla)
            ->where('prenda_nombre', $nombrePrenda)
            ->where('cantidad', $cantidadTotal)  // Usa cantidad total de la prenda
            ->first();
        
        echo "    costura_bodega_detalles: ";
        if ($costura) {
            echo "✅ ID {$costura->id} - Estado: {$costura->estado_bodega}\n";
        } else {
            echo "❌ NO ENCONTRADO con cantidad={$cantidadTotal}\n";
            
            // Mostrar qué hay en costura con esa talla
            $alternativasCostura = DB::table('costura_bodega_detalles')
                ->where('numero_pedido', '1')
                ->where('talla', $talla)
                ->get();
            
            if ($alternativasCostura->count() > 0) {
                echo "       Registros existentes en costura con talla {$talla}:\n";
                foreach ($alternativasCostura as $alt) {
                    echo "         - ID {$alt->id}: prenda='{$alt->prenda_nombre}', cant={$alt->cantidad}, estado={$alt->estado_bodega}\n";
                }
            }
        }
    }
}

echo "\n\n=== RESUMEN DE INCONSISTENCIAS ===\n";
echo "\n¿Por qué no se muestran los estados en el frontend?\n";
echo "1. Verificar que el controlador usa cantidad TOTAL de la prenda\n";
echo "2. Verificar que los registros en bodega tienen el MISMO valor de cantidad\n";
echo "3. Los 4 criterios deben coincidir EXACTAMENTE: numero_pedido, talla, prenda_nombre, cantidad\n";

echo "\n\n=== FIN DEL DEBUG ===\n";
