<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\SupervisorAsesoresController;

echo "\n=== TEST DEL ENDPOINT /supervisor-asesores/reportes/data ===\n\n";

// Crear una request simulada
$request = new Request([
    'period' => 'month',
    'asesor_id' => ''
]);

// Crear el controlador
$controller = new SupervisorAsesoresController();

// Llamar al método
$response = $controller->reportesData($request);

// Decodificar la respuesta JSON
$data = json_decode($response->getContent(), true);

echo "Resumen:\n";
echo "  Total Cotizaciones: " . ($data['summary']['total_cotizaciones'] ?? 0) . "\n";
echo "  Total Pedidos: " . ($data['summary']['total_pedidos'] ?? 0) . "\n";
echo "  Tasa Conversión: " . ($data['summary']['conversion_rate'] ?? '0%') . "\n";
echo "  Ingresos: " . ($data['summary']['total_ingresos'] ?? 0) . "\n\n";

echo "Cotizaciones por Estado:\n";
foreach ($data['summary']['cotizaciones_por_estado'] as $item) {
    echo "  • " . $item['estado'] . ": " . $item['cantidad'] . "\n";
}

echo "\nTop Asesores:\n";
foreach ($data['top_asesores'] as $asesor) {
    echo "  • " . $asesor['name'] . ": " . $asesor['cotizaciones_count'] . " cot, " . $asesor['pedidos_count'] . " ped\n";
}

echo "\nTop Clientes:\n";
foreach ($data['top_clientes'] as $cliente) {
    echo "  • " . $cliente['nombre'] . ": " . $cliente['cotizaciones_count'] . " cot, \$" . $cliente['monto_total'] . "\n";
}

echo "\n✅ Test completado\n";
