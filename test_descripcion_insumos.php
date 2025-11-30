<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "✅ VERIFICANDO DESCRIPCIÓN EN INSUMOS\n";
echo "════════════════════════════════════════════════════════\n\n";

// Simular lo que hace el controlador
$baseQuery = PedidoProduccion::where(function($q) {
    $q->whereIn('estado', ['No iniciado', 'En Ejecución', 'Anulada']);
})->where(function($q) {
    $q->where('area', 'LIKE', '%Corte%')
      ->orWhere('area', 'LIKE', '%Creación%orden%')
      ->orWhere('area', 'LIKE', '%Creación de orden%');
});

$ordenes = $baseQuery->with('prendas')->where('numero_pedido', 45451)->limit(10)->get();

echo "Órdenes encontradas: " . $ordenes->count() . "\n\n";

foreach ($ordenes as $orden) {
    echo "📋 Orden: " . $orden->numero_pedido . " (" . $orden->cliente . ")\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Acceder a descripcion_prendas (debería calcularse automáticamente)
    $desc = $orden->descripcion_prendas;
    echo "Longitud: " . strlen($desc) . " caracteres\n";
    echo "Primeros 100 caracteres:\n";
    echo substr($desc, 0, 100) . "...\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
}

echo "✅ TEST COMPLETADO\n";
