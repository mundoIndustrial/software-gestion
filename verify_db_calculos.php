<?php
// Script para verificar que los últimos 10 registros tienen cálculos correctos
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\RegistroPisoCorte;

echo "=== VERIFICACIÓN DE CÁLCULOS EN BASE DE DATOS ===\n\n";

$registros = RegistroPisoCorte::latest('id')->limit(10)->get();

foreach ($registros as $index => $reg) {
    echo "REGISTRO #{$reg->id}\n";
    echo "─────────────────────────────────────\n";
    echo "Fecha: {$reg->fecha}\n";
    echo "Orden: {$reg->orden_produccion}\n";
    echo "Actividad: {$reg->actividad}\n";
    echo "\nPauses:\n";
    echo "  Programada: {$reg->paradas_programadas} ({$reg->tiempo_para_programada} seg)\n";
    echo "  No programada: {$reg->paradas_no_programadas} ({$reg->tiempo_parada_no_programada} seg)\n";
    echo "\nExtendido y Trazado:\n";
    echo "  Tipo: {$reg->tipo_extendido} ({$reg->numero_capas} capas)\n";
    echo "  Tiempo extendido: {$reg->tiempo_extendido} seg\n";
    echo "  Trazado: {$reg->trazado} ({$reg->tiempo_trazado} seg)\n";
    echo "\nCálculos:\n";
    echo "  ✓ Tiempo disponible: " . number_format($reg->tiempo_disponible, 2) . " seg\n";
    echo "  ✓ Meta: " . number_format($reg->meta, 2) . "\n";
    echo "  ✓ Eficiencia: " . number_format($reg->eficiencia, 2) . "\n";
    echo "  Cantidad producida: {$reg->cantidad}\n";
    echo "  Tiempo ciclo: {$reg->tiempo_ciclo}\n";
    
    // Verificar si es 0
    if ($reg->tiempo_disponible == 0 && $reg->meta == 0 && $reg->eficiencia == 0) {
        echo "  ⚠️  ADVERTENCIA: Todos los cálculos son 0\n";
    } else if ($reg->tiempo_disponible > 0) {
        echo "  ✅ Cálculos OK\n";
    }
    
    echo "\n";
}

echo "✅ Verificación completada\n";
?>
