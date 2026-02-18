<?php

// Configuración de base de datos
$host = 'localhost';
$dbname = 'mundoindustrial';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DEBUG DE DATOS EPP ===\n\n";
    
    // Buscar todos los items con area = 'EPP'
    $stmt = $pdo->prepare("SELECT * FROM bodega_detalles_talla WHERE area = 'EPP'");
    $stmt->execute();
    $eppItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Items con area = 'EPP':\n";
    foreach ($eppItems as $item) {
        echo "- Pedido: {$item['numero_pedido']}, Talla: {$item['talla']}, Prenda: {$item['prenda_nombre']}\n";
        echo "  Estado bodega: '{$item['estado_bodega']}'\n";
        echo "  EPP Estado: '{$item['epp_estado']}'\n";
        echo "  Costura Estado: '{$item['costura_estado']}'\n";
        echo "  Fecha: {$item['created_at']}\n\n";
    }
    
    echo "Total items EPP: " . count($eppItems) . "\n\n";
    
    // Buscar items con area = 'EPP' y estado_bodega = 'Pendiente'
    $stmt = $pdo->prepare("SELECT * FROM bodega_detalles_talla WHERE area = 'EPP' AND (estado_bodega = 'Pendiente' OR estado_bodega IS NULL OR estado_bodega = '')");
    $stmt->execute();
    $eppPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Items EPP con estado_bodega = 'Pendiente' (o nulo/vacío):\n";
    foreach ($eppPendientes as $item) {
        echo "- Pedido: {$item['numero_pedido']}, Talla: {$item['talla']}, Prenda: {$item['prenda_nombre']}\n";
        echo "  Estado bodega: '{$item['estado_bodega']}'\n\n";
    }
    
    echo "Total items EPP Pendientes: " . count($eppPendientes) . "\n\n";
    
    // Comparar con Costura
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bodega_detalles_talla WHERE area = 'Costura'");
    $stmt->execute();
    $costuraCount = $stmt->fetchColumn();
    echo "Items con area = 'Costura': " . $costuraCount . "\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bodega_detalles_talla WHERE area = 'Costura' AND (estado_bodega = 'Pendiente' OR estado_bodega IS NULL OR estado_bodega = '')");
    $stmt->execute();
    $costuraPendientes = $stmt->fetchColumn();
    echo "Items Costura con estado_bodega = 'Pendiente': " . $costuraPendientes . "\n\n";
    
    // Mostrar todos los valores de estado_bodega para EPP
    $stmt = $pdo->prepare("SELECT DISTINCT estado_bodega FROM bodega_detalles_talla WHERE area = 'EPP'");
    $stmt->execute();
    $estadosEPP = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Valores de estado_bodega para EPP: " . implode(', ', $estadosEPP) . "\n";
    
    // Mostrar todos los valores de estado_bodega para Costura
    $stmt = $pdo->prepare("SELECT DISTINCT estado_bodega FROM bodega_detalles_talla WHERE area = 'Costura'");
    $stmt->execute();
    $estadosCostura = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Valores de estado_bodega para Costura: " . implode(', ', $estadosCostura) . "\n";
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}

?>
