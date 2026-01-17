#!/usr/bin/env php
<?php
/**
 * Script para probar API de búsqueda de EPP
 * Hace una llamada HTTP a /api/epp
 */

$termino = isset($argv[1]) ? $argv[1] : 'casco';

echo "🔍 Buscando EPP con término: '{$termino}'\n";
echo "═══════════════════════════════════════════════════════════\n";

// Hacer petición HTTP a la API local
$url = "http://localhost:8000/api/epp?q=" . urlencode($termino);

echo "📡 URL: {$url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Error HTTP {$httpCode}\n";
    echo "Respuesta:\n";
    echo $response;
    exit(1);
}

$data = json_decode($response, true);

if (!$data['success']) {
    echo "❌ Error en respuesta: {$data['message']}\n";
    exit(1);
}

echo "✅ Búsqueda exitosa\n";
echo "📊 Total encontrado: {$data['total']}\n";
echo "\n";

foreach ($data['data'] as $index => $epp) {
    echo "[$index] {$epp['nombre']}\n";
    echo "    • Código: {$epp['codigo']}\n";
    echo "    • Categoría: {$epp['categoria']}\n";
    echo "    • Descripción: {$epp['descripcion']}\n";
    echo "    • Imágenes: " . count($epp['imagenes'] ?? []) . "\n";
    if (!empty($epp['imagen_principal_url'])) {
        echo "    • URL Principal: {$epp['imagen_principal_url']}\n";
    }
    echo "\n";
}

echo "✅ Test completado\n";
