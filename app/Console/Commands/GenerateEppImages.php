<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Facades\Image;
use Storage;

class GenerateEppImages extends Command
{
    protected $signature = 'app:generate-epp-images';

    protected $description = 'Generar imágenes de prueba para EPP';

    public function handle()
    {
        $baseDir = 'epp/imagenes';
        $publicPath = storage_path('app/public/' . $baseDir);
        
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        $imageFiles = [
            'casco-amarillo-frontal.jpg',
            'casco-amarillo-lateral.jpg',
            'casco-blanco-frontal.jpg',
            'guantes-nitrilo-dorso.jpg',
            'guantes-nitrilo-palma.jpg',
            'guantes-cuero-frontal.jpg',
            'botas-negras-frontal.jpg',
            'botas-negras-lateral.jpg',
            'zapatos-seguridad-frontal.jpg',
            'chaleco-naranja-frontal.jpg',
            'overol-azul-completo.jpg',
            'gafas-panoramicas-frontal.jpg',
            'careta-transparente-frontal.jpg',
            'mascarilla-n95-frontal.jpg',
            'respirador-media-cara.jpg',
            'orejeras-frontal.jpg',
            'arnes-altura-completo.jpg',
        ];

        foreach ($imageFiles as $filename) {
            $filepath = $publicPath . '/' . $filename;
            
            if (!file_exists($filepath)) {
                try {
                    // Crear imagen dummy con Intervention
                    $img = Image::canvas(400, 300, '#cccccc');
                    
                    // Agregar texto al centro
                    $img->text($filename, 200, 150, function ($font) {
                        $font->size(16);
                        $font->color('#000000');
                        $font->align('center');
                        $font->valign('center');
                    });
                    
                    $img->save($filepath);
                    $this->info("✅ Creada: $filename");
                } catch (\Exception $e) {
                    $this->error("❌ Error creando $filename: " . $e->getMessage());
                }
            } else {
                $this->info("⏭️  Ya existe: $filename");
            }
        }

        $this->info('✅ Imágenes de EPP generadas correctamente');
    }
}

