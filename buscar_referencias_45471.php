<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrendaPedido;

$prendas = PrendaPedido::where('numero_pedido', 45471)->get();

foreach ($prendas as $prenda) {
    echo "\n========== {$prenda->nombre_prenda} ==========\n";
    echo "DESCRIPCION COMPLETA:\n";
    echo $prenda->descripcion;
    echo "\n\n";
    
    // Buscar patrones de referencia
    echo "BÚSQUEDA DE REFERENCIA:\n";
    if (preg_match('/REF[:\s]*([A-Z0-9\-]+)/i', $prenda->descripcion, $matches)) {
        echo "✅ Referencia encontrada: " . $matches[1] . "\n";
    } else {
        echo "❌ No hay referencia encontrada\n";
    }
    
    // Buscar patrones de tela específicos
    echo "\nBÚSQUEDA DE TELA:\n";
    if (preg_match('/\b(DRILL|POLIESTER|ALGODÓN|OXFORD|LINO|SARGA|BORNEO|GRIS|NARANJA)\b/i', $prenda->descripcion, $matches)) {
        echo "Tela encontrada: " . $matches[0] . "\n";
    }
}
