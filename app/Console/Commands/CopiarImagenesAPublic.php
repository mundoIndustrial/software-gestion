<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CopiarImagenesAPublic extends Command
{
    protected $signature = 'imagenes:copiar-a-public';
    protected $description = 'Copia imágenes de storage/app/public a public/storage para acceso directo';

    public function handle()
    {
        $this->info('🔄 Copiando imágenes a public/storage...');

        $rutaStorage = storage_path('app/public/cotizaciones');
        $rutaPublic = public_path('storage/cotizaciones');

        if (!is_dir($rutaStorage)) {
            $this->error('❌ Directorio storage/app/public/cotizaciones no existe');
            return;
        }

        // Crear directorio destino si no existe
        if (!is_dir($rutaPublic)) {
            @mkdir($rutaPublic, 0755, true);
        }

        // Copiar recursivamente
        $this->copiarDirectorio($rutaStorage, $rutaPublic);

        $this->info('✅ Imágenes copiadas correctamente a public/storage');
        
        // Actualizar rutas en BD para que apunten a /storage/
        $this->actualizarRutasEnBD();
    }
    
    private function actualizarRutasEnBD()
    {
        $this->info('🔄 Actualizando rutas en BD...');
        
        // Actualizar prenda_fotos_cot
        $fotosActualizadas = \DB::table('prenda_fotos_cot')
            ->where('ruta_original', 'like', '%storage-serve%')
            ->orWhere('ruta_webp', 'like', '%storage-serve%')
            ->update([
                'ruta_original' => \DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
                'ruta_webp' => \DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
            ]);
        
        if ($fotosActualizadas > 0) {
            $this->info("✅ Fotos de prendas actualizadas: $fotosActualizadas");
        }
        
        // Actualizar prenda_tela_fotos_cot
        $telasActualizadas = \DB::table('prenda_tela_fotos_cot')
            ->where('ruta_original', 'like', '%storage-serve%')
            ->orWhere('ruta_webp', 'like', '%storage-serve%')
            ->update([
                'ruta_original' => \DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
                'ruta_webp' => \DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
            ]);
        
        if ($telasActualizadas > 0) {
            $this->info("✅ Fotos de telas actualizadas: $telasActualizadas");
        }
    }

    private function copiarDirectorio($origen, $destino)
    {
        $dir = opendir($origen);
        
        if (!is_dir($destino)) {
            @mkdir($destino, 0755, true);
        }

        while (($archivo = readdir($dir)) !== false) {
            if ($archivo == '.' || $archivo == '..') {
                continue;
            }

            $rutaOrigen = $origen . DIRECTORY_SEPARATOR . $archivo;
            $rutaDestino = $destino . DIRECTORY_SEPARATOR . $archivo;

            if (is_dir($rutaOrigen)) {
                $this->copiarDirectorio($rutaOrigen, $rutaDestino);
            } else {
                @copy($rutaOrigen, $rutaDestino);
                $this->line("  ✓ Copiado: $archivo");
            }
        }

        closedir($dir);
    }
}
