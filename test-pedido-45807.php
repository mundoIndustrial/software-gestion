<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Insumos\ProcesoAutomaticoService;

echo "=== PRUEBA CON PEDIDO 45807 ===\n";

$service = new ProcesoAutomaticoService();
$result = $service->crearProcesosParaPedido(45807);

echo "Resultado: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Procesos creados: " . $result['procesos_creados'] . "\n";

if (!empty($result['detalles'])) {
    echo "Detalles:\n";
    foreach ($result['detalles'] as $detalle) {
        echo "  - " . $detalle . "\n";
    }
}

if (!$result['success']) {
    echo "Error: " . $result['message'] . "\n";
}

echo "\n=== VERIFICACIÓN EN BD ===\n";
$procesos = \App\Models\ProcesosPrenda::where('numero_pedido', 45807)->get();
echo "Total procesos en BD: " . $procesos->count() . "\n";

foreach ($procesos as $proceso) {
    echo "  - ID: {$proceso->id}, Proceso: {$proceso->proceso}, Estado: {$proceso->estado_proceso}\n";
}
