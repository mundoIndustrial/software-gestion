<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SystemClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:clear-all-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia el caché del servidor e invalida el caché del navegador para todos los usuarios (Cache Busting)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=========================================');
        $this->info('  INICIANDO LIMPIEZA TOTAL DE CACHÉ');
        $this->info('=========================================');

        // 1. Invalidar caché del navegador (Cache Busting)
        $this->updateAssetVersion();

        // 2. Limpiar caché de la aplicación
        $this->line('  [1/4] Limpiando caché de datos (Cache::clear)...');
        Artisan::call('cache:clear');
        $this->info('  ✓ Caché de datos limpiado');

        // 3. Limpiar y regenerar caché de configuración, rutas y vistas
        if (config('app.env') === 'production') {
            $this->line('  [2/4] Optimizando sistema para PRODUCCIÓN (config, rutas, vistas)...');
            Artisan::call('optimize');
            $this->info('  ✓ Configuración y rutas optimizadas');
        } else {
            $this->line('  [2/4] Limpiando caché (Modo Desarrollo)...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            $this->info('  ✓ Configuración y rutas limpiadas');
        }

        // 4. Limpiar caché de vistas específicamente (por si acaso)
        $this->line('  [3/4] Limpiando caché de vistas (Blade)...');
        Artisan::call('view:clear');
        $this->info('  ✓ Vistas Blade limpiadas');

        // 5. Limpiar caché de eventos (si existe)
        $this->line('  [4/4] Limpiando caché de eventos...');
        Artisan::call('event:clear');
        $this->info('  ✓ Eventos limpiados');

        $this->info('=========================================');
        $this->info('  ✓ SISTEMA ACTUALIZADO CORRECTAMENTE');
        $this->info('  Todos los usuarios verán los cambios.');
        $this->info('=========================================');
    }

    /**
     * Actualiza la versión de los assets en el archivo .env
     */
    private function updateAssetVersion()
    {
        $this->line('  [*] Actualizando ASSET_VERSION para forzar recarga en navegadores...');
        
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            $this->error('  ✗ No se encontró el archivo .env');
            return;
        }

        $newVersion = time(); // Usamos el timestamp actual como versión única
        $envContent = File::get($envPath);

        // Si ya existe la variable, la reemplazamos
        if (preg_match('/^ASSET_VERSION=.*/m', $envContent)) {
            $envContent = preg_replace('/^ASSET_VERSION=.*/m', "ASSET_VERSION={$newVersion}", $envContent);
        } else {
            // Si no existe, la añadimos al final
            $envContent .= "\nASSET_VERSION={$newVersion}";
        }

        File::put($envPath, $envContent);
        $this->info("  ✓ ASSET_VERSION actualizada a: {$newVersion}");
    }
}
