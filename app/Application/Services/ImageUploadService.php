<?php

namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Servicio de aplicación para gestión de imágenes de pedidos
 * Siguiendo arquitectura DDD - Capa de Aplicación
 */
class ImageUploadService
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    private const MAX_FILE_SIZE = 10240; // 10MB en KB
    private const WEBP_QUALITY = 85;
    private const THUMBNAIL_SIZE = 300;

    /**
     * Procesar y guardar imagen (original + WebP + thumbnail)
     * 
     * @param UploadedFile $file
     * @param string $filename
     * @param string $folder
     * @return array
     */
    public function processAndSaveImage(UploadedFile $file, string $filename, string $folder): array
    {
        $basePath = "pedidos/{$folder}";
        
        // Guardar original
        $originalPath = $file->storeAs($basePath . '/original', $filename . '.' . $file->getClientOriginalExtension(), 'public');

        // Crear ImageManager con driver GD (Intervention Image v3)
        $manager = new ImageManager(new Driver());

        // Crear versión WebP optimizada
        $image = $manager->read($file->getRealPath());
        $webpFilename = $filename . '.webp';
        $webpPath = $basePath . '/webp/' . $webpFilename;
        
        // Optimizar y guardar WebP
        $encoded = $image->toWebp(self::WEBP_QUALITY);
        Storage::disk('public')->put($webpPath, (string) $encoded);

        // Crear thumbnail
        $thumbnail = $manager->read($file->getRealPath());
        $thumbnail->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
        $thumbnailPath = $basePath . '/thumbnails/' . $webpFilename;
        $encodedThumb = $thumbnail->toWebp(self::WEBP_QUALITY);
        Storage::disk('public')->put($thumbnailPath, (string) $encodedThumb);

        return [
            'original' => $originalPath,
            'webp' => $webpPath,
            'thumbnail' => $thumbnailPath
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
     * 
     * @param UploadedFile $file
     * @param int $prendaIndex
     * @param int|null $cotizacionId
     * @return array
     */
    public function uploadPrendaImage(UploadedFile $file, int $prendaIndex, ?int $cotizacionId = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('prenda', $prendaIndex);
        $paths = $this->processAndSaveImage($file, $filename, 'prendas');

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'prenda_index' => $prendaIndex,
            'filename' => $filename
        ];
    }

    /**
     * Procesar upload de imagen de tela
     * 
     * @param UploadedFile $file
     * @param int $prendaIndex
     * @param int $telaIndex
     * @param int|null $telaId
     * @return array
     */
    public function uploadTelaImage(UploadedFile $file, int $prendaIndex, int $telaIndex, ?int $telaId = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('tela', $prendaIndex, $telaIndex);
        $paths = $this->processAndSaveImage($file, $filename, 'telas');

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'prenda_index' => $prendaIndex,
            'tela_index' => $telaIndex,
            'tela_id' => $telaId,
            'filename' => $filename
        ];
    }

    /**
     * Procesar upload de imagen de logo
     * 
     * @param UploadedFile $file
     * @param int|null $logoCotizacionId
     * @return array
     */
    public function uploadLogoImage(UploadedFile $file, ?int $logoCotizacionId = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('logo');
        $paths = $this->processAndSaveImage($file, $filename, 'logos');

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'filename' => $filename
        ];
    }

    /**
     * Procesar upload de imagen de reflectivo
     * 
     * @param UploadedFile $file
     * @param int|null $reflectivoId
     * @return array
     */
    public function uploadReflectivoImage(UploadedFile $file, ?int $reflectivoId = null): array
    {
        $this->validateImage($file);
        
        $filename = $this->generateUniqueFilename('reflectivo');
        $paths = $this->processAndSaveImage($file, $filename, 'reflectivos');

        return [
            'url' => Storage::url($paths['webp']),
            'ruta_webp' => $paths['webp'],
            'ruta_original' => $paths['original'],
            'thumbnail' => Storage::url($paths['thumbnail']),
            'filename' => $filename
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
