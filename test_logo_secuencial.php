<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LogoPedido;
use Illuminate\Support\Facades\DB;

// Obtener número siguiente de secuencia
$siguiente = DB::table('numero_secuencias')
    ->where('tipo', 'logo_pedidos')
    ->first();

echo "=== VERIFICACIÓN ANTES DE CREAR ===\n";
echo "Siguiente número en secuencia: " . ($siguiente->siguiente ?? 'NO ENCONTRADO') . "\n\n";

// Crear un nuevo logo_pedido
$logoPedido = LogoPedido::create([
    'numero_pedido' => LogoPedido::generarNumeroPedido(),
    'cliente' => 'TEST CLIENT ' . date('His'),
    'forma_de_pago' => 'CONTADO',
    'asesora' => 'Test User',
    'estado' => 'PENDIENTE_SUPERVISOR',
    'area' => 'creacion_de_orden',
    'fecha_de_creacion_de_orden' => now(),
]);

echo "=== DESPUÉS DE CREAR ===\n";
echo "✅ Logo Pedido ID: {$logoPedido->id}\n";
echo "✅ Número Pedido: {$logoPedido->numero_pedido}\n";
echo "✅ Cliente: {$logoPedido->cliente}\n";
echo "✅ Creado: {$logoPedido->created_at}\n\n";

// Verificar secuencia actualizada
$siguienteNuevo = DB::table('numero_secuencias')
    ->where('tipo', 'logo_pedidos')
    ->first();

echo "Siguiente número en secuencia AHORA: " . ($siguienteNuevo->siguiente ?? 'NO ENCONTRADO') . "\n";

// Mostrar los últimos 3 logo_pedidos
echo "\n=== ÚLTIMOS 3 LOGO PEDIDOS EN BD ===\n";
$ultimos = LogoPedido::orderBy('id', 'desc')->take(3)->get(['id', 'numero_pedido', 'cliente']);
foreach ($ultimos as $lp) {
    echo "ID: {$lp->id} | Número: {$lp->numero_pedido} | Cliente: {$lp->cliente}\n";
}
