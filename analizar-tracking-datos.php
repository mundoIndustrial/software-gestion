<?php

require 'vendor/autoload.php';

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Usar la BD directamente
\DB::enableQueryLog();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║       ANÁLISIS: DATOS EN BD vs DATOS EN RESPUESTA               ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$pedidoId = 45808;

// 1. DATOS EN BD
echo "1️⃣  DATOS DIRECTOS EN BASE DE DATOS\n";
echo "─────────────────────────────────────────────────────────────────\n";

$pedidoDb = \DB::table('pedidos_produccion')
    ->where('numero_pedido', $pedidoId)
    ->orWhere('id', $pedidoId)
    ->first();

if ($pedidoDb) {
    echo "✓ Pedido encontrado (ID: {$pedidoDb->id}, numero_pedido: {$pedidoDb->numero_pedido})\n\n";
    echo "Campos principales:\n";
    echo "  • estado: " . ($pedidoDb->estado ?? 'NULL') . "\n";
    echo "  • fecha_de_creacion_de_orden: " . ($pedidoDb->fecha_de_creacion_de_orden ?? 'NULL') . "\n";
    echo "  • fecha_estimada_de_entrega: " . ($pedidoDb->fecha_estimada_de_entrega ?? 'NULL') . "\n";
    echo "  • cliente: " . ($pedidoDb->cliente ?? 'NULL') . "\n";
    echo "  • area: " . ($pedidoDb->area ?? 'NULL') . "\n";
    echo "  • created_at: " . ($pedidoDb->created_at ?? 'NULL') . "\n";
} else {
    echo "✗ Pedido NO encontrado en BD\n";
}

echo "\n\n";

// 2. DATOS DEL USECASE
echo "2️⃣  DATOS DEL USECASE (ObtenerProcesosPorPedidoUseCase)\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $useCase = app(\App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase::class);
    $resultado = $useCase->ejecutar($pedidoId);
    
    echo "✓ UseCase ejecutado exitosamente\n\n";
    echo "Estructura devuelta:\n";
    
    $array = $resultado;
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (\Exception $e) {
    echo "✗ Error en UseCase: " . $e->getMessage() . "\n";
}

echo "\n\n";

// 3. ENDPOINT API
echo "3️⃣  ENDPOINT /api/ordenes/{$pedidoId}/procesos\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $controller = app(\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class);
    
    // Simular que se llama a getProcesos
    $response = $controller->getProcesos($pedidoId);
    $apiData = json_decode($response->getContent(), true);
    
    echo "✓ API ejecutada\n\n";
    echo "Respuesta:\n";
    echo json_encode($apiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (\Exception $e) {
    echo "✗ Error en API: " . $e->getMessage() . "\n";
}

echo "\n\n";

// 4. ENDPOINT /registros/{id}/recibos-datos
echo "4️⃣  ENDPOINT /registros/{$pedidoId}/recibos-datos (estructura esperada)\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $pedidoController = app(\App\Http\Controllers\Api_temp\PedidoController::class);
    
    // Llamar a obtenerDetalleCompleto
    $response = $pedidoController->obtenerDetalleCompleto($pedidoId, false);
    $detalleData = json_decode($response->getContent(), true);
    
    echo "✓ obtenerDetalleCompleto ejecutado\n\n";
    
    if (isset($detalleData['data'])) {
        echo "Campos principales en 'data':\n";
        $data = $detalleData['data'];
        echo "  • numero: " . ($data['numero'] ?? 'NULL') . "\n";
        echo "  • numero_pedido: " . ($data['numero_pedido'] ?? 'NULL') . "\n";
        echo "  • cliente: " . ($data['cliente'] ?? 'NULL') . "\n";
        echo "  • estado: " . ($data['estado'] ?? 'NULL') . "\n";
        echo "  • fecha_creacion: " . ($data['fecha_creacion'] ?? 'NULL') . "\n";
        echo "  • fecha: " . ($data['fecha'] ?? 'NULL') . "\n";
        echo "  • area: " . ($data['area'] ?? 'NULL') . "\n";
        echo "  • forma_de_pago: " . ($data['forma_de_pago'] ?? 'NULL') . "\n";
        
        echo "\n  Campos disponibles: " . implode(", ", array_keys($data)) . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n\n";

// 5. ANÁLISIS COMPARATIVO
echo "5️⃣  ANÁLISIS COMPARATIVO\n";
echo "─────────────────────────────────────────────────────────────────\n";

if ($pedidoDb) {
    echo "COMPARACIÓN:\n";
    echo "┌─────────────────────────┬──────────────────┬──────────────────┐\n";
    echo "│ Campo                   │ Base de Datos    │ DTO Response     │\n";
    echo "├─────────────────────────┼──────────────────┼──────────────────┤\n";
    
    try {
        $useCase = app(\App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase::class);
        $resultado = $useCase->ejecutar($pedidoId);
        
        $campos = [
            'numero_pedido' => 'numero_pedido',
            'estado' => 'estado',
            'cliente' => 'cliente',
            'fecha_de_creacion_de_orden' => 'fecha_creacion',
        ];
        
        foreach ($campos as $dbField => $dtoField) {
            $bdValue = $pedidoDb->$dbField ?? 'NULL';
            $dtoValue = $resultado->$dtoField ?? 'NULL';
            
            $match = ($bdValue == $dtoValue) ? '✓' : '✗';
            
            printf("│ %-23s │ %-16s │ %-16s │ %s\n", 
                $dbField, 
                substr($bdValue, 0, 16), 
                substr($dtoValue, 0, 16),
                $match
            );
        }
        
    } catch (\Exception $e) {
        echo "Error en comparación: " . $e->getMessage() . "\n";
    }
    
    echo "└─────────────────────────┴──────────────────┴──────────────────┘\n";
}

echo "\n";
