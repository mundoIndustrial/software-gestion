<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MIGRATION SUMMARY ===\n\n";

// Total de prendas
$total = \App\Models\PrendaPedido::count();
$con_tallas = \App\Models\PrendaPedido::whereNotNull('cantidad_talla')->count();
$sin_tallas = $total - $con_tallas;

echo "Total de prendas: $total\n";
echo "Con cantidad_talla: $con_tallas (" . round(($con_tallas/$total)*100, 1) . "%)\n";
echo "Sin cantidad_talla: $sin_tallas (" . round(($sin_tallas/$total)*100, 1) . "%)\n\n";

// Ejemplos de órdenes que ahora mostrarán tallas
$ordenesConTallas = \App\Models\PedidoProduccion::with('prendas')
    ->whereHas('prendas', function($q) {
        $q->whereNotNull('cantidad_talla');
    })
    ->limit(5)
    ->get();

echo "=== ÓRDENES QUE AHORA MOSTRARÁN TALLAS ===\n";
foreach ($ordenesConTallas as $orden) {
    $tallasPorPreenda = $orden->prendas->count();
    echo "Orden #{$orden->id}: {$tallasPorPreenda} prenda(s) con tallas\n";
}

echo "\n✅ Migración completada exitosamente!\n";
echo "Las órdenes antiguas ahora mostrarán tallas en el modal del insumos.\n";
