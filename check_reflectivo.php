<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Iniciar Laravel
$app->make('config');

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;

echo "📊 ÚLTIMAS 5 COTIZACIONES:\n";
echo "==========================\n\n";

$cotizaciones = Cotizacion::latest()->take(5)->get();

foreach ($cotizaciones as $cot) {
    $tipo = $cot->tipoCotizacion ? $cot->tipoCotizacion->codigo : 'N/A';
    echo "ID: {$cot->id}\n";
    echo "  Número: {$cot->numero_cotizacion}\n";
    echo "  Tipo: $tipo\n";
    echo "  tipo_cotizacion_id: {$cot->tipo_cotizacion_id}\n";
    echo "  Estado: {$cot->estado}\n";
    echo "  Creada: {$cot->created_at}\n";
    echo "\n";
}

echo "\n\n📋 TIPOS DE COTIZACIÓN DISPONIBLES:\n";
echo "====================================\n\n";

$tipos = TipoCotizacion::all();
foreach ($tipos as $tipo) {
    echo "ID: {$tipo->id}, Código: {$tipo->codigo}, Nombre: {$tipo->nombre}\n";
}
?>
