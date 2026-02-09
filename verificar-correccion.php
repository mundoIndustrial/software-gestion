<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "===========================================\n";
echo "VERIFICACIÓN FINAL - BÚSQUEDA CORREGIDA\n";
echo "===========================================\n\n";

$pedido = \App\Models\PedidoProduccion::where('numero_pedido', '1')->first();

if (!$pedido) {
    echo "❌ No se encontró el pedido\n";
    exit;
}

echo "✅ Pedido encontrado (ID: {$pedido->id})\n\n";

$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido->id)
    ->whereNull('deleted_at')
    ->get();

echo "=== SIMULACIÓN DE BÚSQUEDA CORREGIDA ===\n";
echo "(Usando cantidad de TALLA, no cantidad total de prenda)\n\n";

foreach ($prendas as $prenda) {
    $nombrePrenda = $prenda->nombre_prenda ?? 'SIN NOMBRE';
    
    $tallas = DB::table('prenda_pedido_tallas')
        ->where('prenda_pedido_id', $prenda->id)
        ->get();
    
    if ($tallas->isEmpty()) continue;
    
    echo "\n━━━ {$nombrePrenda} ━━━\n";
    
    foreach ($tallas as $tallaRow) {
        $talla = $tallaRow->talla;
        $cantidad = $tallaRow->cantidad;  // ← CANTIDAD DE LA TALLA
        
        echo "\n  Buscando: talla={$talla}, cantidad={$cantidad}\n";
        
        // Buscar en bodega_detalles_talla
        $bodegaBase = DB::table('bodega_detalles_talla')
            ->where('numero_pedido', '1')
            ->where('talla', $talla)
            ->where('prenda_nombre', $nombrePrenda)
            ->where('cantidad', $cantidad)
            ->first();
        
        echo "    bodega_detalles_talla: ";
        if ($bodegaBase) {
            echo "✅ ENCONTRADO (ID {$bodegaBase->id}, estado: {$bodegaBase->estado_bodega})\n";
        } else {
            echo "❌ NO ENCONTRADO\n";
        }
        
        // Buscar en costura_bodega_detalles
        $costura = DB::table('costura_bodega_detalles')
            ->where('numero_pedido', '1')
            ->where('talla', $talla)
            ->where('prenda_nombre', $nombrePrenda)
            ->where('cantidad', $cantidad)
            ->first();
        
        echo "    costura_bodega_detalles: ";
        if ($costura) {
            echo "✅ ENCONTRADO (ID {$costura->id}, estado: {$costura->estado_bodega})\n";
        } else {
            echo "❌ NO ENCONTRADO\n";
        }
    }
}

echo "\n\n=== RESUMEN ===\n";
echo "✅ El código fue corregido para usar la cantidad de CADA TALLA\n";
echo "✅ Ahora las búsquedas deberían coincidir correctamente\n";
echo "\n💡 SIGUIENTE PASO:\n";
echo "   1. Recarga la página en el navegador\n";
echo "   2. Verifica que los estados aparecen correctamente\n";
echo "   3. Si aún no aparecen, revisa los logs en storage/logs/laravel.log\n";

echo "\n=== FIN ===\n";
