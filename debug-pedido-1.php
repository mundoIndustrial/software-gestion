<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BodegaDetallesTalla;
use App\Models\CosturaBodegaDetalle;

echo "\n============================================\n";
echo "DEBUGUEO PEDIDO 1\n";
echo "============================================\n\n";

// 1. Datos en bodega_detalles_talla
echo "1. DATOS EN bodega_detalles_talla (numero_pedido = '1'):\n";
echo str_repeat("-", 80) . "\n";

$bodegaBasicos = BodegaDetallesTalla::where('numero_pedido', '1')->get();

if ($bodegaBasicos->count() === 0) {
    echo "❌ NO HAY DATOS EN bodega_detalles_talla\n";
} else {
    foreach ($bodegaBasicos as $registro) {
        echo sprintf(
            "ID: %d | Talla: %s | Prenda: %s | Cantidad: %d | Estado: %s | Área: %s\n",
            $registro->id,
            $registro->talla,
            $registro->prenda_nombre,
            $registro->cantidad,
            $registro->estado_bodega,
            $registro->area
        );
    }
}

echo "\n";

// 2. Datos en costura_bodega_detalles
echo "2. DATOS EN costura_bodega_detalles (numero_pedido = '1'):\n";
echo str_repeat("-", 80) . "\n";

$costuraBodegas = CosturaBodegaDetalle::where('numero_pedido', '1')->get();

if ($costuraBodegas->count() === 0) {
    echo "❌ NO HAY DATOS EN costura_bodega_detalles\n";
} else {
    foreach ($costuraBodegas as $registro) {
        echo sprintf(
            "ID: %d | Talla: %s | Prenda: %s | Cantidad: %d | Estado: %s\n",
            $registro->id,
            $registro->talla,
            $registro->prenda_nombre,
            $registro->cantidad,
            $registro->estado_bodega
        );
    }
}

echo "\n";

// 3. Comparación
echo "3. COMPARACIÓN Y ANÁLISIS:\n";
echo str_repeat("-", 80) . "\n";

if ($bodegaBasicos->count() > 0) {
    foreach ($bodegaBasicos as $basico) {
        $clave = $basico->numero_pedido . '|' . $basico->talla . '|' . $basico->prenda_nombre . '|' . $basico->cantidad;
        
        $costura = CosturaBodegaDetalle::where('numero_pedido', $basico->numero_pedido)
            ->where('talla', $basico->talla)
            ->where('prenda_nombre', $basico->prenda_nombre)
            ->where('cantidad', $basico->cantidad)
            ->first();
        
        echo "\n🔍 Clave: $clave\n";
        
        if ($costura) {
            echo "✅ ENCONTRADO EN costura_bodega_detalles\n";
            echo "   Estado en bodega_detalles_talla: {$basico->estado_bodega}\n";
            echo "   Estado en costura_bodega_detalles: {$costura->estado_bodega}\n";
            
            if ($basico->estado_bodega !== $costura->estado_bodega) {
                echo "   ⚠️  ESTADOS DIFERENTES!\n";
            }
        } else {
            echo "❌ NO ENCONTRADO EN costura_bodega_detalles\n";
            
            // Buscar qué registros SÍ existen para este número de pedido + talla
            $existentes = CosturaBodegaDetalle::where('numero_pedido', $basico->numero_pedido)
                ->where('talla', $basico->talla)
                ->get();
            
            if ($existentes->count() > 0) {
                echo "   Registros existentes para este pedido + talla:\n";
                foreach ($existentes as $existe) {
                    echo sprintf(
                        "   - Prenda: '%s' | Cantidad: %d | Estado: %s\n",
                        $existe->prenda_nombre,
                        $existe->cantidad,
                        $existe->estado_bodega
                    );
                }
            }
        }
    }
}

echo "\n============================================\n";
echo "FIN DEL DEBUG\n";
echo "============================================\n\n";
