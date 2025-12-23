<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\LogoPedido;

echo "=== LIMPIEZA: Resetear secuencia a un número manejable ===\n";
// Resetear la secuencia para test
DB::table('numero_secuencias')
    ->where('tipo', 'logo_pedidos')
    ->update(['siguiente' => 10]);

echo "✅ Secuencia logo_pedidos reseteada a: 10\n\n";

// Ahora vamos a simular la creación de un logo_pedido
// usando el controlador directamente

echo "=== TEST: Crear logo_pedido SOLO ===\n";
$newLogoPedido = LogoPedido::create([
    'numero_pedido' => '#LOGO-00010', // Simulamos la secuencia
    'cliente' => 'TEST CLIENTE 01',
    'forma_de_pago' => 'CONTADO',
    'asesora' => 'Test Asesora',
    'estado' => 'PENDIENTE_SUPERVISOR',
    'area' => 'creacion_de_orden',
    'fecha_de_creacion_de_orden' => now(),
]);

echo "✅ Logo Pedido creado:\n";
echo "   ID: {$newLogoPedido->id}\n";
echo "   Número: {$newLogoPedido->numero_pedido}\n";
echo "   Cliente: {$newLogoPedido->cliente}\n";
echo "   Estado: {$newLogoPedido->estado}\n\n";

// Verificar que los últimos 5 tengan formato correcto
echo "=== ÚLTIMOS 5 LOGO PEDIDOS (REVISIÓN) ===\n";
$ultimos = LogoPedido::orderBy('id', 'desc')
    ->take(5)
    ->get(['id', 'numero_pedido', 'cliente', 'pedido_id']);

foreach ($ultimos as $lp) {
    $pedidoId = $lp->pedido_id ?? 'NULL';
    $numero = $lp->numero_pedido;
    // Verificar formato
    if (preg_match('/^#?LOGO-\d{5}$/', $numero)) {
        $status = '✅';
    } else {
        $status = '❌';
    }
    echo "$status ID: {$lp->id} | Número: $numero | Cliente: {$lp->cliente} | Pedido_ID: $pedidoId\n";
}
