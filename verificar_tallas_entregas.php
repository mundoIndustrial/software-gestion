<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PrendaPedido;
use DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Verificar: ¿Las tallas están en entrega_prenda_pedido?\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Buscar una prenda sin cantidad_talla
$prendaSinTallas = PrendaPedido::where('cantidad_talla', null)
    ->first();

if ($prendaSinTallas) {
    echo "Prenda sin cantidad_talla encontrada: {$prendaSinTallas->id}\n";
    echo "Nombre: {$prendaSinTallas->nombre_prenda}\n\n";
    
    // Buscar tallas en entrega_prenda_pedido
    $tallas = DB::table('entrega_prenda_pedido')
        ->where('prenda_pedido_id', $prendaSinTallas->id)
        ->select('talla', 'cantidad_original')
        ->get();
    
    if ($tallas->count() > 0) {
        echo "✅ Tallas encontradas en entrega_prenda_pedido:\n";
        foreach ($tallas as $talla) {
            echo "   • {$talla->talla}: {$talla->cantidad_original}\n";
        }
        
        // Construir JSON para cantidad_talla
        $tallasArray = [];
        foreach ($tallas as $t) {
            $tallasArray[$t->talla] = (int)$t->cantidad_original;
        }
        $json = json_encode($tallasArray);
        echo "\nJSON generado: $json\n";
    } else {
        echo "❌ No hay tallas en entrega_prenda_pedido\n";
    }
} else {
    echo "No hay prendas sin cantidad_talla\n";
}
