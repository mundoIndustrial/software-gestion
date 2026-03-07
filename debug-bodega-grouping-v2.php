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
echo "  - Cliente: {$pedido->cliente}\n\n";

// 2. Consultar prendas del pedido
$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoProduccionId)
    ->whereNull('deleted_at')
    ->get();

echo "👕 PRENDAS ENCONTRADAS: " . $prendas->count() . "\n";
echo str_repeat("-", 80) . "\n\n";

foreach ($prendas as $index => $prenda) {
    echo "PRENDA " . ($index + 1) . ":\n";
    echo "  - prenda_pedido_id: {$prenda->id}\n";
    echo "  - prenda_id: " . ($prenda->prenda_id ?? 'N/A') . "\n";
    echo "  - Nombre: {$prenda->nombre_prenda}\n";
    echo "  - Descripción: " . ($prenda->descripcion ?? 'N/A') . "\n";
    echo "  - De bodega: " . ($prenda->de_bodega ? 'SÍ' : 'NO') . "\n\n";
    
    // Obtener tallas de la prenda
    $tallas = DB::table('prenda_pedido_tallas')
        ->where('prenda_pedido_id', $prenda->id)
        ->select(
            'id as prenda_pedido_talla_id',
            'genero',
            'talla',
            'cantidad as cantidad_talla'
        )
        ->get();
    
    if ($tallas->count() > 0) {
        echo "  TALLAS (" . $tallas->count() . "):\n";
        echo "  " . str_repeat("-", 76) . "\n";
        printf("  %-25s %-20s %-15s %-15s\n", "prenda_pedido_talla_id", "Genero", "Talla", "Cantidad");
        echo "  " . str_repeat("-", 76) . "\n";
        
        foreach ($tallas as $talla) {
            printf(
                "  %-25s %-20s %-15s %-15s\n",
                $talla->prenda_pedido_talla_id,
                $talla->genero ?? 'N/A',
                $talla->talla ?? 'N/A',
                $talla->cantidad_talla ?? 0
            );
        }
        echo "  " . str_repeat("-", 76) . "\n\n";
        
        // Para cada talla, obtener los colores
        foreach ($tallas as $talla) {
            $colores = DB::table('prenda_pedido_talla_colores as pptc')
                ->where('pptc.prenda_pedido_talla_id', $talla->prenda_pedido_talla_id)
                ->select(
                    'pptc.id as talla_color_id',
                    'pptc.cantidad',
                    'pptc.color_id',
                    'pptc.color_nombre'
                )
                ->get();
            
            if ($colores->count() > 0) {
                echo "    Colores para Genero: {$talla->genero}, Talla: {$talla->talla}\n";
                echo "    " . str_repeat("-", 72) . "\n";
                printf("    %-20s %-10s %-35s %-10s\n", "talla_color_id", "Color ID", "Color Nombre", "Cantidad");
                echo "    " . str_repeat("-", 72) . "\n";
                
                foreach ($colores as $color) {
                    printf(
                        "    %-20s %-10s %-35s %-10s\n",
                        $color->talla_color_id,
                        $color->color_id ?? 'N/A',
                        $color->color_nombre ?? 'N/A',
                        $color->cantidad
                    );
                }
                echo "    " . str_repeat("-", 72) . "\n\n";
            }
        }
        
        // Agrupar por género
        $porGenero = $tallas->groupBy('genero');
        echo "  AGRUPAMIENTO POR GÉNERO:\n";
        foreach ($porGenero as $genero => $items) {
            echo "    • {$genero}: {$items->count()} talla(s)\n";
            foreach ($items as $item) {
                echo "      - Talla {$item->talla} (ID: {$item->prenda_pedido_talla_id})\n";
            }
        }
    } else {
        echo "  ⚠️  No se encontraron tallas para esta prenda\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

// 3. Consultar bodega_detalles_talla
echo "📦 BODEGA DETALLES TALLA:\n";
echo str_repeat("-", 80) . "\n";

$bodegaDetalles = DB::table('bodega_detalles_talla as bd')
    ->where('bd.pedido_produccion_id', $pedidoProduccionId)
    ->select('bd.*')
    ->get();

if ($bodegaDetalles->count() > 0) {
    echo "Registros en bodega_detalles_talla: " . $bodegaDetalles->count() . "\n\n";
    printf("%-10s %-25s %-20s %-10s %-15s %-20s %-15s\n", 
        "ID", "Prenda", "Talla", "Cant.", "Talla Color ID", "Área", "Estado");
    echo str_repeat("-", 115) . "\n";
    
    foreach ($bodegaDetalles as $detalle) {
        $nombreCorto = substr($detalle->prenda_nombre ?? 'N/A', 0, 23);
        printf("%-10s %-25s %-20s %-10s %-15s %-20s %-15s\n",
            $detalle->id,
            $nombreCorto,
            $detalle->talla ?? 'N/A',
            $detalle->cantidad ?? 0,
            $detalle->talla_color_id ?? 'N/A',
            $detalle->area ?? 'N/A',
            $detalle->estado_bodega ?? 'N/A'
        );
    }
} else {
    echo "⚠️  No hay registros en bodega_detalles_talla para este pedido\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Diagnóstico completado\n";
