<?php

/**
 * Script de prueba para verificar la creación automática de procesos
 * al aprobar un pedido en insumos/materiales
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Insumos\ProcesoAutomaticoService;
use App\Services\Insumos\MaterialesService;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

echo "=== PRUEBA DE CREACIÓN AUTOMÁTICA DE PROCESOS ===\n\n";

// 1. Buscar un pedido en estado Pendiente para probar
echo "1. Buscando pedido en estado 'Pendiente'...\n";
$pedidoPendiente = PedidoProduccion::where('estado', 'Pendiente')->first();

if (!$pedidoPendiente) {
    echo "❌ No se encontraron pedidos en estado 'Pendiente' para probar\n";
    echo "   Creando un pedido de prueba...\n";
    
    // Crear pedido de prueba si no existe
    $pedidoPendiente = PedidoProduccion::create([
        'numero_pedido' => 'TEST-' . time(),
        'cliente' => 'Cliente Prueba',
        'estado' => 'Pendiente',
        'area' => 'Creación de orden',
        'fecha_de_creacion_de_orden' => now(),
        'asesor' => 'Sistema',
        'forma_de_pago' => 'Contado'
    ]);
    
    echo "✅ Pedido de prueba creado: {$pedidoPendiente->numero_pedido}\n";
} else {
    echo "✅ Pedido encontrado: {$pedidoPendiente->numero_pedido}\n";
}

// 2. Verificar si tiene prendas asociadas
echo "\n2. Verificando prendas del pedido...\n";
$prendas = PrendaPedido::where('pedido_produccion_id', $pedidoPendiente->id)->get();

if ($prendas->isEmpty()) {
    echo "⚠️  El pedido no tiene prendas asociadas. Creando prendas de prueba...\n";
    
    // Crear prendas de prueba
    $prenda1 = PrendaPedido::create([
        'pedido_produccion_id' => $pedidoPendiente->id,
        'nombre_prenda' => 'Camisa Prueba',
        'descripcion' => 'Camisa para probar procesos automáticos',
        'de_bodega' => false,
        'observaciones' => 'Prenda de prueba'
    ]);
    
    $prenda2 = PrendaPedido::create([
        'pedido_produccion_id' => $pedidoPendiente->id,
        'nombre_prenda' => 'Polo Prueba',
        'descripcion' => 'Polo con bordado para probar procesos',
        'de_bodega' => true,
        'observaciones' => 'Prenda con bordado'
    ]);
    
    echo "✅ Creadas 2 prendas de prueba\n";
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoPendiente->id)->get();
} else {
    echo "✅ El pedido tiene {$prendas->count()} prendas asociadas\n";
}

// 3. Probar la creación automática de procesos
echo "\n3. Probando creación automática de procesos...\n";
$procesoService = new ProcesoAutomaticoService();
$resultado = $procesoService->crearProcesosParaPedido($pedidoPendiente->numero_pedido);

if ($resultado['success']) {
    echo "✅ Procesos creados exitosamente\n";
    echo "   Total procesos creados: {$resultado['procesos_creados']}\n";
    
    if (!empty($resultado['detalles'])) {
        echo "   Detalles:\n";
        foreach ($resultado['detalles'] as $detalle) {
            echo "     - {$detalle}\n";
        }
    }
} else {
    echo "❌ Error al crear procesos: {$resultado['message']}\n";
}

// 4. Verificar los procesos creados en la BD
echo "\n4. Verificando procesos en la base de datos...\n";
$procesosCreados = \App\Models\ProcesosPrenda::where('numero_pedido', $pedidoPendiente->numero_pedido)->get();

echo "✅ Total de procesos en BD: {$procesosCreados->count()}\n";
foreach ($procesosCreados as $proceso) {
    echo "   - ID: {$proceso->id}, Proceso: {$proceso->proceso}, Estado: {$proceso->estado_proceso}\n";
    echo "     Fecha inicio: {$proceso->fecha_inicio}, Código: {$proceso->codigo_referencia}\n";
}

// 5. Probar el flujo completo del MaterialesService
echo "\n5. Probando flujo completo del MaterialesService...\n";
$materialesService = new MaterialesService();
$resultadoCompleto = $materialesService->cambiarEstadoPedido($pedidoPendiente->numero_pedido, 'En Ejecución');

if ($resultadoCompleto['success']) {
    echo "✅ Flujo completo exitoso\n";
    echo "   Mensaje: {$resultadoCompleto['message']}\n";
    echo "   Estado: {$resultadoCompleto['estado']}, Área: {$resultadoCompleto['area']}\n";
    echo "   Procesos creados: {$resultadoCompleto['procesos_creados']}\n";
    
    // Verificar que el pedido cambió de estado
    $pedidoActualizado = PedidoProduccion::find($pedidoPendiente->id);
    echo "   Estado actual del pedido: {$pedidoActualizado->estado}\n";
    echo "   Área actual del pedido: {$pedidoActualizado->area}\n";
} else {
    echo "❌ Error en flujo completo: {$resultadoCompleto['message']}\n";
}

// 6. Limpiar datos de prueba (opcional)
echo "\n6. ¿Desea limpiar los datos de prueba? (s/n): ";
$handle = fopen("php://stdin", "r");
$respuesta = trim(fgets($handle));
fclose($handle);

if (strtolower($respuesta) === 's') {
    echo "   Limpiando datos de prueba...\n";
    
    // Eliminar procesos creados
    \App\Models\ProcesosPrenda::where('numero_pedido', $pedidoPendiente->numero_pedido)->delete();
    
    // Eliminar prendas creadas
    PrendaPedido::where('pedido_produccion_id', $pedidoPendiente->id)->delete();
    
    // Eliminar pedido creado
    $pedidoPendiente->delete();
    
    echo "✅ Datos de prueba eliminados\n";
} else {
    echo "   Datos de prueba conservados para revisión manual\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
echo "Pedido utilizado: {$pedidoPendiente->numero_pedido}\n";
echo "Revise la tabla 'procesos_prenda' para verificar los resultados\n";
