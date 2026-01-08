<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Auth;

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Obtener todas las cotizaciones
echo "=== REVISANDO COTIZACIONES EN BD ===\n\n";

$todas = Cotizacion::all();
echo "Total de cotizaciones: " . $todas->count() . "\n\n";

// Agrupar por estado
$porEstado = $todas->groupBy('estado');
echo "RESUMEN POR ESTADO:\n";
foreach ($porEstado as $estado => $cotizaciones) {
    echo "  - $estado: " . count($cotizaciones) . " cotizaciones\n";
}

echo "\n=== DETALLES DE COTIZACIONES ===\n";
foreach ($todas as $cot) {
    $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
    echo "ID: {$cot->id} | NÚMERO: {$cot->numero_cotizacion} | ESTADO: {$cot->estado} | CLIENTE: $cliente\n";
}

echo "\n=== COTIZACIONES QUE SE MOSTRARÍAN (APROBADAS) ===\n";
$aprobadas = Cotizacion::whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])->get();
echo "Total que se mostrarían: " . $aprobadas->count() . "\n";
foreach ($aprobadas as $cot) {
    $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
    echo "ID: {$cot->id} | NÚMERO: {$cot->numero_cotizacion} | ESTADO: {$cot->estado} | CLIENTE: $cliente\n";
}

if ($aprobadas->count() === 0) {
    echo "\n⚠️ NO HAY COTIZACIONES APROBADAS\n";
    echo "\nPARA CREAR COTIZACIONES DE PRUEBA, EJECUTA:\n";
    echo "php artisan db:seed --class=CotizacionSeeder\n";
    echo "\nO ACTUALIZA MANUALMENTE:\n";
    echo "UPDATE cotizaciones SET estado = 'APROBADA_COTIZACIONES' LIMIT 5;\n";
}
?>
