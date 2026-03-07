<?php

/**
 * Script de diagnóstico para revisar agrupamiento de datos en Bodega
 * Pedido ID: 43 (Número de pedido 45)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNÓSTICO DE AGRUPAMIENTO - BODEGA ===\n\n";

$pedidoProduccionId = 43;

echo "Consultando datos del pedido_produccion_id: {$pedidoProduccionId}\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Obtener datos del pedido
$pedido = DB::table('pedidos_produccion')->where('id', $pedidoProduccionId)->first();
if (!$pedido) {
    echo "❌ No se encontró el pedido con ID {$pedidoProduccionId}\n";
    exit(1);
}

echo "📋 PEDIDO:\n";
echo "  - ID: {$pedido->id}\n";
echo "  - Número: {$pedido->numero_pedido}\n";
echo "  - Cliente: " . ($pedido->empresa ?? $pedido->cliente ?? 'N/A') . "\n";
echo "  - Asesor: " . ($pedido->asesor ?? 'N/A') . "\n\n";

// 2. Consultar prendas del pedido con sus variantes
$recibos = DB::table('recibo_prendas as rp')
    ->where('rp.pedido_produccion_id', $pedidoProduccionId)
    ->select('rp.*')
    ->get();

echo "👕 RECIBOS DE PRENDAS ENCONTRADOS: " . $recibos->count() . "\n";
echo str_repeat("-", 80) . "\n\n";

foreach ($recibos as $index => $recibo) {
    echo "PRENDA " . ($index + 1) . ":\n";
    echo "  - recibo_prenda_id: {$recibo->id}\n";
    echo "  - prenda_id: {$recibo->prenda_id}\n";
    
    // Obtener nombre de la prenda
    $prenda = DB::table('prendas')->where('id', $recibo->prenda_id)->first();
    echo "  - Nombre: " . ($prenda ? $prenda->nombre : 'N/A') . "\n";
    echo "  - De bodega: " . ($recibo->de_bodega ? 'SÍ' : 'NO') . "\n";
    
    // Obtener variantes (tallas/géneros/colores)
    $variantes = DB::table('tallas_colores as tc')
        ->join('tallas as t', 'tc.talla_id', '=', 't.id')
        ->leftJoin('generos as g', 't.genero_id', '=', 'g.id')
        ->leftJoin('colores as c', 'tc.color_id', '=', 'c.id')
        ->where('tc.prenda_id', $recibo->prenda_id)
        ->where('tc.pedido_produccion_id', $pedidoProduccionId)
        ->where('tc.cantidad', '>', 0)
        ->select(
            'tc.id as talla_color_id',
            'tc.cantidad',
            't.nombre as talla',
            'g.nombre as genero',
            'c.nombre as color'
        )
        ->orderBy('g.nombre')
        ->orderBy('t.nombre')
        ->get();
    
    echo "  - Variantes encontradas: " . $variantes->count() . "\n\n";
    
    if ($variantes->count() > 0) {
        echo "  DETALLES DE VARIANTES:\n";
        echo "  " . str_repeat("-", 76) . "\n";
        printf("  %-20s %-15s %-15s %-10s %-15s\n", "Género", "Talla", "Color", "Cantidad", "talla_color_id");
        echo "  " . str_repeat("-", 76) . "\n";
        
        foreach ($variantes as $variante) {
            printf(
                "  %-20s %-15s %-15s %-10s %-15s\n",
                $variante->genero ?? 'N/A',
                $variante->talla ?? 'N/A',
                $variante->color ?? 'SIN COLOR',
                $variante->cantidad,
                $variante->talla_color_id
            );
        }
        
        echo "  " . str_repeat("-", 76) . "\n";
        
        // Agrupar por género
        $porGenero = $variantes->groupBy('genero');
        echo "\n  AGRUPAMIENTO POR GÉNERO:\n";
        foreach ($porGenero as $genero => $items) {
            $totalCantidad = $items->sum('cantidad');
            echo "    • {$genero}: {$items->count()} talla(s), Total: {$totalCantidad} unidades\n";
            foreach ($items as $item) {
                echo "      - Talla {$item->talla}: {$item->cantidad} uds (color: {$item->color})\n";
            }
        }
        
        // Agrupar por color y luego por género
        $porColor = $variantes->groupBy('color');
        echo "\n  AGRUPAMIENTO POR COLOR → GÉNERO:\n";
        foreach ($porColor as $color => $itemsColor) {
            echo "    🎨 Color: " . ($color ?: 'SIN COLOR') . "\n";
            $porGeneroEnColor = $itemsColor->groupBy('genero');
            foreach ($porGeneroEnColor as $genero => $items) {
                $totalCantidad = $items->sum('cantidad');
                echo "       • {$genero}: {$items->count()} talla(s), Total: {$totalCantidad} unidades\n";
                foreach ($items as $item) {
                    echo "         - Talla {$item->talla}: {$item->cantidad} uds\n";
                }
            }
        }
    } else {
        echo "  ⚠️  No se encontraron variantes para esta prenda\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

// 3. Consultar bodega_detalles
echo "📦 BODEGA DETALLES:\n";
echo str_repeat("-", 80) . "\n";

$bodegaDetalles = DB::table('bodega_detalles as bd')
    ->leftJoin('tallas_colores as tc', 'bd.talla_color_id', '=', 'tc.id')
    ->leftJoin('tallas as t', 'tc.talla_id', '=', 't.id')
    ->leftJoin('generos as g', 't.genero_id', '=', 'g.id')
    ->leftJoin('colores as c', 'tc.color_id', '=', 'c.id')
    ->where('bd.pedido_produccion_id', $pedidoProduccionId)
    ->select(
        'bd.id as bodega_detalle_id',
        'bd.recibo_prenda_id',
        'bd.talla_color_id',
        'bd.talla',
        'bd.area',
        'bd.estado_bodega',
        't.nombre as talla_nombre',
        'g.nombre as genero',
        'c.nombre as color',
        'tc.cantidad'
    )
    ->get();

if ($bodegaDetalles->count() > 0) {
    echo "Registros en bodega_detalles: " . $bodegaDetalles->count() . "\n\n";
    printf("%-10s %-20s %-15s %-15s %-15s %-20s %-15s\n", 
        "Detalle ID", "Género", "Talla", "Color", "Cantidad", "Área", "Estado");
    echo str_repeat("-", 110) . "\n";
    
    foreach ($bodegaDetalles as $detalle) {
        printf("%-10s %-20s %-15s %-15s %-15s %-20s %-15s\n",
            $detalle->bodega_detalle_id,
            $detalle->genero ?? 'N/A',
            $detalle->talla ?? $detalle->talla_nombre ?? 'N/A',
            $detalle->color ?? 'SIN COLOR',
            $detalle->cantidad ?? 0,
            $detalle->area ?? 'N/A',
            $detalle->estado_bodega ?? 'N/A'
        );
    }
} else {
    echo "⚠️  No hay registros en bodega_detalles para este pedido\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Diagnóstico completado\n";
