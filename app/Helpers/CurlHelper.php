<?php

namespace App\Helpers;

/**
 * Helper para configurar cURL globalmente
 * Deshabilita verificación SSL en desarrollo
 */
class CurlHelper
{
    private static $initialized = false;

    public static function disableSSLVerification()
    {
        if (self::$initialized) {
            return;
        }

        // Solo en desarrollo
        if (config('app.env') !== 'local') {
            return;
        }

        // Configurar opciones por defecto de cURL usando ini_set
        ini_set('curl.cainfo', '');
        ini_set('openssl.cafile', '');
        
        // Configurar variables de entorno para Google Cloud
        putenv('GOOGLE_APPLICATION_CREDENTIALS_VERIFY_SSL=false');
        putenv('GCLOUD_DISABLE_SSL_VERIFICATION=true');
        
        // Configurar stream context por defecto
        stream_context_set_default([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
            'http' => [
                'ignore_errors' => true,
            ],
        ]);

        self::$initialized = true;
    }
}
