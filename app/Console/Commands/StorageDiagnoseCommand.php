<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:diagnose', description: 'Diagnostica problemas de acceso a archivos (storage 403)')]
class StorageDiagnoseCommand extends Command
{
    protected $signature = 'storage:diagnose {--fix : Intentar reparar automáticamente}';
    protected $description = 'Diagnóstico completo de problemas de acceso a storage';

    public function handle(): int
    {
        $this->info(' DIAGNÓSTICO DE STORAGE - LARAVEL');
        $this->newLine();

        $fix = $this->option('fix');

        if ($fix) {
            $this->warn('  Modo REPARACIÓN activo');
        } else {
            $this->info('  Modo LECTURA (sin cambios)');
        }

        $this->newLine();

        // =====================================================================
        // 1. VERIFICAR ENLACE SIMBÓLICO
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('1️⃣  ENLACE SIMBÓLICO');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $publicStoragePath = public_path('storage');
        $targetPath = storage_path('app/public');

        if (is_link($publicStoragePath)) {
            $linkTarget = readlink($publicStoragePath);
            $this->components->twoColumnDetail(' Enlace simbólico', 'Existe');
            $this->components->twoColumnDetail('  Apunta a', $linkTarget);

            if (realpath($publicStoragePath) === realpath($targetPath)) {
                $this->components->twoColumnDetail('  Destino correcto', '');
            } else {
                $this->components->twoColumnDetail('  Destino correcto', ' INCORRECTO');
                if ($fix) {
                    $this->fixSymlink();
                }
            }
        } elseif (is_dir($publicStoragePath) && is_dir($publicStoragePath . '/..')) {
            // En Windows, a veces PHP no detecta bien el symlink, pero podemos verificar
            // si public/storage apunta a storage/app/public
            $realPath = realpath($publicStoragePath);
            $targetRealPath = realpath($targetPath);
            
            if ($realPath === $targetRealPath) {
                $this->components->twoColumnDetail(' Enlace simbólico', 'Existe (Windows)');
                $this->components->twoColumnDetail('  Apunta a', $realPath);
                $this->components->twoColumnDetail('  Destino correcto', '');
            } else {
                $this->components->twoColumnDetail(' Enlace simbólico', 'NO EXISTE o INCORRECTO');
                if ($fix) {
                    $this->fixSymlink();
                }
            }
        } else {
            $this->components->twoColumnDetail(' Enlace simbólico', 'NO EXISTE o INCORRECTO');
            if ($fix) {
                $this->fixSymlink();
            }
        }

        $this->newLine();

        // =====================================================================
        // 2. VERIFICAR DIRECTORIO storage/app/public
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('2️⃣  DIRECTORIOS');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $baseDir = base_path();
        $directories = [
            'storage' => storage_path(),
            'storage/app/public' => storage_path('app/public'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => $baseDir . '/bootstrap/cache',
        ];

        foreach ($directories as $label => $path) {
            if (is_dir($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $this->components->twoColumnDetail(" $label", "Existe ($perms)");
            } else {
                $this->components->twoColumnDetail(" $label", 'NO EXISTE');
            }
        }

        $this->newLine();

        // =====================================================================
        // 3. VERIFICAR PERMISOS
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('3️⃣  PERMISOS');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach ($directories as $label => $path) {
            if (is_dir($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $readable = is_readable($path) ? '' : '';
                $writable = is_writable($path) ? '' : '';

                $this->components->twoColumnDetail("$label", "R:$readable W:$writable (perms: $perms)");

                if (!is_writable($path) && $fix) {
                    $this->fixPermissions($path);
                }
            }
        }

        $this->newLine();

        // =====================================================================
        // 4. VERIFICAR CONFIGURACIÓN
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('4️⃣  CONFIGURACIÓN');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $diskConfig = config('filesystems.disks.public');

        $this->components->twoColumnDetail('Driver', $diskConfig['driver']);
        $this->components->twoColumnDetail('Root', $diskConfig['root']);
        $this->components->twoColumnDetail('URL', $diskConfig['url']);
        $this->components->twoColumnDetail('Visibility', $diskConfig['visibility'] ?? 'N/A');

        $this->newLine();

        // =====================================================================
        // 5. PROBAR FUNCIONES DE STORAGE
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('5️⃣  FUNCIONES DE STORAGE');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            // Probar URL
            $testUrl = Storage::disk('public')->url('test.jpg');
            $this->components->twoColumnDetail(' Storage::disk(public)->url()', $testUrl);
        } catch (\Exception $e) {
            $this->components->twoColumnDetail(' Storage::disk(public)->url()', $e->getMessage());
        }

        try {
            // Probar asset
            $assetUrl = asset('storage/test.jpg');
            $this->components->twoColumnDetail(' asset(storage/test.jpg)', $assetUrl);
        } catch (\Exception $e) {
            $this->components->twoColumnDetail(' asset(storage/test.jpg)', $e->getMessage());
        }

        $this->newLine();

        // =====================================================================
        // 6. ARCHIVOS ALMACENADOS
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('6️⃣  ARCHIVOS ALMACENADOS');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $publicStoragePath = storage_path('app/public');
        if (is_dir($publicStoragePath)) {
            $files = collect(File::allFiles($publicStoragePath));
            $dirs = collect(File::directories($publicStoragePath));

            $totalSize = $files->sum(fn($f) => $f->getSize());
            $totalSizeMB = round($totalSize / (1024 * 1024), 2);

            $this->components->twoColumnDetail('Total archivos', $files->count());
            $this->components->twoColumnDetail('Total carpetas', $dirs->count());
            $this->components->twoColumnDetail('tamano total', "{$totalSizeMB} MB");

            // Mostrar carpetas principales
            foreach ($dirs as $dir) {
                $dirName = basename($dir);
                $fileCount = count(File::allFiles($dir));
                $this->components->twoColumnDetail("  📁 $dirName", "$fileCount archivos");
            }
        }

        $this->newLine();

        // =====================================================================
        // 7. SERVIDOR WEB
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('7️⃣  SERVIDOR WEB');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->components->twoColumnDetail('APP_URL', config('app.url'));
        $this->components->twoColumnDetail('Server Software', $_SERVER['SERVER_SOFTWARE'] ?? 'N/A');

        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $rewriteEnabled = in_array('mod_rewrite', $modules);
            $this->components->twoColumnDetail('mod_rewrite', $rewriteEnabled ? '' : '');
        }

        $this->newLine();

        // =====================================================================
        // 8. RESUMEN Y ACCIONES
        // =====================================================================

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('8️⃣  RESUMEN');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->newLine();

        if ($fix) {
            $this->info(' Diagnóstico y reparación completados');
        } else {
            $this->info(' Diagnóstico completado');
            $this->line('💡 Para reparar automáticamente, ejecuta:');
            $this->line('   php artisan storage:diagnose --fix');
        }

        $this->newLine();

        $this->line(' PRÓXIMOS PASOS:');
        $this->line('  1. Visita: http://localhost:8000/storage');
        $this->line('  2. Prueba: http://localhost:8000/storage/test-file.txt');
        $this->line('  3. Si ves 403: Ejecuta con --fix o revisa permisos manualmente');

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return Command::SUCCESS;
    }

    protected function fixSymlink(): void
    {
        try {
            $publicPath = public_path('storage');
            $storagePath = storage_path('app/public');

            // Remover si existe
            if (is_link($publicPath) || is_dir($publicPath)) {
                @unlink($publicPath);
            }

            // Crear nuevo enlace
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows
                symlink($storagePath, $publicPath);
            } else {
                // Linux/Mac
                symlink('../storage/app/public', $publicPath);
            }

            $this->components->twoColumnDetail(' Enlace simbólico', 'REPARADO');
        } catch (\Exception $e) {
            $this->error("Error al reparar enlace: {$e->getMessage()}");
        }
    }

    protected function fixPermissions($path): void
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // En Windows, intentar cambiar atributos
                $this->warn("  En Windows no se pueden cambiar permisos automáticamente");
            } else {
                // Linux/Mac
                @chmod($path, 0755);
                $this->components->twoColumnDetail(" Permisos reparados", $path);
            }
        } catch (\Exception $e) {
            $this->error("Error al reparar permisos: {$e->getMessage()}");
        }
    }
}
