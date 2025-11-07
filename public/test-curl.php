<?php
// Script de diagnóstico para verificar cURL y conexión a Google

echo "<h2>Diagnóstico de cURL y Conexión</h2>";

// 1. Verificar si cURL está habilitado
echo "<h3>1. cURL habilitado:</h3>";
if (function_exists('curl_version')) {
    $version = curl_version();
    echo "✓ Sí - Versión: " . $version['version'] . "<br>";
    echo "SSL Versión: " . $version['ssl_version'] . "<br>";
    echo "Protocolos: " . implode(', ', $version['protocols']) . "<br>";
} else {
    echo "✗ No - cURL no está instalado<br>";
}

// 2. Verificar OpenSSL
echo "<h3>2. OpenSSL:</h3>";
if (extension_loaded('openssl')) {
    echo "✓ OpenSSL está habilitado<br>";
} else {
    echo "✗ OpenSSL no está habilitado<br>";
}

// 3. Probar conexión a Google OAuth
echo "<h3>3. Prueba de conexión a Google OAuth:</h3>";
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_NOBODY, true); // Solo HEAD request

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "✗ Error: " . $error . "<br>";
} else {
    echo "✓ Conexión exitosa - HTTP Code: " . $httpCode . "<br>";
}

// 4. Probar conexión a Google Drive API
echo "<h3>4. Prueba de conexión a Google Drive API:</h3>";
$ch = curl_init('https://www.googleapis.com/drive/v3/about?fields=user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_NOBODY, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "✗ Error: " . $error . "<br>";
} else {
    echo "✓ Conexión exitosa - HTTP Code: " . $httpCode . "<br>";
}

// 5. Configuración PHP relevante
echo "<h3>5. Configuración PHP:</h3>";
echo "max_execution_time: " . ini_get('max_execution_time') . " segundos<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Sí' : 'No') . "<br>";

// 6. Verificar certificados CA
echo "<h3>6. Certificados CA:</h3>";
$cainfo = ini_get('curl.cainfo');
$capath = ini_get('openssl.cafile');
echo "curl.cainfo: " . ($cainfo ?: 'No configurado') . "<br>";
echo "openssl.cafile: " . ($capath ?: 'No configurado') . "<br>";

if (!$cainfo && !$capath) {
    echo "<strong style='color: red;'>⚠ ADVERTENCIA: No hay certificados CA configurados. Esto puede causar errores SSL.</strong><br>";
}
