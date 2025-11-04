<?php

/**
 * Deshabilitar verificación SSL para Firebase en desarrollo
 * 
 * IMPORTANTE: Solo para desarrollo local
 * En producción, configura correctamente los certificados SSL
 */

if (file_exists(__DIR__ . '/../.env')) {
    // Cargar variables de entorno
    $envContent = file_get_contents(__DIR__ . '/../.env');
    
    // Verificar si está en modo local y SSL deshabilitado
    $isLocal = strpos($envContent, 'APP_ENV=local') !== false;
    $sslDisabled = strpos($envContent, 'FIREBASE_VERIFY_SSL=false') !== false;
    
    if ($isLocal && $sslDisabled) {
        // Configurar variables de entorno para Guzzle
        putenv('GUZZLE_CURL_OPTIONS=' . json_encode([
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]));
        
        // Configurar stream context por defecto
        stream_context_set_default([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);
        
        // Definir constantes globales para cURL
        if (!defined('CURLOPT_SSL_VERIFYPEER_DEFAULT')) {
            define('CURLOPT_SSL_VERIFYPEER_DEFAULT', false);
            define('CURLOPT_SSL_VERIFYHOST_DEFAULT', false);
        }
    }
}
