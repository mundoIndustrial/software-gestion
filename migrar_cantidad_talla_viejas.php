<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PrendaPedido;

echo "═══════════════════════════════════════════════════════════════\n";
echo "MIGRACIÓN: Llenar cantidad_talla en órdenes viejas\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Buscar prendas sin cantidad_talla
$prendasSinTallas = PrendaPedido::whereNull('cantidad_talla')->get();

echo "Total de prendas sin cantidad_talla: " . $prendasSinTallas->count() . "\n\n";

$actualizado = 0;
$sinExtraer = 0;

foreach ($prendasSinTallas as $prenda) {
    echo "Procesando: {$prenda->nombre_prenda}";
    
    if (!$prenda->descripcion) {
        echo " ❌ Sin descripción\n";
        $sinExtraer++;
        continue;
    }
    
    // Intentar extraer tallas del formato "TALLA X = Y"
    $tallas = [];
    $lineas = explode("\n", $prenda->descripcion);
    
    foreach ($lineas as $linea) {
        // Buscar patrones como "TALLA M = 1" o "1 TALLA 10"
        if (preg_match('/TALLA\s+([A-Z0-9]+)\s*=\s*(\d+)/i', $linea, $matches)) {
            $talla = strtoupper(trim($matches[1]));
            $cantidad = (int)$matches[2];
            $tallas[$talla] = $cantidad;
        }
        // Patrón alternativo: "1 TALLA 10"
        elseif (preg_match('/(\d+)\s+TALLA\s+([A-Z0-9]+)/i', $linea, $matches)) {
            $cantidad = (int)$matches[1];
            $talla = strtoupper(trim($matches[2]));
            $tallas[$talla] = $cantidad;
        }
    }
    
    if (!empty($tallas)) {
        $json = json_encode($tallas);
        $prenda->update(['cantidad_talla' => $json]);
        echo " ✅ Actualizado: " . json_encode($tallas) . "\n";
        $actualizado++;
    } else {
        echo " ⚠️  No se pudieron extraer tallas\n";
        $sinExtraer++;
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "RESULTADO:\n";
echo "  ✅ Actualizadas: $actualizado\n";
echo "  ⚠️  Sin tallas extraídas: $sinExtraer\n";
echo "════════════════════════════════════════════════════════════════\n";
