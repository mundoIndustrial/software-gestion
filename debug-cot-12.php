<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Cotizacion;

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Buscar cotización 12
$cot = Cotizacion::find(12);
echo '🔍 Cotización 12: ' . ($cot ? $cot->numero_cotizacion : 'NO EXISTE') . PHP_EOL;

if ($cot) {
    echo 'Tipo: ' . ($cot->tipoCotizacion ? $cot->tipoCotizacion->nombre : 'N/A') . PHP_EOL;
    echo 'Prendas: ' . $cot->prendas->count() . PHP_EOL;
    
    foreach ($cot->prendas as $p) {
        echo "  - [ID {$p->id}] {$p->nombre_producto} | Técnicas Logo: " . $p->logoCotizacionesTecnicas->count() . PHP_EOL;
    }
}
