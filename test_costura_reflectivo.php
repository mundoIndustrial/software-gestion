<?php

// Script de prueba para Costura-Reflectivo
// Ejecutar: php artisan tinker < test_costura_reflectivo.php

use App\Models\User;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;

// 1. Obtener usuario Costura-Reflectivo
echo "\n=== PRUEBA: USUARIO COSTURA-REFLECTIVO ===\n";
$usuario = User::where('email', 'costura-reflectivo@mundoindustrial.com')->first();

if (!$usuario) {
    echo "❌ Usuario Costura-Reflectivo NO ENCONTRADO\n";
    exit(1);
}

echo "✅ Usuario encontrado: {$usuario->name} (ID: {$usuario->id})\n";
echo "📋 Roles: " . implode(', ', $usuario->roles()->pluck('name')->toArray()) . "\n";

// 2. Verificar que el servicio funciona
echo "\n=== SERVICIO: ObtenerPedidosOperarioService ===\n";
$service = new ObtenerPedidosOperarioService();
$resultado = $service->obtenerPedidosDelOperario($usuario);

echo "✅ Servicio ejecutado correctamente\n";
echo "📊 Datos del operario:\n";
echo "   - Nombre: {$resultado->nombreOperario}\n";
echo "   - Tipo: {$resultado->tipoOperario}\n";
echo "   - Área: {$resultado->areaOperario}\n";
echo "   - Total de pedidos: {$resultado->totalPedidos}\n";
echo "   - En proceso: {$resultado->pedidosEnProceso}\n";
echo "   - Completados: {$resultado->pedidosCompletados}\n";

// 3. Mostrar pedidos encontrados
if ($resultado->totalPedidos > 0) {
    echo "\n=== PEDIDOS ENCONTRADOS ===\n";
    echo "Total: " . count($resultado->pedidos) . " pedidos\n\n";
    
    foreach (array_slice($resultado->pedidos, 0, 5) as $index => $pedido) {
        echo ($index + 1) . ". Pedido #{$pedido['numero_pedido']}\n";
        echo "   - Cliente: {$pedido['cliente']}\n";
        echo "   - Estado: {$pedido['estado']}\n";
        echo "   - Descripción: {$pedido['descripcion']}\n";
        echo "   - Cantidad: {$pedido['cantidad']}\n";
        echo "\n";
    }
    
    if (count($resultado->pedidos) > 5) {
        echo "... y " . (count($resultado->pedidos) - 5) . " pedidos más\n";
    }
} else {
    echo "\n⚠️  No hay pedidos encontrados para Costura-Reflectivo\n";
    echo "   (Esto es normal si no hay cotizaciones reflectivo o procesos asignados a Ramiro)\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";
