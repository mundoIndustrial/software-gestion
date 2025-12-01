<?php
require 'vendor/autoload.php';

$db = new PDO('mysql:host=SERVERMI;dbname=mundoindustrial', 'root', 'Xc3^KM4$N#L7!');

// Obtener 3 órdenes con descripción
$stmt = $db->prepare('SELECT pedido, descripcion FROM tabla_original_bodega WHERE descripcion IS NOT NULL LIMIT 3');
$stmt->execute();

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Pedido: " . $row['pedido'] . "\n";
    echo "Descripcion (primeros 300 caracteres):\n";
    echo substr($row['descripcion'], 0, 300) . "\n";
    echo str_repeat("=", 80) . "\n";
}
?>
