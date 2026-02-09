<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "===========================================\n";
echo "DEBUG PEDIDO 1 - BÚSQUEDA POR 4 CRITERIOS\n";
echo "===========================================\n\n";

// 1. BUSCAR PEDIDO PRODUCCIÓN
$pedido = \App\Models\PedidoProduccion::where('numero_pedido', '1')->first();

if (!$pedido) {
    echo "❌ No se encontró el pedido de producción #1\n";
    exit;
}

echo "✅ PEDIDO ENCONTRADO\n";
echo "   ID: {$pedido->id}\n";
echo "   Número: {$pedido->numero_pedido}\n";
echo "   Estado: {$pedido->estado}\n\n";

// 2. OBTENER PRENDAS DEL PEDIDO
echo "=== PRENDAS DEL PEDIDO (desde JSON) ===\n";
$prendas = json_decode($pedido->prendas_json, true) ?? [];
foreach ($prendas as $index => $prenda) {
    echo "\nPrenda #{$index}:\n";
    echo "   Nombre: {$prenda['nombre']}\n";
    echo "   Cantidad: {$prenda['cantidad']}\n";
    
    if (isset($prenda['variantes']) && is_array($prenda['variantes'])) {
        echo "   Variantes:\n";
        foreach ($prenda['variantes'] as $variante) {
            $talla = $variante['talla'] ?? 'N/A';
            $cantidad = $variante['cantidad'] ?? 0;
            echo "      - Talla: {$talla}, Cantidad: {$cantidad}\n";
        }
    }
}

echo "\n\n=== TABLA: bodega_detalles_talla ===\n";
$bodegaBase = \App\Models\BodegaDetallesTalla::where('numero_pedido', '1')->get();

if ($bodegaBase->isEmpty()) {
    echo "⚠️ No hay registros en bodega_detalles_talla\n";
} else {
    echo "✅ Encontrados {$bodegaBase->count()} registros:\n\n";
    foreach ($bodegaBase as $registro) {
        echo "ID: {$registro->id}\n";
        echo "   Talla: {$registro->talla}\n";
        echo "   Prenda: {$registro->prenda_nombre}\n";
        echo "   Cantidad: {$registro->cantidad}\n";
        echo "   Estado: {$registro->estado_bodega}\n";
        echo "   Área: {$registro->area}\n";
        echo "   ----\n";
    }
}

echo "\n\n=== TABLA: costura_bodega_detalles ===\n";
$costura = \App\Models\CosturaBodegaDetalle::where('numero_pedido', '1')->get();

if ($costura->isEmpty()) {
    echo "⚠️ No hay registros en costura_bodega_detalles\n";
} else {
    echo "✅ Encontrados {$costura->count()} registros:\n\n";
    foreach ($costura as $registro) {
        echo "ID: {$registro->id}\n";
        echo "   Talla: {$registro->talla}\n";
        echo "   Prenda: {$registro->prenda_nombre}\n";
        echo "   Cantidad: {$registro->cantidad}\n";
        echo "   Estado: {$registro->estado_bodega}\n";
        echo "   ----\n";
    }
}

echo "\n\n=== TABLA: epp_bodega_detalles ===\n";
$epp = \App\Models\EppBodegaDetalle::where('numero_pedido', '1')->get();

if ($epp->isEmpty()) {
    echo "⚠️ No hay registros en epp_bodega_detalles\n";
} else {
    echo "✅ Encontrados {$epp->count()} registros:\n\n";
    foreach ($epp as $registro) {
        echo "ID: {$registro->id}\n";
        echo "   Talla: {$registro->talla}\n";
        echo "   Prenda: {$registro->prenda_nombre}\n";
        echo "   Cantidad: {$registro->cantidad}\n";
        echo "   Estado: {$registro->estado_bodega}\n";
        echo "   ----\n";
    }
}

echo "\n\n=== SIMULACIÓN DE BÚSQUEDA DEL CONTROLADOR ===\n";
echo "Simulando búsqueda con 4 criterios para cada prenda:\n\n";

foreach ($prendas as $prenda) {
    $prendaNombre = $prenda['nombre'] ?? 'sin-nombre';
    $cantidad = $prenda['cantidad'] ?? 0;
    
    if (isset($prenda['variantes']) && is_array($prenda['variantes'])) {
        foreach ($prenda['variantes'] as $variante) {
            $talla = $variante['talla'] ?? '';
            
            echo "Buscando: numero_pedido=1, talla={$talla}, prenda={$prendaNombre}, cantidad={$cantidad}\n";
            
            // Buscar en bodega_detalles_talla
            $bodegaDataBase = \App\Models\BodegaDetallesTalla::where('numero_pedido', '1')
                ->where('talla', $talla)
                ->where('prenda_nombre', $prendaNombre)
                ->where('cantidad', $cantidad)
                ->first();
            
            echo "   bodega_detalles_talla: ";
            if ($bodegaDataBase) {
                echo "✅ Encontrado (ID: {$bodegaDataBase->id}, estado: {$bodegaDataBase->estado_bodega})\n";
            } else {
                echo "❌ NO encontrado\n";
            }
            
            // Buscar en costura_bodega_detalles
            $costuraData = \App\Models\CosturaBodegaDetalle::where('numero_pedido', '1')
                ->where('talla', $talla)
                ->where('prenda_nombre', $prendaNombre)
                ->where('cantidad', $cantidad)
                ->first();
            
            echo "   costura_bodega_detalles: ";
            if ($costuraData) {
                echo "✅ Encontrado (ID: {$costuraData->id}, estado: {$costuraData->estado_bodega})\n";
            } else {
                echo "❌ NO encontrado\n";
            }
            
            // Buscar en epp_bodega_detalles
            $eppData = \App\Models\EppBodegaDetalle::where('numero_pedido', '1')
                ->where('talla', $talla)
                ->where('prenda_nombre', $prendaNombre)
                ->where('cantidad', $cantidad)
                ->first();
            
            echo "   epp_bodega_detalles: ";
            if ($eppData) {
                echo "✅ Encontrado (ID: {$eppData->id}, estado: {$eppData->estado_bodega})\n";
            } else {
                echo "❌ NO encontrado\n";
            }
            
            echo "\n";
        }
    }
}

echo "\n=== FIN DEL DEBUG ===\n";
