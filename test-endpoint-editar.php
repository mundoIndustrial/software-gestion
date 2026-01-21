<?php
/**
 * Script de Prueba: Endpoint /supervisor-pedidos/{id}/editar
 * 
 * Simula una solicitud HTTP GET al endpoint de edición
 * y verifica que retorna todos los datos necesarios
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupervisorPedidosController;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// ID del pedido a probar
$pedidoId = 1; // Cambiar según sea necesario

echo "=== PRUEBA DE ENDPOINT /supervisor-pedidos/{id}/editar ===\n\n";
echo "Pedido ID: $pedidoId\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Crear instancia del controlador
    $controller = new SupervisorPedidosController();
    
    // Llamar al método edit
    $response = $controller->edit($pedidoId);
    
    // Obtener datos JSON
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✅ Respuesta exitosa del endpoint\n\n";
        
        $orden = $data['orden'];
        
        echo "📋 DATOS DEL PEDIDO:\n";
        echo "  Número: " . $orden['numero_pedido'] . "\n";
        echo "  Cliente: " . $orden['cliente'] . "\n";
        echo "  Prendas: " . count($orden['prendas']) . "\n\n";
        
        // Verificar cada prenda
        foreach ($orden['prendas'] as $idx => $prenda) {
            echo "--- PRENDA " . ($idx + 1) . " ---\n";
            echo "Nombre: " . $prenda['nombre_prenda'] . "\n";
            echo "Descripción: " . $prenda['descripcion'] . "\n";
            
            // Variantes
            echo "\n✅ VARIANTES: " . count($prenda['variantes']) . "\n";
            if (count($prenda['variantes']) > 0) {
                foreach ($prenda['variantes'] as $var) {
                    echo "  ✓ Talla: " . $var['talla'] . ", Cantidad: " . $var['cantidad'] . ", Género: " . $var['genero'] . "\n";
                    echo "    - Color: " . ($var['color_nombre'] ?? 'N/A') . "\n";
                    echo "    - Tela: " . ($var['tela_nombre'] ?? 'N/A') . "\n";
                    echo "    - Manga: " . ($var['tipo_manga_nombre'] ?? 'N/A') . "\n";
                    echo "    - Broche: " . ($var['tipo_broche_nombre'] ?? 'N/A') . "\n";
                }
            } else {
                echo "  ⚠️  Sin variantes\n";
            }
            
            // Tallas por género
            echo "\n✅ TALLAS POR GÉNERO:\n";
            if (isset($prenda['generosConTallas']) && count($prenda['generosConTallas']) > 0) {
                foreach ($prenda['generosConTallas'] as $genero => $data) {
                    echo "  $genero: " . implode(', ', $data['tallas']) . "\n";
                }
            } else {
                echo "  ⚠️  Sin tallas\n";
            }
            
            // Telas agregadas
            echo "\n✅ TELAS AGREGADAS: " . count($prenda['telasAgregadas']) . "\n";
            if (count($prenda['telasAgregadas']) > 0) {
                foreach ($prenda['telasAgregadas'] as $tela) {
                    echo "  ✓ Tela: " . $tela['tela'] . ", Color: " . $tela['color'] . ", Ref: " . $tela['referencia'] . "\n";
                }
            } else {
                echo "  ⚠️  Sin telas\n";
            }
            
            // Imágenes de prenda
            echo "\n✅ IMÁGENES DE PRENDA: " . count($prenda['imagenes']) . "\n";
            if (count($prenda['imagenes']) > 0) {
                foreach ($prenda['imagenes'] as $img) {
                    echo "  ✓ " . $img['url'] . "\n";
                }
            } else {
                echo "  ⚠️  Sin imágenes\n";
            }
            
            // Imágenes de tela
            echo "\n✅ IMÁGENES DE TELA: " . count($prenda['imagenes_tela']) . "\n";
            if (count($prenda['imagenes_tela']) > 0) {
                foreach ($prenda['imagenes_tela'] as $img) {
                    echo "  ✓ " . $img['url'] . "\n";
                }
            } else {
                echo "  ⚠️  Sin imágenes de tela\n";
            }
            
            // Procesos
            echo "\n✅ PROCESOS: " . count($prenda['procesos']) . "\n";
            if (count($prenda['procesos']) > 0) {
                foreach ($prenda['procesos'] as $proceso) {
                    echo "  ✓ Tipo: " . $proceso['tipo'] . "\n";
                    echo "    - Observaciones: " . $proceso['observaciones'] . "\n";
                    echo "    - Ubicaciones: " . implode(', ', $proceso['ubicaciones']) . "\n";
                    echo "    - Imágenes: " . count($proceso['imagenes']) . "\n";
                    
                    if (count($proceso['imagenes']) > 0) {
                        foreach ($proceso['imagenes'] as $img) {
                            echo "      • " . $img['url'] . "\n";
                        }
                    }
                }
            } else {
                echo "  ⚠️  Sin procesos\n";
            }
            
            echo "\n";
        }
        
        echo "\n=== RESUMEN DE VALIDACIÓN ===\n";
        
        $validaciones = [
            'Variantes cargadas' => count($orden['prendas'][0]['variantes'] ?? []) > 0,
            'Tallas por género cargadas' => count($orden['prendas'][0]['generosConTallas'] ?? []) > 0,
            'Telas agregadas cargadas' => count($orden['prendas'][0]['telasAgregadas'] ?? []) > 0,
            'Imágenes de prenda cargadas' => count($orden['prendas'][0]['imagenes'] ?? []) > 0,
            'Imágenes de tela cargadas' => count($orden['prendas'][0]['imagenes_tela'] ?? []) > 0,
            'Procesos cargados' => count($orden['prendas'][0]['procesos'] ?? []) > 0,
        ];
        
        foreach ($validaciones as $validacion => $resultado) {
            echo ($resultado ? '✅' : '❌') . " $validacion\n";
        }
        
        $todosOk = array_reduce($validaciones, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        echo "\n" . ($todosOk ? "✅ TODOS LOS DATOS SE CARGAN CORRECTAMENTE" : "⚠️  ALGUNOS DATOS NO SE CARGAN") . "\n";
        
    } else {
        echo "❌ Error en la respuesta: " . $data['message'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n";
