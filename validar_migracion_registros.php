<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== VALIDACIÓN PREVIA: Verificar que hay datos en registros_por_orden ===\n\n";

// 1. Contar registros en tabla_original (que tienen cotizacion_id = NULL)
$totalTablaOriginal = \DB::table('tabla_original')->count();
$pedidosSinCotizacion = \DB::table('pedidos_produccion')->whereNull('cotizacion_id')->count();

echo "Tabla Original:\n";
echo "  Total registros: $totalTablaOriginal\n";
echo "  Pedidos sin cotizacion_id en BD: $pedidosSinCotizacion\n\n";

// 2. Contar registros en registros_por_orden
$totalRegistrosPorOrden = \DB::table('registros_por_orden')->count();
$pedidosUnicos = \DB::table('registros_por_orden')->distinct()->pluck('pedido')->count();
$prendasUnicas = \DB::table('registros_por_orden')->distinct()->pluck('prenda')->count();

echo "Registros por Orden:\n";
echo "  Total registros: $totalRegistrosPorOrden\n";
echo "  Pedidos únicos: $pedidosUnicos\n";
echo "  Prendas únicas: $prendasUnicas\n\n";

// 3. Ejemplo de datos que se migrarán
$ejemplo = \DB::table('registros_por_orden')->limit(5)->get();

echo "Ejemplo de 5 registros de registros_por_orden:\n";
foreach ($ejemplo as $i => $reg) {
    echo "  " . ($i+1) . ". Pedido: {$reg->pedido}, Prenda: {$reg->prenda}, Talla: {$reg->talla}, Cantidad: {$reg->cantidad}\n";
}

// 4. Simular la consolidación para una prenda
echo "\n=== SIMULACIÓN DE CONSOLIDACIÓN DE TALLAS ===\n\n";

$prendaEjemplo = \DB::table('registros_por_orden')->first();

if ($prendaEjemplo) {
    $registrosTallas = \DB::table('registros_por_orden')
        ->where('pedido', $prendaEjemplo->pedido)
        ->where('prenda', $prendaEjemplo->prenda)
        ->select('talla', 'cantidad')
        ->get();

    $cantidadTalla = [];
    foreach ($registrosTallas as $reg) {
        $talla = strtoupper(trim($reg->talla ?? 'SIN_TALLA'));
        $cantidad = intval($reg->cantidad ?? 0);
        
        if (!isset($cantidadTalla[$talla])) {
            $cantidadTalla[$talla] = 0;
        }
        $cantidadTalla[$talla] += $cantidad;
    }

    echo "Ejemplo: Pedido " . $prendaEjemplo->pedido . " | Prenda: " . $prendaEjemplo->prenda . "\n";
    echo "  Registros encontrados: " . $registrosTallas->count() . "\n";
    echo "  JSON consolidado: " . json_encode($cantidadTalla) . "\n";
}

// 5. Verificar struktur
echo "\n=== VERIFICACIÓN DE ESTRUCTURA DE BD ===\n\n";

$columnasPrendaPedido = \DB::select("DESCRIBE prendas_pedido");
echo "Columnas en prendas_pedido:\n";
foreach ($columnasPrendaPedido as $col) {
    if (strpos($col->Field, 'talla') !== false || strpos($col->Field, 'cantidad') !== false) {
        echo "  ✓ {$col->Field} ({$col->Type})\n";
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "✅ VALIDACIÓN COMPLETADA\n";
echo "====════════════════════════════════════════════════════════════\n\n";
