<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "═══════════════════════════════════════════════════════════════\n";
echo "DIAGNÓSTICO: Formato de Descripciones en Prendas\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$ordenes = PedidoProduccion::with('prendas')->limit(10)->get();

foreach ($ordenes as $orden) {
    echo "📋 Orden: {$orden->numero_pedido}\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    foreach ($orden->prendas as $index => $prenda) {
        echo "\n  Prenda " . ($index + 1) . ": {$prenda->nombre_prenda}\n";
        echo "  Descripción en DB:\n";
        
        $desc = $prenda->descripcion;
        
        if ($desc) {
            // Mostrar primeros 100 caracteres y si tiene saltos
            $preview = substr($desc, 0, 100);
            $hasNewlines = strpos($desc, "\n") !== false;
            
            echo "    • Contiene saltos de línea: " . ($hasNewlines ? "SÍ" : "NO") . "\n";
            echo "    • Primeros 100 caracteres: " . str_replace("\n", "\\n", $preview) . "\n";
            echo "    • Longitud total: " . strlen($desc) . " caracteres\n";
            
            // Mostrar estructura
            if ($hasNewlines) {
                echo "    • Estructura:\n";
                $lineas = explode("\n", $desc);
                foreach ($lineas as $linea) {
                    echo "      - " . substr($linea, 0, 60) . "\n";
                }
            }
        } else {
            echo "    • (vacía)\n";
        }
    }
    
    echo "\n  descripcion_prendas (attribute):\n";
    echo "  " . str_replace("\n", "\n  ", $orden->descripcion_prendas) . "\n";
    echo "════════════════════════════════════════════════════════════════\n\n";
}
