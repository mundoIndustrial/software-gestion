<?php
require 'bootstrap/app.php';
$app = make(\Illuminate\Contracts\Foundation\Application::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Verificar datos del pedido 2596
$pedido = \App\Models\PedidoProduccion::find(2596);
if ($pedido) {
    echo "Pedido 2596 encontrado\n";
    echo "Prendas en pedido: " . $pedido->prendas->count() . "\n";
    
    foreach ($pedido->prendas as $prenda) {
        echo "\n  Prenda: " . $prenda->nombre_prenda . "\n";
        echo "  - cantidad_talla: " . json_encode($prenda->cantidad_talla) . "\n";
        echo "  - fotos: " . $prenda->fotos->count() . "\n";
        echo "  - fotosTelas: " . $prenda->fotosTelas->count() . "\n";
    }
    
    echo "\n\nEPPs del pedido: " . $pedido->epps->count() . "\n";
    if ($pedido->epps->count() > 0) {
        foreach ($pedido->epps as $epp) {
            echo "  - EPP: " . ($epp->epp?->nombre ?? 'Desconocido') . ", Cantidad: " . $epp->cantidad . "\n";
        }
    }
} else {
    echo "Pedido 2596 no encontrado\n";
}
?>
