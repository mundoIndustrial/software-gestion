<?php
// Verificación de cálculos en la base de datos
$db = new PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');
$stmt = $db->query('SELECT id, tiempo_disponible, meta, eficiencia, paradas_programadas, tipo_extendido, numero_capas FROM registro_piso_corte ORDER BY id DESC LIMIT 5');

echo "=== ÚLTIMOS 5 REGISTROS DE CORTE ===\n\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $td = $row['tiempo_disponible'];
    $meta = $row['meta'];
    $eff = $row['eficiencia'];
    $paradas = $row['paradas_programadas'];
    $tipo = $row['tipo_extendido'];
    $capas = $row['numero_capas'];
    
    echo "ID: $id\n";
    echo "  Paradas: $paradas | Tipo: $tipo (capas: $capas)\n";
    echo "  TD: " . number_format($td, 2) . " | Meta: " . number_format($meta, 2) . " | Eff: " . number_format($eff, 2) . "\n";
    
    if ($td == 0 && $meta == 0 && $eff == 0) {
        echo "  ❌ PROBLEMA: Todos cero\n";
    } else if ($td > 0) {
        echo "  ✅ Cálculos OK\n";
    }
    echo "\n";
}
?>
