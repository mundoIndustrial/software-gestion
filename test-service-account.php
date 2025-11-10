<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===========================================\n";
echo "TEST DE SERVICE ACCOUNT - GOOGLE DRIVE\n";
echo "===========================================\n\n";

$credentialsPath = resource_path('mundoindustrial-backups-d98b14a4bd34.json');

echo "1. Verificando archivo de credenciales...\n";
if (!file_exists($credentialsPath)) {
    echo "❌ ERROR: Archivo no encontrado: $credentialsPath\n";
    exit(1);
}
echo "✅ Archivo encontrado\n\n";

echo "2. Leyendo credenciales...\n";
$credentials = json_decode(file_get_contents($credentialsPath), true);

if (!$credentials) {
    echo "❌ ERROR: No se pudo leer el archivo JSON\n";
    exit(1);
}
echo "✅ JSON válido\n";
echo "   Email: " . $credentials['client_email'] . "\n";
echo "   Project: " . $credentials['project_id'] . "\n\n";

echo "3. Generando JWT con firebase/php-jwt...\n";

$now = time();

$payload = [
    'iss' => $credentials['client_email'],
    'scope' => 'https://www.googleapis.com/auth/drive.file',
    'aud' => 'https://oauth2.googleapis.com/token',
    'exp' => $now + 3600,
    'iat' => $now
];

echo "   Payload: " . json_encode($payload) . "\n\n";

echo "4. Firmando JWT con kid...\n";

// Agregar kid (key ID) al header
$headers = [];
if (isset($credentials['private_key_id'])) {
    $headers['kid'] = $credentials['private_key_id'];
    echo "   Kid: " . $credentials['private_key_id'] . "\n";
}

try {
    $jwt = \Firebase\JWT\JWT::encode($payload, $credentials['private_key'], 'RS256', null, $headers);
    echo "✅ JWT generado y firmado correctamente\n";
} catch (\Exception $e) {
    echo "❌ ERROR al generar JWT: " . $e->getMessage() . "\n";
    exit(1);
}

echo "   JWT Length: " . strlen($jwt) . " caracteres\n";
echo "   JWT Preview: " . substr($jwt, 0, 50) . "...\n\n";

echo "5. Intercambiando JWT por Access Token...\n";

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";

if ($curlError) {
    echo "❌ CURL Error: $curlError\n";
    exit(1);
}

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $accessToken = $data['access_token'] ?? null;
    
    if ($accessToken) {
        echo "✅ Access Token obtenido exitosamente!\n";
        echo "   Token: " . substr($accessToken, 0, 30) . "...\n";
        echo "   Expires in: " . ($data['expires_in'] ?? 'N/A') . " segundos\n\n";
        
        echo "===========================================\n";
        echo "✅ TODO FUNCIONA CORRECTAMENTE\n";
        echo "===========================================\n";
        echo "\nAhora puedes usar el botón 'Subir a Google Drive'\n";
        echo "Solo asegúrate de compartir la carpeta con:\n";
        echo $credentials['client_email'] . "\n";
    } else {
        echo "❌ No se encontró access_token en la respuesta\n";
        echo "Respuesta: $response\n";
    }
} else {
    echo "❌ ERROR al obtener token\n";
    echo "Respuesta: $response\n\n";
    
    $errorData = json_decode($response, true);
    if (isset($errorData['error'])) {
        echo "Error: " . $errorData['error'] . "\n";
        if (isset($errorData['error_description'])) {
            echo "Descripción: " . $errorData['error_description'] . "\n";
        }
    }
}
