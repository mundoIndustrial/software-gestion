<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CosturaBodegaDetalle;
use App\Models\EppBodegaDetalle;

echo "\n============================================\n";
echo "LIMPIEZA Y ACTUALIZACIÓN DE RECORDATORIOS\n";
echo "============================================\n\n";

// 1. Limpiar costura_bodega_detalles
echo "1. ACTUALIZANDO costura_bodega_detalles (estados vacíos → 'Pendiente'):\n";
echo str_repeat("-", 80) . "\n";

$costuraActualizados = CosturaBodegaDetalle::where('numero_pedido', '1')
    ->where(function($query) {
        $query->whereNull('estado_bodega')
            ->orWhere('estado_bodega', '')
            ->orWhere('estado_bodega', 'NULL');
    })
    ->update(['estado_bodega' => 'Pendiente']);

echo "✅ Se actualizaron $costuraActualizados registros en costura_bodega_detalles\n\n";

// 2. Limpiar epp_bodega_detalles
echo "2. ACTUALIZANDO epp_bodega_detalles (estados vacíos → 'Pendiente'):\n";
echo str_repeat("-", 80) . "\n";

$eppActualizados = EppBodegaDetalle::where('numero_pedido', '1')
    ->where(function($query) {
        $query->whereNull('estado_bodega')
            ->orWhere('estado_bodega', '')
            ->orWhere('estado_bodega', 'NULL');
    })
    ->update(['estado_bodega' => 'Pendiente']);

echo "✅ Se actualizaron $eppActualizados registros en epp_bodega_detalles\n\n";

// 3. Verificar
echo "3. VERIFICANDO DATOS ACTUALIZADOS:\n";
echo str_repeat("-", 80) . "\n";

$costuraBodegas = CosturaBodegaDetalle::where('numero_pedido', '1')->get();

foreach ($costuraBodegas as $registro) {
    echo sprintf(
        "costura_bodega_detalles ID: %d | Talla: %s | Prenda: %s | Cantidad: %d | Estado: %s\n",
        $registro->id,
        $registro->talla,
        $registro->prenda_nombre,
        $registro->cantidad,
        $registro->estado_bodega
    );
}

echo "\n============================================\n";
echo "✅ LIMPIEZA COMPLETADA\n";
echo "============================================\n\n";
