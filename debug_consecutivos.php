<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG CONSECUTIVOS ===\n\n";

// 1. Verificar procesos para prenda 4
echo "1. Procesos para prenda 4:\n";
$procesos = DB::table('pedidos_procesos_prenda_detalles')
    ->where('prenda_pedido_id', 4)
    ->get();

echo "Total procesos: " . $procesos->count() . "\n";
foreach ($procesos as $proceso) {
    echo "  - ID: {$proceso->id}, Tipo: " . ($proceso->tipo_proceso ?? 'N/A') . ", Tipo ID: {$proceso->tipo_proceso_id}, Estado: " . ($proceso->estado ?? 'N/A') . "\n";
}

echo "\n";

// 2. Verificar consecutivos para pedido 16, prenda 4
echo "2. Consecutivos para pedido 16, prenda 4:\n";
$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->where('prenda_id', 4)
    ->where('activo', 1)
    ->get();

echo "Total consecutivos: " . $consecutivos->count() . "\n";
foreach ($consecutivos as $consecutivo) {
    echo "  - ID: {$consecutivo->id}, Tipo: {$consecutivo->tipo_recibo}, Consecutivo: {$consecutivo->consecutivo_actual}\n";
}

echo "\n";

// 3. Simular el método obtenerConsecutivosPrenda
echo "3. Simulación del método obtenerConsecutivosPrenda:\n";

// Obtener TODOS los procesos de esta prenda (sin filtro de estado)
$procesosPrenda = DB::table('pedidos_procesos_prenda_detalles')
    ->where('prenda_pedido_id', 4)
    ->pluck('tipo_proceso_id')
    ->toArray();

echo "Procesos encontrados: " . json_encode($procesosPrenda) . "\n";

// Mapear tipo_proceso_id a tipo_recibo
$tipoProcesoARecibo = [
    1 => 'REFLECTIVO',      // ID 1 = Reflectivo
    2 => 'ESTAMPADO',       // ID 2 = Estampado
    3 => 'BORDADO',         // ID 3 = Bordado
    4 => 'DTF',             // ID 4 = DTF
    5 => 'SUBLIMADO',       // ID 5 = Sublimado
    6 => 'COSTURA',         // ID 6 = Costura
];

$tiposReciboPermitidos = array_unique(array_map(function($procesoId) use ($tipoProcesoARecibo) {
    return $tipoProcesoARecibo[$procesoId] ?? null;
}, $procesosPrenda));

$tiposReciboPermitidos = array_filter($tiposReciboPermitidos);

echo "Tipos de recibo permitidos: " . json_encode($tiposReciboPermitidos) . "\n";

// Obtener consecutivos específicos para esta prenda
$consecutivosEncontrados = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->where('prenda_id', 4)
    ->where('activo', 1)
    ->whereIn('tipo_recibo', $tiposReciboPermitidos)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Consecutivos encontrados: " . $consecutivosEncontrados->count() . "\n";
foreach ($consecutivosEncontrados as $consecutivo) {
    echo "  - {$consecutivo->tipo_recibo}: {$consecutivo->consecutivo_actual}\n";
}

echo "\n=== FIN DEBUG ===\n";
