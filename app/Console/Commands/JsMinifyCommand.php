<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Minifica todos los archivos JS en public/js/ usando Terser.
 * 
 * Genera archivos .min.js al lado de cada .js original.
 * En producción, las Blade templates cargan .min.js automáticamente
 * via el helper js_asset().
 * 
 * Uso:
 *   php artisan js:minify              # Minifica todos los JS
 *   php artisan js:minify --clean      # Elimina todos los .min.js
 *   php artisan js:minify --force      # Re-minifica incluso si .min.js ya existe y está actualizado
 *   php artisan js:minify --report     # Solo muestra reporte de tamaños sin minificar
 */
class JsMinifyCommand extends Command
{
    protected $signature = 'js:minify 
                            {--clean : Eliminar todos los archivos .min.js}
                            {--force : Re-minificar incluso si el .min.js ya está actualizado}
                            {--report : Solo mostrar reporte de tamaños}
                            {--dry-run : Simular sin escribir archivos}';

    protected $description = 'Minifica archivos JS en public/js/ usando Terser (~60-70% reducción)';

    private int $totalOriginal = 0;
    private int $totalMinified = 0;
    private int $filesProcessed = 0;
    private int $filesSkipped = 0;
    private int $filesFailed = 0;

    public function handle(): int
    {
        $jsDir = public_path('js');

        if (!is_dir($jsDir)) {
            $this->error("No existe el directorio public/js/");
            return self::FAILURE;
        }

        // --clean: eliminar todos los .min.js
        if ($this->option('clean')) {
            return $this->cleanMinified($jsDir);
        }

        // Verificar que Terser está disponible
        if (!$this->option('report') && !$this->terserAvailable()) {
            $this->error("Terser no está disponible. Ejecuta: npm install terser");
            return self::FAILURE;
        }

        $this->info('');
        $this->info('  ⚡ JS Minification Pipeline');
        $this->info('  ══════════════════════════════════════');
        $this->info('');

        // Recopilar todos los archivos .js (no .min.js)
        $files = $this->collectJsFiles($jsDir);
        $total = count($files);

        if ($total === 0) {
            $this->warn("No se encontraron archivos JS para procesar.");
            return self::SUCCESS;
        }

        $this->info("  Encontrados: {$total} archivos JS");
        $this->info('');

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat('  [%bar%] %current%/%max% — %message%');
        $bar->setMessage('Iniciando...');
        $bar->start();

        foreach ($files as $file) {
            $relativePath = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $file);
            $bar->setMessage($relativePath);

            if ($this->option('report')) {
                $this->collectReportData($file);
            } else {
                $this->minifyFile($file);
            }

            $bar->advance();
        }

        $bar->setMessage('Completado');
        $bar->finish();
        $this->info('');
        $this->info('');

        $this->printReport();

        return self::SUCCESS;
    }

    /**
     * Recopilar todos los .js que no sean .min.js
     */
    private function collectJsFiles(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            // Solo .js, excluir .min.js
            if ($file->getExtension() === 'js' && !str_ends_with($path, '.min.js')) {
                $files[] = $path;
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Minificar un archivo individual
     */
    private function minifyFile(string $sourcePath): void
    {
        $minPath = $this->getMinPath($sourcePath);
        $originalSize = filesize($sourcePath);
        $this->totalOriginal += $originalSize;

        // Skip si ya existe y está actualizado (a menos que --force)
        if (!$this->option('force') && file_exists($minPath)) {
            if (filemtime($minPath) >= filemtime($sourcePath)) {
                $minSize = filesize($minPath);
                $this->totalMinified += $minSize;
                $this->filesSkipped++;
                return;
            }
        }

        if ($this->option('dry-run')) {
            $this->totalMinified += (int)($originalSize * 0.35); // estimación
            $this->filesProcessed++;
            return;
        }

        // Ejecutar Terser
        $npxPath = $this->getNpxPath();
        $process = new Process([
            $npxPath, 'terser', $sourcePath,
            '--compress', 'drop_console=false,passes=2',
            '--mangle',
            '--output', $minPath,
        ], base_path());

        $process->setTimeout(30);

        try {
            $process->run();

            if ($process->isSuccessful() && file_exists($minPath)) {
                $minSize = filesize($minPath);
                $this->totalMinified += $minSize;
                $this->filesProcessed++;
            } else {
                // Si Terser falla, copiar el original como fallback
                copy($sourcePath, $minPath);
                $this->totalMinified += $originalSize;
                $this->filesFailed++;
            }
        } catch (\Exception $e) {
            copy($sourcePath, $minPath);
            $this->totalMinified += $originalSize;
            $this->filesFailed++;
        }
    }

    /**
     * Solo recopilar datos para reporte
     */
    private function collectReportData(string $sourcePath): void
    {
        $minPath = $this->getMinPath($sourcePath);
        $originalSize = filesize($sourcePath);
        $this->totalOriginal += $originalSize;

        if (file_exists($minPath)) {
            $this->totalMinified += filesize($minPath);
            $this->filesProcessed++;
        } else {
            // Estimar reducción del 65%
            $this->totalMinified += (int)($originalSize * 0.35);
            $this->filesSkipped++;
        }
    }

    /**
     * Eliminar todos los .min.js
     */
    private function cleanMinified(string $dir): int
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $count = 0;
        $totalSize = 0;

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (str_ends_with($path, '.min.js')) {
                $totalSize += filesize($path);
                unlink($path);
                $count++;
            }
        }

        $this->info("  Eliminados: {$count} archivos .min.js (" . $this->formatSize($totalSize) . " liberados)");
        return self::SUCCESS;
    }

    /**
     * Imprimir reporte final
     */
    private function printReport(): void
    {
        $saved = $this->totalOriginal - $this->totalMinified;
        $percent = $this->totalOriginal > 0
            ? round(($saved / $this->totalOriginal) * 100, 1)
            : 0;

        $this->info('  ┌──────────────────────────────────────┐');
        $this->info('  │         Reporte de Minificación      │');
        $this->info('  ├──────────────────────────────────────┤');
        $this->info("  │  Original:    " . str_pad($this->formatSize($this->totalOriginal), 22) . " │");
        $this->info("  │  Minificado:  " . str_pad($this->formatSize($this->totalMinified), 22) . " │");
        $this->info("  │  Reducción:   " . str_pad($this->formatSize($saved) . " ({$percent}%)", 22) . " │");
        $this->info('  ├──────────────────────────────────────┤');
        $this->info("  │  Procesados:  " . str_pad((string)$this->filesProcessed, 22) . " │");

        if ($this->filesSkipped > 0) {
            $this->info("  │  Omitidos:    " . str_pad((string)$this->filesSkipped . ' (ya actualizados)', 22) . " │");
        }
        if ($this->filesFailed > 0) {
            $this->warn("  │  Fallidos:    " . str_pad((string)$this->filesFailed, 22) . " │");
        }

        $this->info('  └──────────────────────────────────────┘');
        $this->info('');
    }

    /**
     * Obtener la ruta .min.js para un archivo .js
     */
    private function getMinPath(string $sourcePath): string
    {
        return preg_replace('/\.js$/', '.min.js', $sourcePath);
    }

    /**
     * Formatear tamaño en bytes a formato legible
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Verificar que Terser está disponible
     */
    private function terserAvailable(): bool
    {
        $npxPath = $this->getNpxPath();
        $process = new Process([$npxPath, 'terser', '--version'], base_path());
        $process->setTimeout(10);
        
        try {
            $process->run();
            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener la ruta de npx según el OS
     */
    private function getNpxPath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'npx.cmd';
        }
        return 'npx';
    }
}
