<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Intervention\Image\ImageManager;

/**
 * Servicio para procesar y guardar imágenes de técnicas en cotizaciones
 * 
 * Guarda en estructura:
 * /public/cotizaciones/{cotizacion_id}/logo/{tipo_logo}/{imagen}.webp
 */
class TecnicaImagenService
{
    private ImageManager $imageManager;
    
    public function __construct()
    {
        $this->imageManager = ImageManager::gd();
    }

    /**
     * Guardar una imagen de técnica
     * 
     * @param UploadedFile $file Archivo subido
     * @param int $cotizacionId ID de la cotización
     * @param string $tipoLogoNombre Nombre del tipo de logo (bordado, estampado, etc)
     * @param int|null $grupoCombinado Si es técnica combinada, el ID del grupo (no usado en nueva ruta)
     * @return array Ruta única [ruta_webp]
     */
    public function guardarImagen(UploadedFile $file, int $cotizacionId, string $tipoLogoNombre, ?int $grupoCombinado = null)
    {
        try {
            \Log::info('📸 Guardando imagen de técnica', [
                'cotizacion_id' => $cotizacionId,
                'tipo_logo' => $tipoLogoNombre,
                'grupo_combinado' => $grupoCombinado,
                'filename' => $file->getClientOriginalName(),
                'size_kb' => $file->getSize() / 1024
            ]);

            // Generar nombre único
            $nombreUnico = Str::uuid() . '.webp';
            
            // Nueva ruta simplificada: /cotizaciones/{cot_id}/logo/{tipo_logo}/
            $rutaBase = "cotizaciones/{$cotizacionId}/logo/{$tipoLogoNombre}";

            // Crear directorio si no existe
            if (!Storage::disk('public')->exists($rutaBase)) {
                Storage::disk('public')->makeDirectory($rutaBase, 0755, true);
            }

            // Leer contenido para procesamiento
            $contenido = file_get_contents($file->getRealPath());
            $image = $this->imageManager->read($contenido);

            $ancho = $image->width();
            $alto = $image->height();

            \Log::info('✓ Imagen cargada y procesada', [
                'ancho' => $ancho,
                'alto' => $alto,
                'tamaño_bytes' => filesize($file->getRealPath())
            ]);

            // Redimensionar si es muy grande (máximo 2000x2000)
            if ($ancho > 2000 || $alto > 2000) {
                $image->scaleDown(2000, 2000);
                \Log::info('✓ Imagen redimensionada', [
                    'nuevo_ancho' => $image->width(),
                    'nuevo_alto' => $image->height()
                ]);
            }

            // Guardar como WebP (optimizado) - ÚNICO ARCHIVO
            $rutaWebp = "{$rutaBase}/{$nombreUnico}";
            $contenidoWebP = $image->toWebp(85);
            Storage::disk('public')->put($rutaWebp, $contenidoWebP);

            $tamañoWebP = Storage::disk('public')->size($rutaWebp);

            \Log::info('✓ WebP guardado (imagen única)', [
                'ruta' => $rutaWebp,
                'tamaño_bytes' => $tamañoWebP,
                'ancho' => $image->width(),
                'alto' => $image->height()
            ]);

            // Retornar una única ruta
            return [
                'ruta_webp' => $rutaWebp,
                'ancho' => $image->width(),
                'alto' => $image->height(),
                'tamaño' => $tamañoWebP
            ];

        } catch (\Exception $e) {
            \Log::error(' Error al guardar imagen de técnica', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Guardar múltiples imágenes
     */
    public function guardarMultiples(array $archivos, int $cotizacionId, string $tipoLogoNombre, ?int $grupoCombinado = null): array
    {
        $rutas = [];
        
        foreach ($archivos as $index => $archivo) {
            try {
                $resultado = $this->guardarImagen($archivo, $cotizacionId, $tipoLogoNombre, $grupoCombinado);
                $resultado['orden'] = $index;
                $rutas[] = $resultado;
                \Log::info("✓ Imagen {$index} guardada");
            } catch (\Exception $e) {
                \Log::error(" Error en imagen {$index}", ['error' => $e->getMessage()]);
                // Continuar con la siguiente
            }
        }
        
        return $rutas;
    }
}
