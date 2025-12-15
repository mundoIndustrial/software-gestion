<?php
// Script simple para verificar procesos sin cargar todo el framework

$env = parse_ini_file('.env');
$db_host = $env['DB_HOST'];
$db_name = $env['DB_DATABASE'];
$db_user = $env['DB_USERNAME'];
$db_pass = $env['DB_PASSWORD'];

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$numeroPedido = 45451;

echo "\n=== VERIFICANDO PEDIDO $numeroPedido ===\n";

// Procesos
$sql = "SELECT id, proceso, estado_proceso, fecha_inicio, fecha_fin FROM procesos_prenda WHERE numero_pedido = $numeroPedido";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "\n📋 PROCESOS ENCONTRADOS:\n";
    while($row = $result->fetch_assoc()) {
        echo "\n   ID: " . $row['id'];
        echo "\n   Proceso: " . $row['proceso'];
        echo "\n   Estado: " . $row['estado_proceso'];
        echo "\n   Inicio: " . $row['fecha_inicio'];
        echo "\n   Fin: " . ($row['fecha_fin'] ?? 'NULL');
        echo "\n";
    }
} else {
    echo "\n❌ NO hay procesos\n";
}

// Pedido en tabla_original_bodega
$sql2 = "SELECT id, estado, novedades FROM tabla_original_bodega WHERE pedido = $numeroPedido";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    echo "\n📦 BODEGA:\n";
    $row = $result2->fetch_assoc();
    echo "   Estado: " . $row['estado'] . "\n";
    echo "   Novedades: " . ($row['novedades'] ? substr($row['novedades'], 0, 50) . '...' : 'Ninguna') . "\n";
} else {
    echo "\n❌ No en bodega\n";
}

$conn->close();
?>
