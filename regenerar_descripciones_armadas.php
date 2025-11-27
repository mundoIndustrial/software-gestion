<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrendaPedido;

echo "=== REGENERAR TODAS LAS DESCRIPCIONES ARMADAS ===\n\n";

try {
    $prendas = PrendaPedido::all();
    echo "Total de prendas: " . $prendas->count() . "\n\n";

    $actualizado = 0;
    foreach ($prendas as $prenda) {
        // El boot() del modelo generará automáticamente descripcion_armada
        $prenda->save();
        $actualizado++;
        
        if ($actualizado % 100 === 0) {
            echo "  ✓ Actualizados: $actualizado\n";
        }
    }

    echo "\n✅ Totales actualizados: $actualizado\n\n";

    // Ver algunos ejemplos
    echo "=== EJEMPLOS DE DESCRIPCIONES ARMADAS ===\n\n";
    $ejemplos = PrendaPedido::whereNotNull('descripcion_armada')
        ->limit(5)
        ->get();

    foreach ($ejemplos as $ej) {
        echo "ID: {$ej->id} | Prenda: {$ej->nombre_prenda}\n";
        echo "Armada: " . substr($ej->descripcion_armada, 0, 120) . "...\n";
        echo "---\n";
    }

    echo "\n✅ ¡Listo! Ahora cada prenda nueva se armará automáticamente\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
