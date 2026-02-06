<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;

echo "\n========================================\n";
echo "TEST: GUARDAR ESTADO EN BODEGA_DETALLES_TALLA\n";
echo "========================================\n\n";

// Limpiar registros anteriores para pedido 8
echo "🗑️  Limpiando registros anteriores para pedido 8...\n";
BodegaDetallesTalla::where('numero_pedido', '8')->delete();
EppBodegaDetalle::where('numero_pedido', '8')->delete();

echo "✓ Registros eliminados\n\n";

// Simular guardado de primer prenda (CAMIS DRILL L:3)
echo "📝 Guardando CAMIS DRILL | L | 3 | Entregado...\n";

$bodegaDetalle1 = BodegaDetallesTalla::updateOrCreate(
    [
        'pedido_produccion_id' => 1,
        'numero_pedido' => '8',
        'talla' => 'L',
        'prenda_nombre' => 'CAMIS DRILL',
        'cantidad' => 3,
    ],
    [
        'asesor' => 'Juan',
        'empresa' => 'TestCo',
        'pendientes' => 0,
        'observaciones_bodega' => 'Test 1',
        'estado_bodega' => 'Entregado',
        'usuario_bodega_id' => 1,
        'usuario_bodega_nombre' => 'Admin',
    ]
);

echo "✓ Guardado en bodega_detalles_talla con ID: " . $bodegaDetalle1->id . "\n";

// Simular guardado en epp_bodega_detalles
$eppDetalle1 = EppBodegaDetalle::updateOrCreate(
    [
        'pedido_produccion_id' => 1,
        'numero_pedido' => '8',
        'talla' => 'L',
        'prenda_nombre' => 'CAMIS DRILL',
        'cantidad' => 3,
    ],
    [
        'asesor' => 'Juan',
        'empresa' => 'TestCo',
        'pendientes' => 0,
        'observaciones_bodega' => 'Test 1',
        'estado_bodega' => 'Entregado',
        'usuario_bodega_id' => 1,
        'usuario_bodega_nombre' => 'Admin',
    ]
);

echo "✓ Guardado en epp_bodega_detalles con ID: " . $eppDetalle1->id . "\n\n";

// Simular guardado de segunda prenda (CAMISAW L:20)
echo "📝 Guardando CAMISAW | L | 20 | Pendiente...\n";

$bodegaDetalle2 = BodegaDetallesTalla::updateOrCreate(
    [
        'pedido_produccion_id' => 1,
        'numero_pedido' => '8',
        'talla' => 'L',
        'prenda_nombre' => 'CAMISAW',
        'cantidad' => 20,
    ],
    [
        'asesor' => 'Juan',
        'empresa' => 'TestCo',
        'pendientes' => 15,
        'observaciones_bodega' => 'Test 2',
        'estado_bodega' => 'Pendiente',
        'usuario_bodega_id' => 1,
        'usuario_bodega_nombre' => 'Admin',
    ]
);

echo "✓ Guardado en bodega_detalles_talla con ID: " . $bodegaDetalle2->id . "\n";

// Simular guardado en epp_bodega_detalles
$eppDetalle2 = EppBodegaDetalle::updateOrCreate(
    [
        'pedido_produccion_id' => 1,
        'numero_pedido' => '8',
        'talla' => 'L',
        'prenda_nombre' => 'CAMISAW',
        'cantidad' => 20,
    ],
    [
        'asesor' => 'Juan',
        'empresa' => 'TestCo',
        'pendientes' => 15,
        'observaciones_bodega' => 'Test 2',
        'estado_bodega' => 'Pendiente',
        'usuario_bodega_id' => 1,
        'usuario_bodega_nombre' => 'Admin',
    ]
);

echo "✓ Guardado en epp_bodega_detalles con ID: " . $eppDetalle2->id . "\n\n";

// Verificar datos guardados en bodega_detalles_talla
echo "✅ VERIFICACIÓN - bodega_detalles_talla para pedido 8:\n";
echo str_repeat("-", 100) . "\n";

$registrosBodega = BodegaDetallesTalla::where('numero_pedido', '8')->get();
foreach ($registrosBodega as $reg) {
    echo sprintf(
        "%-15s | %-12s | %-20s | %-8s | %-12s\n",
        $reg->numero_pedido,
        $reg->talla,
        substr($reg->prenda_nombre ?? 'sin-nombre', 0, 20),
        $reg->cantidad,
        $reg->estado_bodega
    );
}

echo str_repeat("-", 100) . "\n\n";

// Verificar datos guardados en epp_bodega_detalles
echo "✅ VERIFICACIÓN - epp_bodega_detalles para pedido 8:\n";
echo str_repeat("-", 100) . "\n";

$registrosEpp = EppBodegaDetalle::where('numero_pedido', '8')->get();
foreach ($registrosEpp as $reg) {
    echo sprintf(
        "%-15s | %-12s | %-20s | %-8s | %-12s\n",
        $reg->numero_pedido,
        $reg->talla,
        substr($reg->prenda_nombre ?? 'sin-nombre', 0, 20),
        $reg->cantidad,
        $reg->estado_bodega
    );
}

echo str_repeat("-", 100) . "\n\n";

// Simular la fusión que hace el controlador
echo "🔄 SIMULANDO FUSIÓN DE DATOS (como lo hace el controlador):\n";
echo str_repeat("-", 100) . "\n";

// Cargar datos básicos
$datosBodegaBasicos = BodegaDetallesTalla::where('numero_pedido', '8')->get()
    ->map(function ($item) {
        return $item->toArray();
    })
    ->keyBy(function ($item) {
        return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
    });

echo "Registros básicos cargados: " . $datosBodegaBasicos->count() . "\n";

// Cargar datos de EPP
$datosEstadoRol = EppBodegaDetalle::where('numero_pedido', '8')->get()
    ->map(function ($item) {
        return $item->toArray();
    })
    ->keyBy(function ($item) {
        return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
    });

echo "Registros de estado EPP cargados: " . $datosEstadoRol->count() . "\n\n";

// Hacer la fusión
$datosBodega = $datosBodegaBasicos->map(function($item) use ($datosEstadoRol) {
    $clave = $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
    
    echo "  Procesando clave: $clave\n";
    
    // Si existe estado en la tabla del rol, usar ese estado
    if ($datosEstadoRol->has($clave)) {
        $estadoRol = $datosEstadoRol[$clave];
        echo "    ✓ Encontrado en EPP! Estado anterior: " . ($item['estado_bodega'] ?? 'null') . " → Estado nuevo: " . ($estadoRol['estado_bodega'] ?? 'null') . "\n";
        $item['estado_bodega'] = $estadoRol['estado_bodega'] ?? $item['estado_bodega'] ?? null;
    } else {
        echo "    - No encontrado en EPP, mantened estado base: " . ($item['estado_bodega'] ?? 'null') . "\n";
    }
    
    return $item;
});

echo "\n✅ DATOS FUSIONADOS (lo que verá el template blade):\n";
echo str_repeat("-", 100) . "\n";

foreach ($datosBodega as $clave => $item) {
    echo sprintf(
        "%-15s | %-12s | %-20s | %-8s | Estado: %-12s\n",
        $item['numero_pedido'],
        $item['talla'],
        substr($item['prenda_nombre'] ?? 'sin-nombre', 0, 20),
        $item['cantidad'],
        $item['estado_bodega'] ?? 'null'
    );
}

echo str_repeat("-", 100) . "\n\n";

echo "✅ TEST COMPLETADO\n";
echo "\n";
