<?php

namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Servicio para procesar y guardar imágenes de cotizaciones
 * 
 * Responsabilidades:
 * - Convertir imágenes a WebP
 * - Guardar en storage/cotizaciones/{cotizacion_id}/{tipo}/
 * - Retornar rutas para guardar en BD
 */
class ProcesarImagenesCotizacionService
{
    private const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];
    private const TAMAÑO_MAXIMO = 5 * 1024 * 1024; // 5MB
    private const CALIDAD_WEBP = 80;

    /**
     * Procesar y guardar imagen de prenda
     */
    public function procesarImagenPrenda(UploadedFile $archivo, int $cotizacionId, int $prendaId): string
    {
        return $this->procesarImagen($archivo, $cotizacionId, 'prendas', $prendaId);
    }

    /**
     * Procesar y guardar imagen de tela
     */
    public function procesarImagenTela(UploadedFile $archivo, int $cotizacionId, int $prendaId): string
    {
        return $this->procesarImagen($archivo, $cotizacionId, 'telas', $prendaId);
    }

    /**
     * Procesar y guardar imagen de logo
     */
    public function procesarImagenLogo(UploadedFile $archivo, int $cotizacionId): string
    {
        return $this->procesarImagen($archivo, $cotizacionId, 'logo');
    }

    /**
     * Procesar imagen genérica
     */
    private function procesarImagen(
        UploadedFile $archivo,
        int $cotizacionId,
        string $tipo,
        ?int $prendaId = null
    ): string {
        try {
            // Validar archivo
            $this->validarArchivo($archivo);

            // Crear estructura de carpetas
            $rutaCarpeta = $this->crearCarpeta($cotizacionId, $tipo);

            // Generar nombre único
            $nombreOriginal = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
            $nombreArchivo = $this->generarNombreUnico($nombreOriginal, $prendaId);

            // Procesar imagen a WebP
            $rutaWebP = $this->convertirAWebP($archivo, $rutaCarpeta, $nombreArchivo);

            Log::info('Imagen procesada correctamente', [
                'cotizacion_id' => $cotizacionId,
                'tipo' => $tipo,
                'prenda_id' => $prendaId,
                'ruta' => $rutaWebP,
            ]);

            return $rutaWebP;
        } catch (\Exception $e) {
            Log::error('Error procesando imagen', [
                'cotizacion_id' => $cotizacionId,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validar archivo
     */
    private function validarArchivo(UploadedFile $archivo): void
    {
        if (!in_array($archivo->getMimeType(), self::TIPOS_PERMITIDOS)) {
            throw new \InvalidArgumentException('Tipo de archivo no permitido. Use JPEG, PNG o WebP');
        }

        if ($archivo->getSize() > self::TAMAÑO_MAXIMO) {
            throw new \InvalidArgumentException('El archivo excede el tamaño máximo de 5MB');
        }
    }

    /**
     * Crear estructura de carpetas
     */
    private function crearCarpeta(int $cotizacionId, string $tipo): string
    {
        $ruta = "cotizaciones/{$cotizacionId}/{$tipo}";

        if (!Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->makeDirectory($ruta, 0755, true);
        }

        return $ruta;
    }

    /**
     * Generar nombre único para archivo
     */
    private function generarNombreUnico(string $nombreOriginal, ?int $prendaId = null): string
    {
        $timestamp = time();
        $random = substr(uniqid(), -4);

        if ($prendaId) {
            return "{$nombreOriginal}_prenda_{$prendaId}_{$timestamp}_{$random}";
        }

        return "{$nombreOriginal}_{$timestamp}_{$random}";
    }

    /**
     * Convertir imagen a WebP y guardar
     */
    private function convertirAWebP(UploadedFile $archivo, string $rutaCarpeta, string $nombreArchivo): string
    {
        // Crear instancia de ImageManager para Intervention Image v3
        $manager = new ImageManager(new Driver());
        
        // Leer imagen original
        $imagen = $manager->read($archivo->getRealPath());

        // Redimensionar si es muy grande (máximo 2000px)
        if ($imagen->width() > 2000 || $imagen->height() > 2000) {
            $imagen->scale(width: 2000, height: 2000);
        }

        // Convertir a WebP
        $nombreWebP = "{$nombreArchivo}.webp";
        $rutaCompleta = "{$rutaCarpeta}/{$nombreWebP}";

        // Guardar en storage/app/public
        $contenidoWebP = $imagen->toWebp(self::CALIDAD_WEBP);
        Storage::disk('public')->put($rutaCompleta, $contenidoWebP);
        
        // También guardar en public/storage para acceso directo
        $rutaPublica = public_path("storage/{$rutaCompleta}");
        $directorioPublico = dirname($rutaPublica);
        
        if (!is_dir($directorioPublico)) {
            @mkdir($directorioPublico, 0755, true);
        }
        
        @file_put_contents($rutaPublica, $contenidoWebP);

        // Retornar ruta relativa para que Laravel la resuelva correctamente
        return "/storage/{$rutaCompleta}";
    }

    /**
     * Procesar múltiples imágenes de prenda
     */
    public function procesarImagenesPrenda(
        array $archivos,
        int $cotizacionId,
        int $prendaId
    ): array {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if ($archivo instanceof UploadedFile) {
                $rutas[] = $this->procesarImagenPrenda($archivo, $cotizacionId, $prendaId);
            }
        }

        return $rutas;
    }

    /**
     * Procesar múltiples imágenes de tela
     */
    public function procesarImagenesTela(
        array $archivos,
        int $cotizacionId,
        int $prendaId
    ): array {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if ($archivo instanceof UploadedFile) {
                $rutas[] = $this->procesarImagenTela($archivo, $cotizacionId, $prendaId);
            }
        }

        return $rutas;
    }

    /**
     * Procesar múltiples imágenes de logo
     */
    public function procesarImagenesLogo(
        array $archivos,
        int $cotizacionId
    ): array {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if ($archivo instanceof UploadedFile) {
                $rutas[] = $this->procesarImagenLogo($archivo, $cotizacionId);
            }
        }

        return $rutas;
    }

    /**
     * Eliminar carpeta de cotización
     */
    public function eliminarCarpetaCotizacion(int $cotizacionId): bool
    {
        $ruta = "cotizaciones/{$cotizacionId}";

        if (Storage::disk('public')->exists($ruta)) {
            return Storage::disk('public')->deleteDirectory($ruta);
        }

        return true;
    }
}