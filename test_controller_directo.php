<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Simular una solicitud HTTP al endpoint de la API
use Illuminate\Http\Request;
use App\Infrastructure\Http\Controllers\Operario\OperarioController;

$controller = new OperarioController(
    app('App\Application\Operario\Services\ObtenerPedidosOperarioService'),
    app('App\Domain\Operario\Repositories\OperarioRepository')
);

echo "═════════════════════════════════════════════════\n";
echo "TEST DIRECTO: Llamar getPedidoData(45452)\n";
echo "═════════════════════════════════════════════════\n\n";

try {
    $response = $controller->getPedidoData(45452);
    $content = $response->getContent();
    
    echo "Response status: " . $response->getStatusCode() . "\n\n";
    echo "Contenido JSON:\n";
    
    $json = json_decode($content, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        echo "\n\n✅ Fotos en la respuesta:\n";
        if (isset($json['fotos'])) {
            echo "  - Cantidad: " . count($json['fotos']) . "\n";
            foreach($json['fotos'] as $idx => $foto) {
                echo "  - Foto " . ($idx + 1) . ": {$foto}\n";
            }
        } else {
            echo "  ❌ NO HAY CLAVE 'fotos' EN LA RESPUESTA\n";
            echo "  - Claves disponibles: " . json_encode(array_keys($json)) . "\n";
        }
    } else {
        echo "❌ JSON inválido: " . json_last_error_msg() . "\n";
        echo "Contenido crudo:\n" . $content . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al llamar getPedidoData:\n";
    echo "  - Mensaje: " . $e->getMessage() . "\n";
    echo "  - Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  - Stack: " . $e->getTraceAsString() . "\n";
}
