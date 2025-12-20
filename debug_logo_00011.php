<?php
/**
 * Debug Script - Verificar datos del pedido LOGO-00011
 */
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/database.php';

try {
    // Crear conexión
    $host = env('DB_HOST', 'localhost');
    $db = env('DB_DATABASE', 'mundo_industrial');
    $user = env('DB_USERNAME', 'root');
    $pass = env('DB_PASSWORD', '');
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "\n================== CONSULTA LOGO-00011 ==================\n\n";
    
    // 1. Consultar logo_pedidos
    $sql = "SELECT * FROM logo_pedidos WHERE numero_pedido = 'LOGO-00011' LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $logoPedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($logoPedido) {
        echo "✅ LOGO-00011 encontrado en tabla logo_pedidos\n";
        echo "\n📋 DATOS DISPONIBLES EN logo_pedidos:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        foreach ($logoPedido as $key => $value) {
            if (in_array($key, ['cliente', 'asesora', 'descripcion', 'tecnicas', 'ubicaciones', 'fecha_de_creacion_de_orden', 'created_at', 'observaciones_tecnicas', 'forma_de_pago', 'encargado_orden', 'estado', 'area'])) {
                if (is_json($value)) {
                    echo "   ✓ {$key}: " . json_encode(json_decode($value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "   ✓ {$key}: " . ($value ?: '[vacío]') . "\n";
                }
            }
        }
    } else {
        echo "❌ LOGO-00011 NO encontrado\n";
    }
    
    // 2. Si tiene pedido_id, traer info de PedidoProduccion
    if ($logoPedido && $logoPedido['pedido_id']) {
        echo "\n📦 DATOS RELACIONADOS EN pedidos_produccion (ID: {$logoPedido['pedido_id']}):\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $sqlPedido = "SELECT id, numero_pedido, cliente, asesor_id, forma_de_pago, fecha_de_creacion_de_orden FROM pedidos_produccion WHERE id = ?";
        $stmtPedido = $pdo->prepare($sqlPedido);
        $stmtPedido->execute([$logoPedido['pedido_id']]);
        $pedidoProd = $stmtPedido->fetch(PDO::FETCH_ASSOC);
        
        if ($pedidoProd) {
            foreach ($pedidoProd as $key => $value) {
                echo "   ✓ {$key}: " . ($value ?: '[vacío]') . "\n";
            }
        } else {
            echo "   ⚠️  No se encontró el PedidoProduccion\n";
        }
    }
    
    // 3. Traer imágenes
    if ($logoPedido) {
        echo "\n🖼️  IMÁGENES EN logo_pedido_imagenes:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $sqlImages = "SELECT id, nombre_archivo, url, orden FROM logo_pedido_imagenes WHERE logo_pedido_id = ? ORDER BY orden ASC";
        $stmtImages = $pdo->prepare($sqlImages);
        $stmtImages->execute([$logoPedido['id']]);
        $imagenes = $stmtImages->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($imagenes)) {
            foreach ($imagenes as $img) {
                echo "   [{$img['orden']}] {$img['nombre_archivo']} → {$img['url']}\n";
            }
        } else {
            echo "   ⚠️  Sin imágenes\n";
        }
    }
    
    // 4. Resumen JSON para API
    echo "\n\n📡 RESPUESTA JSON QUE DEBERÍA ENVIAR LA API:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($logoPedido) {
        $response = [];
        $response['numero_pedido'] = $logoPedido['numero_pedido'];
        $response['cliente'] = $logoPedido['cliente'];
        $response['asesora'] = $logoPedido['asesora'];
        $response['descripcion'] = $logoPedido['descripcion'];
        $response['tecnicas'] = json_decode($logoPedido['tecnicas'], true) ?? [];
        $response['ubicaciones'] = json_decode($logoPedido['ubicaciones'], true) ?? [];
        $response['observaciones_tecnicas'] = $logoPedido['observaciones_tecnicas'];
        $response['forma_de_pago'] = $logoPedido['forma_de_pago'];
        $response['encargado_orden'] = $logoPedido['encargado_orden'];
        $response['fecha_de_creacion_de_orden'] = $logoPedido['fecha_de_creacion_de_orden'];
        $response['estado'] = $logoPedido['estado'];
        $response['area'] = $logoPedido['area'];
        $response['es_logo_pedido'] = true;
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    echo "\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

function is_json($string) {
    if (!is_string($string)) return false;
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
