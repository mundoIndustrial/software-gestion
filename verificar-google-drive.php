<?php
/**
 * Script para verificar la configuración de Google Drive
 * Ejecuta este archivo desde la línea de comandos: php verificar-google-drive.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===========================================\n";
echo "VERIFICACIÓN DE CREDENCIALES GOOGLE DRIVE\n";
echo "===========================================\n\n";

// Verificar cada credencial
$credentials = [
    'GOOGLE_DRIVE_CLIENT_ID' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'GOOGLE_DRIVE_CLIENT_SECRET' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'GOOGLE_DRIVE_REFRESH_TOKEN' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'GOOGLE_DRIVE_ACCESS_TOKEN' => env('GOOGLE_DRIVE_ACCESS_TOKEN'),
    'GOOGLE_DRIVE_FOLDER_ID' => env('GOOGLE_DRIVE_FOLDER_ID'),
];

$allConfigured = true;

foreach ($credentials as $key => $value) {
    $status = $value ? '✅ CONFIGURADO' : '❌ FALTA';
    $preview = $value ? substr($value, 0, 20) . '...' : 'NO CONFIGURADO';
    
    echo "$key: $status\n";
    if ($value) {
        echo "   Valor: $preview\n";
    }
    echo "\n";
    
    if (!$value) {
        $allConfigured = false;
    }
}

echo "===========================================\n";

if (!$allConfigured) {
    echo "⚠️  FALTAN CREDENCIALES\n\n";
    echo "Para configurar Google Drive:\n";
    echo "1. Abre el archivo .env en la raíz del proyecto\n";
    echo "2. Agrega o actualiza estas líneas al final:\n\n";
    echo "GOOGLE_DRIVE_CLIENT_ID=407408718192.apps.googleusercontent.com\n";
    echo "GOOGLE_DRIVE_CLIENT_SECRET=tu_client_secret_aqui\n";
    echo "GOOGLE_DRIVE_REFRESH_TOKEN=tu_refresh_token_aqui\n";
    echo "GOOGLE_DRIVE_ACCESS_TOKEN=tu_access_token_aqui\n";
    echo "GOOGLE_DRIVE_FOLDER_ID=106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6\n\n";
    echo "3. Reemplaza 'tu_client_secret_aqui', 'tu_refresh_token_aqui' y 'tu_access_token_aqui'\n";
    echo "   con los valores reales de tu cuenta de Google\n";
    echo "4. Guarda el archivo .env\n";
    echo "5. Ejecuta: php artisan config:clear\n\n";
} else {
    echo "✅ TODAS LAS CREDENCIALES ESTÁN CONFIGURADAS\n\n";
    echo "Intentando renovar el token...\n\n";
    
    // Intentar renovar el token
    $clientId = env('GOOGLE_DRIVE_CLIENT_ID');
    $clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
    $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
    
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código HTTP: $httpCode\n";
    echo "Respuesta: $response\n\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            echo "✅ TOKEN RENOVADO EXITOSAMENTE\n";
            echo "Nuevo Access Token: " . substr($data['access_token'], 0, 30) . "...\n\n";
            echo "Ahora debes actualizar el .env con este nuevo token:\n";
            echo "GOOGLE_DRIVE_ACCESS_TOKEN=" . $data['access_token'] . "\n\n";
            echo "O ejecuta el backup desde la aplicación y se actualizará automáticamente.\n";
        } else {
            echo "❌ No se pudo obtener el access token de la respuesta\n";
        }
    } else {
        echo "❌ ERROR AL RENOVAR TOKEN\n";
        $errorData = json_decode($response, true);
        if (isset($errorData['error_description'])) {
            echo "Descripción: " . $errorData['error_description'] . "\n";
        }
        echo "\nPosibles causas:\n";
        echo "- El CLIENT_SECRET es incorrecto\n";
        echo "- El REFRESH_TOKEN es incorrecto o ha sido revocado\n";
        echo "- Las credenciales no coinciden con el proyecto de Google Cloud\n";
    }
}

echo "\n===========================================\n";
