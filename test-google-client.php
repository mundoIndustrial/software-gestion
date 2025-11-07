<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===========================================\n";
echo "TEST CON GOOGLE_CLIENT OFICIAL\n";
echo "===========================================\n\n";

$credentialsPath = resource_path('mundoindustrial-backups-d98b14a4bd34.json');

echo "1. Verificando archivo de credenciales...\n";
if (!file_exists($credentialsPath)) {
    echo "❌ ERROR: Archivo no encontrado: $credentialsPath\n";
    exit(1);
}
echo "✅ Archivo encontrado\n\n";

echo "2. Inicializando Google_Client...\n";
try {
    $client = new \Google_Client();
    $client->setAuthConfig($credentialsPath);
    $client->addScope(\Google_Service_Drive::DRIVE_FILE);
    echo "✅ Cliente configurado\n\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "3. Obteniendo access token...\n";
try {
    $accessToken = $client->fetchAccessTokenWithAssertion();
    
    if (isset($accessToken['access_token'])) {
        echo "✅ Access Token obtenido exitosamente!\n";
        echo "   Token: " . substr($accessToken['access_token'], 0, 30) . "...\n";
        echo "   Expires in: " . ($accessToken['expires_in'] ?? 'N/A') . " segundos\n\n";
        
        echo "===========================================\n";
        echo "✅ TODO FUNCIONA CORRECTAMENTE\n";
        echo "===========================================\n";
        echo "\n🎉 Ahora puedes usar el botón 'Subir a Google Drive'\n\n";
        echo "IMPORTANTE: Asegúrate de compartir la carpeta con:\n";
        echo "backup-service@mundoindustrial-backups.iam.gserviceaccount.com\n";
    } else {
        echo "❌ No se encontró access_token en la respuesta\n";
        echo "Respuesta: " . json_encode($accessToken) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
