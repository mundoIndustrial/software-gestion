<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANTES: Eliminando cotizaciones 58 y 59 ===\n";

// Usar modelos de Eloquent que saben las tablas correctas
$prendas = \App\Models\PrendaCot::whereIn('id', [36, 37])->get();
foreach($prendas as $prenda) {
  $prenda->delete();
}

$cots = \App\Models\Cotizacion::whereIn('id', [58, 59])->get();
foreach($cots as $cot) {
  $cot->delete();
}

echo "✅ Eliminadas cotizaciones de prueba\n";
echo "\nAhora crea un nuevo borrador y envía la cotización.\n";
echo "La fallback debería copiar las fotos de tela automáticamente.\n";
