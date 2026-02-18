<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG API APROBADOS ===\n";

try {
    // Simular la llamada al método obtenerAprobados
    $request = new \Illuminate\Http\Request();
    
    // Crear una instancia del controlador
    $controller = new \App\Http\Controllers\CarteraPedidosController();
    
    echo "Llamando a obtenerAprobados...\n";
    $response = $controller->obtenerAprobados($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Data:\n";
    
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "✅ Success: true\n";
        echo "Total pedidos: " . ($data['pagination']['total'] ?? 0) . "\n";
        
        if (!empty($data['data'])) {
            echo "\nPedidos encontrados:\n";
            foreach ($data['data'] as $pedido) {
                echo sprintf("- ID: %d | Pedido: %s | Cliente: %s\n", 
                    $pedido['id'], 
                    $pedido['numero_pedido'] ?? 'N/A', 
                    $pedido['cliente']
                );
            }
        } else {
            echo "❌ No se encontraron pedidos en la respuesta\n";
        }
    } else {
        echo "❌ Success: false\n";
        echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEBUG API ===\n";
