<?php
$conn = new mysqli('localhost', 'root', '', 'mundo_bd');

echo "═══════════════════════════════════════════════════════════════\n";
echo "ESTRUCTURA DE prenda_tela_fotos_cot\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$result = $conn->query('DESCRIBE prenda_tela_fotos_cot');
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']}) - Null: {$row['Null']} - Key: {$row['Key']}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "STRUKTURA DE prenda_fotos_tela_pedido\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$result2 = $conn->query('DESCRIBE prenda_fotos_tela_pedido');
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']}) - Null: {$row['Null']} - Key: {$row['Key']}\n";
    }
} else {
    echo "Tabla no existe!\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "RELACIONES Y FOREIGN KEYS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$query = "SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME IN ('prenda_tela_fotos_cot', 'prenda_fotos_tela_pedido')
AND REFERENCED_TABLE_NAME IS NOT NULL";

$result3 = $conn->query($query);
while ($row = $result3->fetch_assoc()) {
    echo "  {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "MUESTRA DE DATOS EN prenda_tela_fotos_cot\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$result4 = $conn->query('SELECT * FROM prenda_tela_fotos_cot LIMIT 1');
if ($result4 && $result4->num_rows > 0) {
    $row = $result4->fetch_assoc();
    foreach ($row as $col => $val) {
        echo "  $col: " . ($val ?? 'NULL') . "\n";
    }
} else {
    echo "Sin datos\n";
}

$conn->close();
?>
