<?php
/**
 * Script para renovar el Access Token de Google Drive manualmente
 * Ejecuta: php renovar-token-google.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===========================================\n";
echo "RENOVAR ACCESS TOKEN DE GOOGLE DRIVE\n";
echo "===========================================\n\n";

$refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

if (!$refreshToken) {
    echo "❌ ERROR: GOOGLE_DRIVE_REFRESH_TOKEN no está configurado en el .env\n";
    exit(1);
}

echo "Refresh Token encontrado: " . substr($refreshToken, 0, 20) . "...\n\n";
echo "Renovando token...\n";

// Hacer petición a Google para renovar el token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => '377832184815-ulbdp631n4irovrer0it0gk8rfsvetfj.apps.googleusercontent.com',
    'client_secret' => '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',
    'refresh_token' => $refreshToken,
    'grant_type' => 'refresh_token'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['access_token'])) {
        $newAccessToken = $data['access_token'];
        
        echo "✅ Token renovado exitosamente!\n\n";
        echo "Nuevo Access Token:\n";
        echo $newAccessToken . "\n\n";
        
        // Actualizar el .env
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        if (preg_match('/^GOOGLE_DRIVE_ACCESS_TOKEN=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^GOOGLE_DRIVE_ACCESS_TOKEN=.*/m',
                "GOOGLE_DRIVE_ACCESS_TOKEN={$newAccessToken}",
                $envContent
            );
            echo "Actualizando .env...\n";
        } else {
            $envContent .= "\nGOOGLE_DRIVE_ACCESS_TOKEN={$newAccessToken}";
            echo "Agregando al .env...\n";
        }
        
        file_put_contents($envPath, $envContent);
        
        echo "✅ .env actualizado\n\n";
        echo "Limpiando caché...\n";
        Artisan::call('config:clear');
        echo "✅ Caché limpiada\n\n";
        
        echo "===========================================\n";
        echo "✅ TOKEN RENOVADO Y GUARDADO\n";
        echo "===========================================\n";
        echo "\nAhora puedes usar el botón 'Subir a Google Drive'\n";
        echo "El token es válido por 1 hora.\n";
        
    } else {
        echo "❌ No se encontró access_token en la respuesta\n";
        echo "Respuesta: $response\n";
    }
} else {
    echo "❌ ERROR al renovar token\n";
    echo "Respuesta: $response\n\n";
    
    $errorData = json_decode($response, true);
    if (isset($errorData['error'])) {
        echo "Error: " . $errorData['error'] . "\n";
        if (isset($errorData['error_description'])) {
            echo "Descripción: " . $errorData['error_description'] . "\n";
        }
    }
    
    echo "\nPosibles soluciones:\n";
    echo "1. El refresh token podría haber expirado o sido revocado\n";
    echo "2. Necesitas generar un nuevo refresh token desde Google OAuth Playground\n";
}
