<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LimpiarImagenesTemporales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'limpiar:temporales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina todas las imágenes temporales JPG (_temp_*.jpg) del storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de imágenes temporales...');
        
        $rutaStorage = storage_path('app/public/cotizaciones');
        
        if (!is_dir($rutaStorage)) {
            $this->error('❌ Directorio de cotizaciones no existe: ' . $rutaStorage);
            return 1;
        }
        
        // Buscar archivos temporales
        $archivos = $this->buscarArchivosRecursivo($rutaStorage, '_temp_', '.jpg');
        
        if (empty($archivos)) {
            $this->info('✅ No hay imágenes temporales para limpiar.');
            return 0;
        }
        
        $this->info('📁 Se encontraron ' . count($archivos) . ' archivo(s) temporal(es)');
        
        // Mostrar archivos a eliminar
        $this->line('');
        $this->info('Archivos a eliminar:');
        foreach ($archivos as $archivo) {
            $tamaño = filesize($archivo);
            $tamaño_kb = round($tamaño / 1024, 2);
            $this->line('  • ' . basename($archivo) . ' (' . $tamaño_kb . ' KB)');
        }
        $this->line('');
        
        // Confirmar eliminación
        if (!$this->confirm('¿Deseas eliminar estos archivos?')) {
            $this->info('❌ Operación cancelada.');
            return 0;
        }
        
        // Eliminar archivos
        $eliminados = 0;
        $errores = 0;
        
        foreach ($archivos as $archivo) {
            try {
                if (file_exists($archivo) && is_file($archivo)) {
                    @unlink($archivo);
                    $eliminados++;
                    $this->line('  ✓ Eliminado: ' . basename($archivo));
                }
            } catch (\Exception $e) {
                $errores++;
                $this->error('  ✗ Error al eliminar: ' . basename($archivo) . ' - ' . $e->getMessage());
            }
        }
        
        $this->line('');
        $this->info('✅ Limpieza completada:');
        $this->line('  • Eliminados: ' . $eliminados);
        $this->line('  • Errores: ' . $errores);
        
        return 0;
    }
    
    /**
     * Busca archivos recursivamente en un directorio
     */
    private function buscarArchivosRecursivo($directorio, $patron1, $patron2)
    {
        $archivos = [];
        
        try {
            if (!is_dir($directorio)) {
                return $archivos;
            }
            
            $items = @scandir($directorio);
            
            if ($items === false) {
                return $archivos;
            }
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $ruta = $directorio . DIRECTORY_SEPARATOR . $item;
                
                if (is_dir($ruta)) {
                    // Buscar recursivamente en subdirectorios
                    $archivos = array_merge($archivos, $this->buscarArchivosRecursivo($ruta, $patron1, $patron2));
                } elseif (is_file($ruta)) {
                    // Verificar si el archivo coincide con los patrones
                    if (strpos($item, $patron1) !== false && strpos($item, $patron2) !== false) {
                        $archivos[] = $ruta;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorar errores de lectura
        }
        
        return $archivos;
    }
}
