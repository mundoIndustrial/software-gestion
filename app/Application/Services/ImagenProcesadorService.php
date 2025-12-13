<?php

namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic as Image;
use Exception;

class ImagenProcesadorService
{
    private ?ImageManager $imageManager = null;
    private const RUTA_BASE = 'public/prendas';
    private const CALIDAD_WEBP = 80;
    private const ANCHO_MINIATURA = 200;
    private const ALTO_MINIATURA = 200;

    public function __construct()
    {
        // Inicializar lazy - se cargará cuando se necesite
    }
    
    private function getImageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = app(ImageManager::class);
        }
        return $this->imageManager;
    }

    /**
     * Procesar imagen de prenda
     */
    public function procesarImagen(UploadedFile $archivo, int $prendaId): string
    {
        try {
            $this->validarFormato($archivo);

            $contenidoWebP = $this->convertirAWebP($archivo);
            $nombreArchivo = $this->generarNombreArchivo('foto');
            $ruta = "{$this->getRutaPrenda($prendaId)}/fotos/{$nombreArchivo}";

            Storage::put($ruta, $contenidoWebP);

            return Storage::url($ruta);
        } catch (Exception $e) {
            \Log::error('Error procesando imagen de prenda', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Procesar imagen de tela
     */
    public function procesarImagenTela(UploadedFile $archivo, int $prendaId): string
    {
        try {
            $this->validarFormato($archivo);

            $contenidoWebP = $this->convertirAWebP($archivo);
            $nombreArchivo = $this->generarNombreArchivo('tela');
            $ruta = "{$this->getRutaPrenda($prendaId)}/telas/{$nombreArchivo}";

            Storage::put($ruta, $contenidoWebP);

            return Storage::url($ruta);
        } catch (Exception $e) {
            \Log::error('Error procesando imagen de tela', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validar formato de imagen
     */
    public function validarFormato(UploadedFile $archivo): bool
    {
        $extensionesValidas = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, $extensionesValidas)) {
            throw new Exception("Formato de imagen no válido. Permitidos: " . implode(', ', $extensionesValidas));
        }

        // Validar MIME type
        $mimeValidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($archivo->getMimeType(), $mimeValidos)) {
            throw new Exception("MIME type no válido");
        }

        // Validar tamaño (máximo 5MB)
        if ($archivo->getSize() > 5 * 1024 * 1024) {
            throw new Exception("Archivo demasiado grande. Máximo 5MB");
        }

        return true;
    }

    /**
     * Convertir imagen a WebP
     */
    public function convertirAWebP(UploadedFile $archivo): string
    {
        try {
            $imagen = $this->getImageManager()->read($archivo->getStream());

            // Redimensionar si es muy grande
            if ($imagen->width() > 2000 || $imagen->height() > 2000) {
                $imagen->scaleDown(width: 2000, height: 2000);
            }

            // Convertir a WebP
            $webp = $imagen->toWebp(quality: self::CALIDAD_WEBP);

            return $webp->toString();
        } catch (Exception $e) {
            \Log::error('Error convirtiendo imagen a WebP', [
                'archivo' => $archivo->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Error al procesar imagen: " . $e->getMessage());
        }
    }

    /**
     * Generar miniatura
     */
    public function generarMiniatura(string $rutaWebP, int $prendaId): string
    {
        try {
            $contenido = Storage::get($rutaWebP);
            $imagen = $this->getImageManager()->read($contenido);

            // Redimensionar a miniatura
            $imagen->cover(self::ANCHO_MINIATURA, self::ALTO_MINIATURA);
            $miniatura = $imagen->toWebp(quality: self::CALIDAD_WEBP);

            $nombreMiniatura = str_replace('.webp', '_thumb.webp', basename($rutaWebP));
            $rutaMiniatura = dirname($rutaWebP) . '/' . $nombreMiniatura;

            Storage::put($rutaMiniatura, $miniatura->toString());

            return Storage::url($rutaMiniatura);
        } catch (Exception $e) {
            \Log::error('Error generando miniatura', [
                'ruta' => $rutaWebP,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción, solo retornar la ruta original
            return Storage::url($rutaWebP);
        }
    }

    /**
     * Eliminar imágenes de prenda
     */
    public function eliminarImagenesPrenda(int $prendaId): void
    {
        try {
            $ruta = $this->getRutaPrenda($prendaId);
            if (Storage::exists($ruta)) {
                Storage::deleteDirectory($ruta);
            }
        } catch (Exception $e) {
            \Log::warning('Error eliminando imágenes de prenda', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener ruta base de prenda
     */
    private function getRutaPrenda(int $prendaId): string
    {
        return self::RUTA_BASE . "/{$prendaId}";
    }

    /**
     * Generar nombre único para archivo
     */
    private function generarNombreArchivo(string $prefijo): string
    {
        return "{$prefijo}_" . uniqid() . '_' . time() . '.webp';
    }

    /**
     * Obtener información de imagen
     */
    public function obtenerInfo(string $ruta): array
    {
        try {
            $contenido = Storage::get($ruta);
            $imagen = $this->getImageManager()->read($contenido);

            return [
                'ancho' => $imagen->width(),
                'alto' => $imagen->height(),
                'tamaño' => strlen($contenido),
                'formato' => 'webp',
            ];
        } catch (Exception $e) {
            \Log::error('Error obteniendo información de imagen', [
                'ruta' => $ruta,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
