<?php
/**
 * Revisar último pedido creado en BD
 */

$mysqli = new mysqli('localhost', 'root', '', 'mundo_bd');

if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

echo "ÚLTIMA PEDIDO CREADO:\n";
echo "====================\n\n";

// Obtener último pedido
$result = $mysqli->query("
    SELECT id, numero_pedido, estado, descripcion, created_at 
    FROM pedidos_produccion 
    ORDER BY id DESC 
    LIMIT 1
");

$pedido = $result->fetch_assoc();

echo "ID: " . $pedido['id'] . "\n";
echo "Número: " . ($pedido['numero_pedido'] ?? 'NULL') . "\n";
echo "Estado: " . $pedido['estado'] . "\n";
echo "Descripción: " . ($pedido['descripcion'] ?? 'NULL') . "\n";
echo "Creado: " . $pedido['created_at'] . "\n\n";

echo "PRENDAS DEL PEDIDO:\n";
echo "==================\n";

$result = $mysqli->query("
    SELECT id, nombre_prenda, descripcion, descripcion_variaciones, cantidad, cantidad_talla 
    FROM prendas_pedido 
    WHERE numero_pedido = '" . $mysqli->real_escape_string($pedido['numero_pedido']) . "'
    ORDER BY id
");

echo "Total prendas: " . $result->num_rows . "\n\n";

while ($prenda = $result->fetch_assoc()) {
    echo "ID: " . $prenda['id'] . "\n";
    echo "  Nombre: " . $prenda['nombre_prenda'] . "\n";
    echo "  Descripción: " . (strlen($prenda['descripcion'] ?? '') > 0 ? substr($prenda['descripcion'], 0, 80) : 'NULL') . "\n";
    echo "  Variaciones: " . ($prenda['descripcion_variaciones'] ?? 'NULL') . "\n";
    echo "  Cantidad: " . $prenda['cantidad'] . "\n";
    echo "  Tallas JSON: " . (strlen($prenda['cantidad_talla'] ?? '') > 0 ? substr($prenda['cantidad_talla'], 0, 60) : 'NULL') . "\n";
    echo "\n";
}

$mysqli->close();
