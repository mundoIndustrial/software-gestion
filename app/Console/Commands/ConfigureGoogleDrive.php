<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConfigureGoogleDrive extends Command
{
    protected $signature = 'google-drive:configure {client_secret?}';
    protected $description = 'Configura las credenciales de Google Drive en el archivo .env';

    public function handle()
    {
        $this->info('===========================================');
        $this->info('CONFIGURACIÓN DE GOOGLE DRIVE');
        $this->info('===========================================');
        $this->newLine();

        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $this->error('No se encontró el archivo .env');
            return 1;
        }

        $envContent = file_get_contents($envPath);

        // Client ID (fijo)
        $clientId = '407408718192.apps.googleusercontent.com';
        
        // Client Secret (del argumento o preguntar)
        $clientSecret = $this->argument('client_secret');
        
        if (!$clientSecret) {
            $this->warn('Necesitas el CLIENT_SECRET de Google Cloud Console');
            $this->info('1. Ve a: https://console.cloud.google.com');
            $this->info('2. Navega a: APIs & Services > Credentials');
            $this->info('3. Busca el OAuth 2.0 Client ID: 407408718192');
            $this->info('4. Copia el Client Secret');
            $this->newLine();
            
            $clientSecret = $this->ask('Ingresa el CLIENT_SECRET');
            
            if (!$clientSecret) {
                $this->error('CLIENT_SECRET es requerido');
                return 1;
            }
        }

        // Actualizar o agregar CLIENT_ID
        if (preg_match('/^GOOGLE_DRIVE_CLIENT_ID=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^GOOGLE_DRIVE_CLIENT_ID=.*/m',
                "GOOGLE_DRIVE_CLIENT_ID={$clientId}",
                $envContent
            );
            $this->info('✅ CLIENT_ID actualizado');
        } else {
            $envContent .= "\nGOOGLE_DRIVE_CLIENT_ID={$clientId}";
            $this->info('✅ CLIENT_ID agregado');
        }

        // Actualizar o agregar CLIENT_SECRET
        if (preg_match('/^GOOGLE_DRIVE_CLIENT_SECRET=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^GOOGLE_DRIVE_CLIENT_SECRET=.*/m',
                "GOOGLE_DRIVE_CLIENT_SECRET={$clientSecret}",
                $envContent
            );
            $this->info('✅ CLIENT_SECRET actualizado');
        } else {
            $envContent .= "\nGOOGLE_DRIVE_CLIENT_SECRET={$clientSecret}";
            $this->info('✅ CLIENT_SECRET agregado');
        }

        // Guardar el archivo
        file_put_contents($envPath, $envContent);

        $this->newLine();
        $this->info('Limpiando caché de configuración...');
        $this->call('config:clear');

        $this->newLine();
        $this->info('===========================================');
        $this->info('✅ CONFIGURACIÓN COMPLETADA');
        $this->info('===========================================');
        $this->newLine();

        // Verificar que todo esté bien
        $this->info('Verificando credenciales...');
        $this->newLine();

        $hasClientId = env('GOOGLE_DRIVE_CLIENT_ID');
        $hasClientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
        $hasRefreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
        $hasAccessToken = env('GOOGLE_DRIVE_ACCESS_TOKEN');

        $this->table(
            ['Credencial', 'Estado'],
            [
                ['CLIENT_ID', $hasClientId ? '✅ Configurado' : '❌ Falta'],
                ['CLIENT_SECRET', $hasClientSecret ? '✅ Configurado' : '❌ Falta'],
                ['REFRESH_TOKEN', $hasRefreshToken ? '✅ Configurado' : '❌ Falta'],
                ['ACCESS_TOKEN', $hasAccessToken ? '✅ Configurado' : '❌ Falta'],
            ]
        );

        if ($hasClientId && $hasClientSecret && $hasRefreshToken) {
            $this->newLine();
            $this->info('🎉 Todas las credenciales necesarias están configuradas');
            $this->info('Ahora puedes crear backups en Google Drive desde la aplicación');
        } else {
            $this->newLine();
            $this->warn('⚠️  Aún faltan algunas credenciales');
            if (!$hasRefreshToken) {
                $this->error('Falta GOOGLE_DRIVE_REFRESH_TOKEN - Debes configurarlo manualmente en el .env');
            }
            if (!$hasAccessToken) {
                $this->warn('Falta GOOGLE_DRIVE_ACCESS_TOKEN - Se generará automáticamente al crear el primer backup');
            }
        }

        return 0;
    }
}
