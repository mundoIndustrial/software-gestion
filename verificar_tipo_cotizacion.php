<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Cotizacion;

// Buscar cotización 63
$cot = Cotizacion::find(63);

if ($cot) {
    echo "=== COTIZACIÓN 63 ===\n";
    echo "ID: " . $cot->id . "\n";
    echo "Tipo: " . ($cot->tipo ?? 'NULL') . "\n";
    echo "Tipo Cotización ID: " . ($cot->tipo_cotizacion_id ?? 'NULL') . "\n";
    echo "Es Borrador: " . ($cot->es_borrador ? 'true' : 'false') . "\n";
    echo "Estado: " . ($cot->estado ?? 'NULL') . "\n";
    
    // Buscar últimas 5 RF creadas
    echo "\n=== ÚLTIMAS 5 COTIZACIONES CON TIPO NO NULL ===\n";
    $cots = Cotizacion::whereNotNull('tipo')->orderBy('id', 'desc')->limit(5)->get();
    foreach ($cots as $c) {
        echo "ID: {$c->id}, Tipo: {$c->tipo}, Borrador: " . ($c->es_borrador ? 'true' : 'false') . "\n";
    }
} else {
    echo "Cotización 63 no encontrada\n";
}
?>
