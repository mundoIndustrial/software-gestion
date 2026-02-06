<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;

echo "\n========================================\n";
echo "TEST: ESTADO SEPARADO POR ROL\n";
echo "========================================\n\n";

// Limpiar registros anteriores para pedido 8
echo "🗑️  Limpiando registros anteriores para pedido 8...\n";
BodegaDetallesTalla::where('numero_pedido', '8')->delete();
EppBodegaDetalle::where('numero_pedido', '8')->delete();

echo "✓ Registros eliminados\n\n";

// ESCENARIO: Bodeguero guarda primero
echo "📝 BODEGUERO guarda CAMIS DRILL | L | 3 | Entregado...\n";

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
        'observaciones_bodega' => 'Bodeguero nota',
        'estado_bodega' => 'Entregado',  // ← BODEGUERO GUARDA ESTADO
        'usuario_bodega_id' => 1,
        'usuario_bodega_nombre' => 'Admin',
    ]
);

echo "✓ Guardado en bodega_detalles_talla con estado: Entregado\n\n";

// ESCENARIO: EPP-Bodega guarda el mismo item con estado Pendiente
echo "📝 EPP-BODEGA guarda CAMIS DRILL | L | 3 | Pendiente...\n";
echo "   (NO debe guardar estado en bodega_detalles_talla, solo en epp_bodega_detalles)\n\n";

// Simular lo que hace el controlador para EPP-Bodega
$datosBasicos = [
    'prenda_nombre' => 'CAMIS DRILL',
    'asesor' => 'Juan',
    'empresa' => 'TestCo',
    'cantidad' => 3,
    'pendientes' => 2,
    'observaciones_bodega' => 'EPP nota',
    'fecha_entrega' => null,
    'fecha_pedido' => null,
    'usuario_bodega_id' => 2,
    'usuario_bodega_nombre' => 'EPPUser',
    // NO incluir estado_bodega para EPP-Bodega
];

$eppBodegaDetalle1 = BodegaDetallesTalla::updateOrCreate(
    [
        'pedido_produccion_id' => 1,
        'numero_pedido' => '8',
        'talla' => 'L',
        'prenda_nombre' => 'CAMIS DRILL',
        'cantidad' => 3,
    ],
    $datosBasicos
);

echo "✓ Actualizado bodega_detalles_talla (SIN estado)\n";

// Guardar en epp_bodega_detalles
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
        'pendientes' => 2,
        'observaciones_bodega' => 'EPP nota',
        'estado_bodega' => 'Pendiente',  // ← EPP-BODEGA GUARDA ESTADO AQUÍ
        'usuario_bodega_id' => 2,
        'usuario_bodega_nombre' => 'EPPUser',
    ]
);

echo "✓ Guardado en epp_bodega_detalles con estado: Pendiente\n\n";

// ESCENARIO: CAMISAW L:20
echo "📝 BODEGUERO guarda CAMISAW | L | 20 | Pendiente...\n";

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
        'observaciones_bodega' => 'Bodeguero nota 2',
        'estado_bodega' => 'Pendiente',  // ← BODEGUERO GUARDA ESTADO
        'usuario_bodega_id' => 1,
        'usuario_bodega_nombre' => 'Admin',
    ]
);

echo "✓ Guardado en bodega_detalles_talla con estado: Pendiente\n\n";

// Verificar datos en bodega_detalles_talla
echo "✅ VERIFICACIÓN - bodega_detalles_talla para pedido 8:\n";
echo str_repeat("-", 100) . "\n";

$registrosBodega = BodegaDetallesTalla::where('numero_pedido', '8')->orderBy('prenda_nombre')->get();
foreach ($registrosBodega as $reg) {
    echo sprintf(
        "%-15s | %-12s | %-20s | %-8s | Estado: %-12s (usuario: %s)\n",
        $reg->numero_pedido,
        $reg->talla,
        substr($reg->prenda_nombre ?? 'sin-nombre', 0, 20),
        $reg->cantidad,
        $reg->estado_bodega ?? 'NULL',
        $reg->usuario_bodega_nombre
    );
}

echo str_repeat("-", 100) . "\n\n";

// Verificar datos en epp_bodega_detalles
echo "✅ VERIFICACIÓN - epp_bodega_detalles para pedido 8:\n";
echo str_repeat("-", 100) . "\n";

$registrosEpp = EppBodegaDetalle::where('numero_pedido', '8')->orderBy('prenda_nombre')->get();
if ($registrosEpp->count() > 0) {
    foreach ($registrosEpp as $reg) {
        echo sprintf(
            "%-15s | %-12s | %-20s | %-8s | Estado: %-12s (usuario: %s)\n",
            $reg->numero_pedido,
            $reg->talla,
            substr($reg->prenda_nombre ?? 'sin-nombre', 0, 20),
            $reg->cantidad,
            $reg->estado_bodega ?? 'NULL',
            $reg->usuario_bodega_nombre
        );
    }
} else {
    echo "No hay registros\n";
}

echo str_repeat("-", 100) . "\n\n";

// Simular la carga del controlador para EPP-Bodega
echo "🔄 SIMULANDO CARGA DEL CONTROLADOR (EPP-Bodega):\n";
echo str_repeat("-", 100) . "\n";

$rolesDelUsuario = ['EPP-Bodega'];

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

// Aplicar lógica de fusión del controlador
$datosBodega = $datosBodegaBasicos->map(function($item) use ($datosEstadoRol, $rolesDelUsuario) {
    $clave = $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
    
    // Para EPP-Bodega y Costura-Bodega: el estado SOLO viene de sus tablas, no de bodega_detalles_talla
    if (in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)) {
        // El estado viene SOLO de la tabla del rol
        if ($datosEstadoRol->has($clave)) {
            $estadoRol = $datosEstadoRol[$clave];
            $item['estado_bodega'] = $estadoRol['estado_bodega'] ?? null;
            echo "  ✓ $clave → Estado desde EPP: " . ($item['estado_bodega'] ?? 'null') . "\n";
        } else {
            // Si el item no existe en la tabla del rol, no tiene estado
            $item['estado_bodega'] = null;
            echo "  - $clave → No existe en EPP, estado = null\n";
        }
    }
    
    return $item;
});

echo "\n✅ DATOS FUSIONADOS (EPP-Bodega ve esto):\n";
echo str_repeat("-", 100) . "\n";

foreach ($datosBodega as $clave => $item) {
    echo sprintf(
        "%-15s | %-12s | %-20s | %-8s | Estado: %-12s\n",
        $item['numero_pedido'],
        $item['talla'],
        substr($item['prenda_nombre'] ?? 'sin-nombre', 0, 20),
        $item['cantidad'],
        $item['estado_bodega'] ?? 'NULL'
    );
    echo "  └─ data-original-estado será: \"" . ($item['estado_bodega'] ?? '') . "\"\n";
}

echo str_repeat("-", 100) . "\n\n";

echo "✅ TEST COMPLETADO\n";
echo "\n";
