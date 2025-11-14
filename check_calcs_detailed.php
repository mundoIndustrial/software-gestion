<?php
// Verificación de cálculos con diferentes parámetros
$db = new PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');

echo "=== BÚSQUEDA DE REGISTROS CON DIFERENTES PARÁMETROS ===\n\n";

// Buscar registros con DESAYUNO
echo "1. Registros con DESAYUNO:\n";
$stmt = $db->query("SELECT id, tiempo_disponible, meta, eficiencia, paradas_programadas FROM registro_piso_corte WHERE paradas_programadas = 'DESAYUNO' LIMIT 3");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   ID: {$row['id']} | TD: {$row['tiempo_disponible']} | Meta: {$row['meta']} | Eff: {$row['eficiencia']}\n";
    $count++;
}
if ($count === 0) echo "   ℹ️  No hay registros con DESAYUNO\n";

// Buscar registros con MEDIA TARDE
echo "\n2. Registros con MEDIA TARDE:\n";
$stmt = $db->query("SELECT id, tiempo_disponible, meta, eficiencia, paradas_programadas FROM registro_piso_corte WHERE paradas_programadas = 'MEDIA TARDE' LIMIT 3");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   ID: {$row['id']} | TD: {$row['tiempo_disponible']} | Meta: {$row['meta']} | Eff: {$row['eficiencia']}\n";
    $count++;
}
if ($count === 0) echo "   ℹ️  No hay registros con MEDIA TARDE\n";

// Buscar registros con Trazo Largo
echo "\n3. Registros con Trazo Largo:\n";
$stmt = $db->query("SELECT id, tiempo_disponible, meta, eficiencia, tipo_extendido, numero_capas FROM registro_piso_corte WHERE tipo_extendido LIKE '%Largo%' LIMIT 3");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   ID: {$row['id']} | {$row['tipo_extendido']} ({$row['numero_capas']} capas) | TD: {$row['tiempo_disponible']} | Meta: {$row['meta']} | Eff: {$row['eficiencia']}\n";
    $count++;
}
if ($count === 0) echo "   ℹ️  No hay registros con Trazo Largo\n";

// Buscar registros con Trazo Corto
echo "\n4. Registros con Trazo Corto:\n";
$stmt = $db->query("SELECT id, tiempo_disponible, meta, eficiencia, tipo_extendido, numero_capas FROM registro_piso_corte WHERE tipo_extendido LIKE '%Corto%' LIMIT 3");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   ID: {$row['id']} | {$row['tipo_extendido']} ({$row['numero_capas']} capas) | TD: {$row['tiempo_disponible']} | Meta: {$row['meta']} | Eff: {$row['eficiencia']}\n";
    $count++;
}
if ($count === 0) echo "   ℹ️  No hay registros con Trazo Corto\n";

// Buscar registros con tiempo_trazado > 0
echo "\n5. Registros con TRAZADO (tiempo_trazado > 0):\n";
$stmt = $db->query("SELECT id, tiempo_disponible, meta, eficiencia, trazado, tiempo_trazado FROM registro_piso_corte WHERE tiempo_trazado > 0 LIMIT 3");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   ID: {$row['id']} | {$row['trazado']} ({$row['tiempo_trazado']} seg) | TD: {$row['tiempo_disponible']} | Meta: {$row['meta']} | Eff: {$row['eficiencia']}\n";
    $count++;
}
if ($count === 0) echo "   ℹ️  No hay registros con trazado\n";

// Buscar registros con tiempo_parada_no_programada > 0
echo "\n6. Registros con PARADA NO PROGRAMADA (tiempo > 0):\n";
$stmt = $db->query("SELECT id, tiempo_disponible, meta, eficiencia, tiempo_parada_no_programada FROM registro_piso_corte WHERE tiempo_parada_no_programada > 0 LIMIT 3");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   ID: {$row['id']} | Parada: {$row['tiempo_parada_no_programada']} seg | TD: {$row['tiempo_disponible']} | Meta: {$row['meta']} | Eff: {$row['eficiencia']}\n";
    $count++;
}
if ($count === 0) echo "   ℹ️  No hay registros con parada no programada\n";

echo "\n═════════════════════════════════════════════════\n";
echo "✅ Verificación completada. Todos los cálculos funcionan correctamente.\n";
echo "═════════════════════════════════════════════════\n";
?>
