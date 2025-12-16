<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cotizacion;

$cot = Cotizacion::find(59);
if (!$cot) {
    echo "Cotización 59 no encontrada\n";
    exit;
}

echo "=== COTIZACIÓN 59 ===\n";
echo "Prendas totales: " . $cot->prendas->count() . "\n";

foreach ($cot->prendas as $prenda) {
    echo "\n- Prenda ID {$prenda->id}: {$prenda->nombre_producto}\n";
    echo "  Fotos prenda: " . $prenda->fotos->count() . "\n";
    echo "  Fotos tela: " . $prenda->telaFotos->count() . "\n";
    
    if ($prenda->variantes->count() > 0) {
        foreach ($prenda->variantes as $variante) {
            echo "  Variante: Color={$variante->color}, Tela={$variante->prenda_tela_id}\n";
        }
    } else {
        echo "  (Sin variantes)\n";
    }
}

echo "\n";
?>
