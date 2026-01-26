<?php

namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Servicio centralizado para gestión de imágenes de pedidos
 * 
 * FLUJO SIMPLIFICADO (sin temp):
 * 1. Frontend crea pedido → obtiene pedido_id
 * 2. Frontend sube imágenes → guardarImagenDirecta(file, pedido_id, tipo)
 * 3. Backend guarda directamente en: pedidos/{pedido_id}/{tipo}/
 * 
 * ❌ NO usa carpetas temporales
 * ❌ NO usa relocalización
 * ✅ TODO se guarda directamente con pedido_id
 */
class ImageUploadService
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    private const MAX_FILE_SIZE = 10240; // 10MB en KB
    private const WEBP_QUALITY = 85;
    private const THUMBNAIL_SIZE = 300;

    /**
     * MÉTODO PRINCIPAL: Guardar imagen directamente en pedidos/{pedido_id}/{tipo}/
     * 
     * ✅ Guarda en ubicación final sin pasos intermedios
     * ✅ Genera SOLO versión WebP optimizada (1 archivo)
     * ✅ Retorna ruta relativa para guardar en BD
     * 
     * @param UploadedFile $file Archivo subido
     * @param int $pedidoId ID del pedido (REQUERIDO)
     * @param string $tipo 'prendas', 'telas', 'procesos', etc.
     * @param string|null $subcarpeta Subcarpeta opcional (ej: 'ESTAMPADO' para procesos)
     * @param string|null $customFilename Nombre personalizado (opcional)
     * @return array ['webp' => string]
     * @throws \Exception
     */
    public function guardarImagenDirecta(
        UploadedFile $file,
        int $pedidoId,
        string $tipo,
        ?string $subcarpeta = null,
        ?string $customFilename = null
    ): array {
        // Validar imagen
        $this->validateImage($file);

        // Generar nombre único si no se proporciona
        $filename = $customFilename ?? $this->generateUniqueFilename($tipo);
        
        // Construir ruta base: pedido/{pedido_id}/{tipo_singular}/
        // Convertir plural a singular si es necesario
        $tipoSingular = rtrim($tipo, 's');  // epps → epp, prendas → prenda
        $basePath = "pedido/{$pedidoId}/{$tipoSingular}";
        if ($subcarpeta) {
            $basePath .= "/{$subcarpeta}";
        }

        // Crear ImageManager con driver GD (Intervention Image v3)
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        // Guardar SOLO versión WebP optimizada
        $webpFilename = "{$filename}.webp";
        $webpPath = "{$basePath}/{$webpFilename}";
        $encoded = $image->toWebp(self::WEBP_QUALITY);
        Storage::disk('public')->put($webpPath, (string) $encoded);

        Log::info('[ImageUploadService] Imagen guardada directamente', [
            'pedido_id' => $pedidoId,
            'tipo' => $tipo,
            'subcarpeta' => $subcarpeta,
            'ruta_webp' => $webpPath,
        ]);

        return [
            'webp' => $webpPath,
        ];
    }

    /**
     * @deprecated Usar guardarImagenDirecta() en su lugar
     */
    public function processAndSaveImage(UploadedFile $file, string $filename, string $folder, ?string $tempUuid = null): array
    {
        Log::warning('[ImageUploadService] Método processAndSaveImage() está deprecado. Usar guardarImagenDirecta()');
        
        // Mantener por compatibilidad pero recomendar migración
        if (!$tempUuid) {
            $tempUuid = Str::uuid()->toString();
        }

        $basePath = "temp/{$tempUuid}/{$folder}";
        
        $originalPath = $file->storeAs($basePath . '/original', $filename . '.' . $file->getClientOriginalExtension(), 'public');

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $webpFilename = $filename . '.webp';
        $webpPath = $basePath . '/webp/' . $webpFilename;
        
        $encoded = $image->toWebp(self::WEBP_QUALITY);
        Storage::disk('public')->put($webpPath, (string) $encoded);

        $thumbnail = $manager->read($file->getRealPath());
        $thumbnail->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
        $thumbnailPath = $basePath . '/thumbnails/' . $webpFilename;
        $encodedThumb = $thumbnail->toWebp(self::WEBP_QUALITY);
        Storage::disk('public')->put($thumbnailPath, (string) $encodedThumb);

        return [
            'original' => $originalPath,
            'webp' => $webpPath,
            'thumbnail' => $thumbnailPath,
            'temp_uuid' => $tempUuid,
        ];
    }

    /**
     * Generar nombre único para archivo
     * 
     * @param string $type
     * @param mixed ...$indices
     * @return string
     */
    public function generateUniqueFilename(string $type, ...$indices): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        $indicesStr = !empty($indices) ? '_' . implode('_', $indices) : '';
        
        return "{$type}{$indicesStr}_{$timestamp}_{$random}";
    }

    /**
     * Validar archivo de imagen
     * 
     * @param UploadedFile $file
     * @return bool
     * @throws \Exception
     */
    public function validateImage(UploadedFile $file): bool
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_TYPES)) {
            throw new \Exception('Tipo de archivo no permitido. Use JPG, PNG o WebP');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            throw new \Exception('El archivo es demasiado grande. Máximo 10MB');
        }

        return true;
    }

    /**
     * Eliminar imagen del storage
     * 
     * @param string|null $rutaWebp
     * @param string|null $rutaOriginal
     * @param string|null $thumbnail
     * @return array
     */
    public function deleteImage(?string $rutaWebp, ?string $rutaOriginal, ?string $thumbnail): array
    {
        $deleted = [];
        
        if ($rutaWebp && Storage::exists($rutaWebp)) {
            Storage::delete($rutaWebp);
            $deleted[] = 'webp';
        }

        if ($rutaOriginal && Storage::exists($rutaOriginal)) {
            Storage::delete($rutaOriginal);
            $deleted[] = 'original';
        }

        if ($thumbnail && Storage::exists($thumbnail)) {
            Storage::delete($thumbnail);
            $deleted[] = 'thumbnail';
        }

        return $deleted;
    }

    /**
     * Procesar upload de imagen de prenda
     * Guarda temporalmente en /prendas/temp/{uuid}/
     * 
     * @param UploadedFile $file
     * @param int $prendaIndex
     * @param int|null $cotizacionId (ignorado, guardado para compatibilidad)
     * @param string|null $tempUuid UUID para agrupar uploads
     * @return array
     */
    public function uploadPrendaImage(UploadedFile $file, int $prendaIndex, ?int $cotizacionId = null, ?string $tempUuid = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('prenda', $prendaIndex);
        $paths = $this->processAndSaveImage($file, $filename, 'prendas', $tempUuid);

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'prenda_index' => $prendaIndex,
            'filename' => $filename,
            'temp_uuid' => $paths['temp_uuid'],
        ];
    }

    /**
     * Procesar upload de imagen de tela
     * Guarda temporalmente en /telas/temp/{uuid}/
     * 
     * @param UploadedFile $file
     * @param int $prendaIndex
     * @param int $telaIndex
     * @param int|null $telaId (ignorado, guardado para compatibilidad)
     * @param string|null $tempUuid UUID para agrupar uploads
     * @return array
     */
    public function uploadTelaImage(UploadedFile $file, int $prendaIndex, int $telaIndex, ?int $telaId = null, ?string $tempUuid = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('tela', $prendaIndex, $telaIndex);
        $paths = $this->processAndSaveImage($file, $filename, 'telas', $tempUuid);

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'prenda_index' => $prendaIndex,
            'tela_index' => $telaIndex,
            'tela_id' => $telaId,
            'filename' => $filename,
            'temp_uuid' => $paths['temp_uuid'],
        ];
    }

    /**
     * Procesar upload de imagen de logo
     * Guarda temporalmente en /logos/temp/{uuid}/
     * 
     * @param UploadedFile $file
     * @param int|null $logoCotizacionId (ignorado, guardado para compatibilidad)
     * @param string|null $tempUuid UUID para agrupar uploads
     * @return array
     */
    public function uploadLogoImage(UploadedFile $file, ?int $logoCotizacionId = null, ?string $tempUuid = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('logo');
        $paths = $this->processAndSaveImage($file, $filename, 'logos', $tempUuid);

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'filename' => $filename,
            'temp_uuid' => $paths['temp_uuid'],
        ];
    }

    /**
     * Procesar upload de imagen de reflectivo
     * Guarda temporalmente en /reflectivos/temp/{uuid}/
     * 
     * @param UploadedFile $file
     * @param int|null $reflectivoId (ignorado, guardado para compatibilidad)
     * @param string|null $tempUuid UUID para agrupar uploads
     * @return array
     */
    public function uploadReflectivoImage(UploadedFile $file, ?int $reflectivoId = null, ?string $tempUuid = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('reflectivo');
        $paths = $this->processAndSaveImage($file, $filename, 'reflectivos', $tempUuid);

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'filename' => $filename,
            'temp_uuid' => $paths['temp_uuid'],
        ];
    }

    /**
     * Procesar upload múltiple de imágenes
     * 
     * @param array $files
     * @param string $tipo
     * @param array $options
     * @return array
     */
    public function uploadMultiple(array $files, string $tipo, array $options = []): array
    {
        $uploadedImages = [];
        $prendaIndex = $options['prenda_index'] ?? null;
        $telaIndex = $options['tela_index'] ?? null;

        foreach ($files as $index => $file) {
            $this->validateImage($file);
            
            $filename = $this->generateUniqueFilename($tipo, $prendaIndex, $telaIndex, $index);
            $paths = $this->processAndSaveImage($file, $filename, $tipo . 's');

            $uploadedImages[] = [
                'url' => Storage::url($paths['webp']),
                'ruta_webp' => $paths['webp'],
                'ruta_original' => $paths['original'],
                'thumbnail' => Storage::url($paths['thumbnail']),
                'filename' => $filename
            ];
        }

        return $uploadedImages;
    }
}
