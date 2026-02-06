<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "VERIFICACIÓN DE DATOS EN BODEGA_DETALLES_TALLA\n";
echo "========================================\n\n";

// Total de registros
$total = DB::table('bodega_detalles_talla')->count();
echo "📊 Total de registros: " . $total . "\n\n";

// Primeros 10 registros
echo "📋 Primeros 10 registros:\n";
echo str_repeat("-", 120) . "\n";

$registros = DB::table('bodega_detalles_talla')
    ->select('numero_pedido', 'talla', 'prenda_nombre', 'cantidad', 'estado_bodega', 'area', 'created_at')
    ->limit(10)
    ->get();

foreach ($registros as $reg) {
    printf(
        "%-12s | %-6s | %-20s | %-8s | %-12s | %-12s | %s\n",
        $reg->numero_pedido,
        $reg->talla,
        substr($reg->prenda_nombre ?? 'null', 0, 20),
        $reg->cantidad,
        $reg->estado_bodega,
        $reg->area ?? 'null',
        $reg->created_at
    );
}
echo str_repeat("-", 120) . "\n\n";

// Datos específicos para pedido 8 (el que hemos estado probando)
echo "🔍 Registros para pedido #8:\n";
echo str_repeat("-", 120) . "\n";

$pedido8 = DB::table('bodega_detalles_talla')
    ->where('numero_pedido', '8')
    ->select('numero_pedido', 'talla', 'prenda_nombre', 'cantidad', 'estado_bodega', 'area', 'observaciones_bodega', 'updated_at')
    ->get();

if ($pedido8->count() > 0) {
    foreach ($pedido8 as $reg) {
        printf(
            "%-12s | %-6s | %-20s | %-8s | %-12s | %-12s\n",
            $reg->numero_pedido,
            $reg->talla,
            substr($reg->prenda_nombre ?? 'null', 0, 20),
            $reg->cantidad,
            $reg->estado_bodega,
            $reg->area ?? 'null'
        );
        echo "  └─ Observaciones: " . ($reg->observaciones_bodega ?? 'sin notas') . "\n";
        echo "  └─ Actualizado: " . $reg->updated_at . "\n";
    }
} else {
    echo "❌ No hay registros para el pedido #8\n";
}
echo str_repeat("-", 120) . "\n\n";

// Verificar EPP-Bodega para pedido #8
echo "🔍 Registros en epp_bodega_detalles para pedido #8:\n";
echo str_repeat("-", 120) . "\n";

$eppBodega = DB::table('epp_bodega_detalles')
    ->where('numero_pedido', '8')
    ->select('numero_pedido', 'talla', 'prenda_nombre', 'cantidad', 'estado_bodega', 'updated_at')
    ->get();

if ($eppBodega->count() > 0) {
    foreach ($eppBodega as $reg) {
        printf(
            "%-12s | %-6s | %-20s | %-8s | %-12s\n",
            $reg->numero_pedido,
            $reg->talla,
            substr($reg->prenda_nombre ?? 'null', 0, 20),
            $reg->cantidad,
            $reg->estado_bodega
        );
        echo "  └─ Actualizado: " . $reg->updated_at . "\n";
    }
} else {
    echo "❌ No hay registros para el pedido #8 en epp_bodega_detalles\n";
}
echo str_repeat("-", 120) . "\n\n";

// Resumen por área
echo "📊 Resumen por Área (bodega_detalles_talla):\n";
echo str_repeat("-", 60) . "\n";

$porArea = DB::table('bodega_detalles_talla')
    ->groupBy('area')
    ->selectRaw('area, COUNT(*) as total, estado_bodega')
    ->orderBy('area')
    ->get();

foreach ($porArea as $area) {
    echo "Área: " . ($area->area ?? 'NULL') . " | Total: " . $area->total . " | Estado: " . $area->estado_bodega . "\n";
}

echo "\n========================================\n\n";
