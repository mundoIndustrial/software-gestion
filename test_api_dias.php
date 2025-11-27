<?php
/**
 * Script para probar el endpoint API de cálculo de días
 */

// Simular request al API
$ch = curl_init();

// Obtener un número de pedido de la base de datos primero
$pdo = new PDO('mysql:host=192.168.0.248;dbname=mundoindustrial', 'root', '');
$stmt = $pdo->query("SELECT numero_pedido FROM pedidos_produccion LIMIT 1");
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo "❌ No hay pedidos en la base de datos\n";
    exit(1);
}

$numeroPedido = $pedido['numero_pedido'];
echo "📋 Probando con pedido: $numeroPedido\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Test 1: Endpoint individual
echo "TEST 1: GET /api/registros/{numero_pedido}/dias\n";
echo "───────────────────────────────────────────────────────────\n";

$url = "http://localhost:8000/api/registros/$numeroPedido/dias";
echo "URL: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($data && isset($data['total_dias'])) {
    echo "✅ Total de días: " . $data['total_dias'] . "\n\n";
} else {
    echo "❌ Error en respuesta\n\n";
}

// Test 2: Endpoint batch
echo "TEST 2: POST /api/registros/dias-batch\n";
echo "───────────────────────────────────────────────────────────\n";

$url = "http://localhost:8000/api/registros/dias-batch";
echo "URL: $url\n";
echo "Body: " . json_encode(['numero_pedidos' => [$numeroPedido]]) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['numero_pedidos' => [$numeroPedido]]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($data && isset($data['dias'][$numeroPedido])) {
    echo "✅ Total de días: " . $data['dias'][$numeroPedido] . "\n";
} else {
    echo "❌ Error en respuesta\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
?>
