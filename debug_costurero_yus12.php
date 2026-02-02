<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ProcesoPrenda;
use App\Models\PedidoProduccion;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;

echo "\n=== DEBUG: USUARIO yus12@gmail.com (COSTURA-REFLECTIVO) ===\n\n";

// 1. Obtener el usuario
$usuario = User::where('email', 'yus12@gmail.com')->first();

if (!$usuario) {
    echo "❌ Usuario NO encontrado\n";
    exit;
}

echo "✅ Usuario encontrado:\n";
echo "   ID: {$usuario->id}\n";
echo "   Nombre: {$usuario->name}\n";
echo "   Email: {$usuario->email}\n";
echo "   Rol: " . ($usuario->roles()->first()?->name ?? 'Sin rol') . "\n\n";

// 2. Ver TODOS los procesos en la BD
echo "=== TODOS LOS PROCESOS EN LA BD ===\n";
$todosProcesos = ProcesoPrenda::all();
echo "Total: {$todosProcesos->count()}\n\n";

foreach ($todosProcesos->take(20) as $proceso) {
    echo "Pedido: {$proceso->numero_pedido} | Proceso: {$proceso->proceso} | Encargado: {$proceso->encargado} | Estado: {$proceso->estado_proceso}\n";
}

echo "\n";

// 3. Ver procesos donde el encargado coincida con el nombre del usuario
echo "=== PROCESOS DONDE ENCARGADO = '" . $usuario->name . "' ===\n";
$usuarioNormalizado = strtolower(trim($usuario->name));
$procesosDelUsuario = ProcesoPrenda::all()->filter(function($p) use ($usuarioNormalizado) {
    return strtolower(trim($p->encargado)) === $usuarioNormalizado;
});

echo "Total: {$procesosDelUsuario->count()}\n";
foreach ($procesosDelUsuario as $proceso) {
    echo "Pedido: {$proceso->numero_pedido} | Proceso: {$proceso->proceso} | Encargado: {$proceso->encargado} | Estado: {$proceso->estado_proceso}\n";
}

echo "\n";

// 4. AHORA: Usar el servicio real para ver qué pedidos se retornan
echo "=== USANDO ObtenerPedidosOperarioService ===\n";
try {
    $service = app(ObtenerPedidosOperarioService::class);
    
    // Simular autenticación
    auth()->loginUsingId($usuario->id);
    
    $datosOperario = $service->obtenerPedidosDelOperario($usuario);
    
    echo "Datos del operario:\n";
    echo "   Nombre: {$datosOperario->nombreOperario}\n";
    echo "   Tipo: {$datosOperario->tipoOperario}\n";
    echo "   Área: {$datosOperario->areaOperario}\n";
    echo "   Total pedidos: {$datosOperario->totalPedidos}\n";
    echo "   En proceso: {$datosOperario->pedidosEnProceso}\n";
    echo "   Completados: {$datosOperario->pedidosCompletados}\n\n";
    
    echo "Pedidos que DEBERÍA ver:\n";
    foreach ($datosOperario->pedidos as $pedido) {
        echo "   - Pedido #{$pedido['numero_pedido']} | {$pedido['descripcion']} | Estado: {$pedido['estado']}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";

