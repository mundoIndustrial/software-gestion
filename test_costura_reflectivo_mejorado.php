<?php

// Script de prueba: Verificar que Costura-Reflectivo funciona correctamente

use App\Models\User;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║          PRUEBA: USUARIO COSTURA-REFLECTIVO MEJORADO             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// 1. Obtener usuario Costura-Reflectivo
echo "📋 PASO 1: Obtener usuario Costura-Reflectivo\n";
echo "─────────────────────────────────────────────\n";
$usuario = User::where('email', 'costura-reflectivo@mundoindustrial.com')->first();

if (!$usuario) {
    echo "❌ FALLO: Usuario NO encontrado\n";
    exit(1);
}

echo "✅ Usuario encontrado:\n";
echo "   ID: {$usuario->id}\n";
echo "   Nombre: {$usuario->name}\n";
echo "   Email: {$usuario->email}\n";
echo "   Roles: " . implode(', ', $usuario->roles()->pluck('name')->toArray()) . "\n\n";

// 2. Verificar que filtra por área "Costura"
echo "📋 PASO 2: Contar pedidos con área 'Costura'\n";
echo "─────────────────────────────────────────────\n";
$totalCostura = PedidoProduccion::where('area', 'Costura')->count();
echo "✅ Total de pedidos con área 'Costura': $totalCostura\n\n";

// 3. Verificar que hay procesos con Ramiro
echo "📋 PASO 3: Contar procesos Costura con encargado Ramiro\n";
echo "────────────────────────────────────────────────────────\n";
$procesosRamiro = ProcesoPrenda::where('proceso', 'Costura')
    ->whereRaw("LOWER(TRIM(encargado)) = 'ramiro'")
    ->count();
echo "✅ Total procesos Costura → Ramiro: $procesosRamiro\n\n";

// 4. Ejecutar servicio
echo "📋 PASO 4: Ejecutar ObtenerPedidosOperarioService\n";
echo "──────────────────────────────────────────────────\n";
$service = new ObtenerPedidosOperarioService();
$resultado = $service->obtenerPedidosDelOperario($usuario);

echo "✅ Servicio ejecutado sin errores:\n";
echo "   Tipo Operario: {$resultado->tipoOperario}\n";
echo "   Área Operario: {$resultado->areaOperario}\n";
echo "   Total Pedidos: {$resultado->totalPedidos}\n";
echo "   En Proceso: {$resultado->pedidosEnProceso}\n";
echo "   Completados: {$resultado->pedidosCompletados}\n\n";

// 5. Verificar que solo devuelve pedidos con área Costura + Ramiro
echo "📋 PASO 5: Validación de Pedidos Filtrados\n";
echo "──────────────────────────────────────────\n";

if ($resultado->totalPedidos > 0) {
    $todasLasCondicionesCumplen = true;
    $ejemplosPedidos = array_slice($resultado->pedidos, 0, 3);
    
    foreach ($ejemplosPedidos as $index => $pedido) {
        $numero = $pedido['numero_pedido'];
        
        // Obtener el pedido real para verificar área
        $pedidoReal = PedidoProduccion::where('numero_pedido', $numero)->first();
        $area = $pedidoReal->area ?? 'DESCONOCIDA';
        
        // Verificar procesos
        $tieneRamiro = ProcesoPrenda::where('numero_pedido', $numero)
            ->where('proceso', 'Costura')
            ->whereRaw("LOWER(TRIM(encargado)) = 'ramiro'")
            ->exists();
        
        $cumple = ($area === 'Costura' && $tieneRamiro);
        $todasLasCondicionesCumplen = $todasLasCondicionesCumplen && $cumple;
        
        $status = $cumple ? '✅' : '❌';
        echo "$status Pedido #{$numero}: Área={$area}, Tiene Ramiro={$tieneRamiro}\n";
    }
    
    echo "\n" . ($todasLasCondicionesCumplen ? "✅" : "❌") . " VALIDACIÓN: ";
    echo ($todasLasCondicionesCumplen ? "TODOS cumplen condiciones" : "Algunos NO cumplen") . "\n\n";
} else {
    echo "⚠️  No hay pedidos para mostrar\n\n";
}

// 6. Verificar que el Listener está registrado
echo "📋 PASO 6: Verificar configuración del Listener\n";
echo "────────────────────────────────────────────────\n";

$eventServiceProvider = file_get_contents(__DIR__ . '/app/Providers/EventServiceProvider.php');
if (strpos($eventServiceProvider, 'CrearProcesosParaCotizacionReflectivo') !== false) {
    echo "✅ Listener registrado en EventServiceProvider\n";
} else {
    echo "❌ Listener NO está registrado\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                    PRUEBA COMPLETADA                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";
