<?php
// Cargar Composer autoload
require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel app
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

// Buscar un pedido con novedades de EPP
echo "=== BUSCANDO PEDIDO CON CAMBIOS DE EPP ===\n";

$pedido = PedidoProduccion::whereNotNull('novedades')
    ->where('novedades', 'like', '%HOMOLOGADO EPP%')
    ->first();

if ($pedido) {
    echo "Pedido encontrado: #" . $pedido->id . "\n";
    echo "Novedades: " . substr($pedido->novedades, 0, 300) . "\n\n";
    
    // Buscar en auditoría
    echo "=== AUDITORÍA ASOCIADA AL PEDIDO ===\n";
    $cambios = DB::table('pedidos_auditoria')
        ->where('pedidos_produccion_id', $pedido->id)
        ->get();
    
    echo "Total de cambios en auditoría: " . $cambios->count() . "\n";
    foreach ($cambios as $cambio) {
        echo "- Tipo: " . $cambio->tipo_cambio . "\n";
        echo "  Usuario ID: " . $cambio->usuario_id . "\n";
        echo "  Fecha: " . $cambio->created_at . "\n";
    }
    
    // Buscar info del usuario si hay
    if ($cambios->count() > 0 && $cambios->first()->usuario_id) {
        echo "\n=== INFO DEL USUARIO QUE HIZO CAMBIOS ===\n";
        $usuario = DB::table('users')->where('id', $cambios->first()->usuario_id)->first();
        if ($usuario) {
            echo "Nombre: " . $usuario->name . "\n";
            echo "Email: " . $usuario->email . "\n";
        }
    }
} else {
    echo "No se encontró pedido con cambios de EPP\n";
}

echo "\n=== FIN ===\n";
