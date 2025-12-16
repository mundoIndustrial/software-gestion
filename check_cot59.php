<?php
$db = new mysqli('localhost', 'root', '29522628', 'mundoindustrial');

echo "=== COTIZACIÓN 59 ===\n";

// Contar prendas
$result = $db->query("SELECT COUNT(*) as count FROM prendas WHERE cotizacion_id = 59");
$row = $result->fetch_assoc();
echo "Total prendas: " . $row['count'] . "\n";

// Contar variantes (telas)
$result = $db->query("SELECT COUNT(*) as count FROM prendas WHERE cotizacion_id = 59 AND prenda_tela_id IS NOT NULL");
$row = $result->fetch_assoc();
echo "Prendas con tela: " . $row['count'] . "\n";

// Listar prendas y sus telas
echo "\nDetalles de prendas:\n";
$result = $db->query("SELECT id, nombre_producto, prenda_tela_id FROM prendas WHERE cotizacion_id = 59");
while ($prenda = $result->fetch_assoc()) {
    echo "- Prenda ID {$prenda['id']}: {$prenda['nombre_producto']} | Tela ID: " . ($prenda['prenda_tela_id'] ?? 'NULL') . "\n";
}

// Contar fotos
$result = $db->query("SELECT COUNT(*) as count FROM prenda_fotos WHERE prenda_id IN (SELECT id FROM prendas WHERE cotizacion_id = 59)");
$row = $result->fetch_assoc();
echo "\nFotos de prendas: " . $row['count'] . "\n";

// Contar fotos de telas
$result = $db->query("SELECT COUNT(*) as count FROM prenda_tela_fotos WHERE prenda_id IN (SELECT id FROM prendas WHERE cotizacion_id = 59)");
$row = $result->fetch_assoc();
echo "Fotos de telas: " . $row['count'] . "\n";
?>
