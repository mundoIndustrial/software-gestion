<?php

/**
 * Script para descargar el certificado CA automáticamente
 * Ejecutar: php descargar-certificado.php
 */

echo "🔥 Descargando certificado CA...\n\n";

$certUrl = 'https://curl.se/ca/cacert.pem';
$certPath = __DIR__ . '/storage/cacert.pem';

// Crear directorio si no existe
if (!file_exists(dirname($certPath))) {
    mkdir(dirname($certPath), 0755, true);
}

// Descargar el certificado (sin verificación SSL para este caso específico)
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);

echo "📥 Descargando desde: $certUrl\n";

$certContent = file_get_contents($certUrl, false, $context);

if ($certContent === false) {
    echo "❌ ERROR: No se pudo descargar el certificado\n";
    exit(1);
}

// Guardar el certificado
file_put_contents($certPath, $certContent);

echo "✅ Certificado descargado en: $certPath\n\n";

// Mostrar instrucciones
echo "📝 SIGUIENTE PASO:\n\n";
echo "Edita tu php.ini y agrega:\n\n";
echo "curl.cainfo = \"" . str_replace('/', '\\', $certPath) . "\"\n";
echo "openssl.cafile=\"" . str_replace('/', '\\', $certPath) . "\"\n\n";

echo "Para encontrar tu php.ini, ejecuta:\n";
echo "php --ini\n\n";

echo "Después de editar php.ini, reinicia el servidor:\n";
echo "php artisan serve\n\n";

echo "✨ ¡Listo! El certificado está descargado.\n";
