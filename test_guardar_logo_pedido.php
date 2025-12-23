<?php
// Script de prueba que simula un request API a guardarLogoPedido

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;

// Obtener un usuario (asesora)
$usuario = User::where('email', 'like', '%@%')->first();
if (!$usuario) {
    echo "❌ No se encontró usuario\n";
    exit(1);
}

echo "=== PRUEBA DE FLUJO: crearLogoPedidoDesdeCotzacion + guardarLogoPedido ===\n";
echo "Usuario: {$usuario->name} ({$usuario->email})\n\n";

// Resetear la secuencia para testing
DB::table('numero_secuencias')
    ->where('tipo', 'logo_pedidos')
    ->update(['siguiente' => 100]);

echo "✅ Secuencia logo_pedidos reseteada a: 100\n\n";

// Crear una request "fake" para guardarLogoPedido
$requestData = [
    'logo_cotizacion_id' => null,
    'pedido_id' => null,
    'cotizacion_id' => null,
    'numero_cotizacion' => 'TEST-COT-001',
    'cliente' => 'TEST CLIENTE FINAL',
    'forma_de_pago' => 'CONTADO',
    'descripcion' => 'Prueba de número secuencial',
    'tecnicas' => ['bordado'],
    'observaciones' => 'Test',
];

echo "=== DATOS DE REQUEST ===\n";
foreach ($requestData as $key => $val) {
    $display = is_array($val) ? implode(',', $val) : $val;
    echo "$key: $display\n";
}
echo "\n";

// Simular validación
$validated = [
    'logo_cotizacion_id' => $requestData['logo_cotizacion_id'],
    'pedido_id' => $requestData['pedido_id'],
    'cotizacion_id' => $requestData['cotizacion_id'],
    'numero_cotizacion' => $requestData['numero_cotizacion'],
    'cliente' => $requestData['cliente'],
    'forma_de_pago' => $requestData['forma_de_pago'],
    'descripcion' => $requestData['descripcion'],
    'tecnicas' => $requestData['tecnicas'],
    'observaciones' => $requestData['observaciones'],
];

// Simular el código del controlador (desde guardarLogoPedido)
$logoPedido = null;
if (!empty($validated['pedido_id'])) {
    $logoPedido = \App\Models\LogoPedido::find($validated['pedido_id']);
}

if (!$logoPedido) {
    // Crear uno nuevo con defaults para pedidos solo-logo
    $controller = $app->make(\App\Http\Controllers\Asesores\PedidoProduccionController::class);
    
    // Usamos reflexión para acceder al método privado
    $reflectionMethod = new \ReflectionMethod($controller, 'generarNumeroLogoPedido');
    $reflectionMethod->setAccessible(true);
    $numeroPedido = $reflectionMethod->invoke($controller);
    
    echo "=== NÚMERO GENERADO MEDIANTE REFLEXIÓN ===\n";
    echo "Número: $numeroPedido\n\n";
    
    $logoPedido = \App\Models\LogoPedido::create([
        'pedido_id' => null,
        'logo_cotizacion_id' => $validated['logo_cotizacion_id'],
        'numero_pedido' => $numeroPedido,
        'cotizacion_id' => $validated['cotizacion_id'],
        'numero_cotizacion' => $validated['numero_cotizacion'],
        'cliente' => $validated['cliente'],
        'asesora' => $usuario->name,
        'forma_de_pago' => $validated['forma_de_pago'],
        'encargado_orden' => $usuario->name,
        'fecha_de_creacion_de_orden' => now(),
        'estado' => \App\Enums\EstadoPedido::PENDIENTE_SUPERVISOR->value,
        'area' => 'creacion_de_orden',
        'descripcion' => $validated['descripcion'] ?? '',
        'tecnicas' => $validated['tecnicas'] ?? [],
        'observaciones' => $validated['observaciones'] ?? '',
    ]);

    echo "✅ Logo Pedido CREADO:\n";
    echo "   ID: {$logoPedido->id}\n";
    echo "   Número: {$logoPedido->numero_pedido}\n";
    echo "   Cliente: {$logoPedido->cliente}\n";
    echo "   Estado: {$logoPedido->estado}\n";
    echo "   Asesora: {$logoPedido->asesora}\n\n";
}

// Verificar que la secuencia avanzó correctamente
$secuenciaActual = DB::table('numero_secuencias')
    ->where('tipo', 'logo_pedidos')
    ->first();

echo "=== ESTADO DE SECUENCIA ===\n";
echo "Siguiente número: {$secuenciaActual->siguiente}\n";
echo "Esperado: 101 (fue 100, ahora debe ser 101)\n\n";

// Mostrar últimos logo_pedidos
echo "=== ÚLTIMOS 3 LOGO PEDIDOS ===\n";
$ultimos = \App\Models\LogoPedido::orderBy('id', 'desc')->take(3)->get(['id', 'numero_pedido', 'cliente']);
foreach ($ultimos as $lp) {
    echo "ID: {$lp->id} | Número: {$lp->numero_pedido} | Cliente: {$lp->cliente}\n";
}
