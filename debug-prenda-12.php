<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\LogoCotizacionTecnicaPrenda;

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Buscar técnicas de la prenda 12 en cotizaciones
$tecnicas = LogoCotizacionTecnicaPrenda::where('prenda_cot_id', 12)->get();
echo '🔍 Técnicas de prenda 12: ' . $tecnicas->count() . PHP_EOL;
foreach ($tecnicas as $t) {
    echo 'ID: ' . $t->id . ' - Cotización: ' . $t->logo_cotizacion_id . PHP_EOL;
    echo 'Fotos: ' . $t->fotos()->count() . PHP_EOL . PHP_EOL;
}
