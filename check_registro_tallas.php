<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

$prenda = PrendaPedido::first();
if ($prenda) {
    echo "=== PRENDA PEDIDO ===\n";
    echo "ID: " . $prenda->id . "\n";
    echo "nombre_prenda: " . $prenda->nombre_prenda . "\n";
    echo "cantidad: " . $prenda->cantidad . "\n";
    echo "cantidad_talla raw: " . $prenda->cantidad_talla . "\n";
    echo "descripcion: \n" . $prenda->descripcion . "\n\n";
    
    // Buscar en registros_por_orden
    $registros = DB::table('registros_por_orden')
        ->where('id_prenda', $prenda->nombre_prenda)
        ->orWhere('descripcion', 'LIKE', '%' . $prenda->nombre_prenda . '%')
        ->limit(5)
        ->get();
    
    echo "=== REGISTROS POR ORDEN (relacionados) ===\n";
    echo "Encontrados: " . count($registros) . "\n";
    if ($registros) {
        foreach ($registros as $r) {
            echo "- Talla: {$r->talla}, Cantidad: {$r->cantidad}, Prenda: {$r->id_prenda}\n";
        }
    }
} else {
    echo "No prendas found\n";
}
