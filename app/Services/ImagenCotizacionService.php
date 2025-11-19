<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImagenCotizacionService
{
    /**
     * Ruta base para cotizaciones
     */
    private const BASE_PATH = 'cotizaciones';

    /**
     * Tipos de imágenes permitidas
     */
    private const TIPOS_PERMITIDOS = ['bordado', 'estampado', 'tela', 'prenda', 'general'];

    /**
     * Guardar imagen de cotización
     * 
     * @param int $cotizacionId
     * @param UploadedFile $archivo
     * @param string $tipo (bordado, estampado, tela, prenda, general)
     * @return string|null Ruta pública de la imagen
     */
    public function guardarImagen(int $cotizacionId, UploadedFile $archivo, string $tipo = 'general'): ?string
    {
        try {
            \Log::info('guardarImagen iniciado', [
                'cotizacion_id' => $cotizacionId,
                'archivo' => $archivo->getClientOriginalName(),
                'tipo_solicitado' => $tipo
            ]);

            // Validar tipo
            if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
                \Log::warning('Tipo no permitido, usando general', ['tipo' => $tipo]);
                $tipo = 'general';
            }

            // Crear carpeta si no existe
            $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
            \Log::info('Carpeta destino', ['carpeta' => $carpeta]);

            if (!Storage::disk('public')->exists($carpeta)) {
                \Log::info('Creando carpeta', ['carpeta' => $carpeta]);
                Storage::disk('public')->makeDirectory($carpeta, 0755, true);
            }

            // Generar nombre único
            $nombreArchivo = $this->generarNombreArchivo($cotizacionId, $tipo, $archivo);
            \Log::info('Nombre archivo generado', ['nombre' => $nombreArchivo]);

            // Guardar archivo
            \Log::info('Guardando archivo en storage', [
                'carpeta' => $carpeta,
                'nombre' => $nombreArchivo
            ]);

            $ruta = Storage::disk('public')->putFileAs(
                $carpeta,
                $archivo,
                $nombreArchivo
            );

            \Log::info('Archivo guardado en storage', ['ruta' => $ruta]);

            // Retornar ruta relativa (sin URL base)
            // Formato: /storage/cotizaciones/37/prenda/37_prenda_20251119174859_197.jpg
            $rutaRelativa = '/storage/' . $ruta;
            \Log::info('Ruta relativa generada', ['ruta_relativa' => $rutaRelativa]);

            return $rutaRelativa;
        } catch (\Exception $e) {
            \Log::error('Error al guardar imagen de cotización', [
                'cotizacion_id' => $cotizacionId,
                'tipo' => $tipo,
                'archivo' => $archivo->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Guardar múltiples imágenes
     * 
     * @param int $cotizacionId
     * @param array $archivos Array de UploadedFile
     * @param string $tipo
     * @return array Array de rutas públicas
     */
    public function guardarMultiples(int $cotizacionId, array $archivos, string $tipo = 'general'): array
    {
        \Log::info('guardarMultiples iniciado', [
            'cotizacion_id' => $cotizacionId,
            'cantidad_archivos' => count($archivos),
            'tipo' => $tipo
        ]);

        $rutas = [];

        foreach ($archivos as $index => $archivo) {
            \Log::info('Procesando archivo', [
                'index' => $index,
                'es_uploaded_file' => $archivo instanceof UploadedFile,
                'nombre' => $archivo->getClientOriginalName() ?? 'N/A'
            ]);

            if ($archivo instanceof UploadedFile) {
                $ruta = $this->guardarImagen($cotizacionId, $archivo, $tipo);
                \Log::info('Imagen guardada', [
                    'ruta' => $ruta,
                    'guardada' => $ruta !== null
                ]);

                if ($ruta) {
                    $rutas[] = $ruta;
                }
            }
        }

        \Log::info('guardarMultiples finalizado', [
            'rutas_retornadas' => count($rutas),
            'rutas' => $rutas
        ]);

        return $rutas;
    }

    /**
     * Obtener todas las imágenes de una cotización
     * 
     * @param int $cotizacionId
     * @return array Array de rutas organizadas por tipo
     */
    public function obtenerImagenes(int $cotizacionId): array
    {
        $imagenes = [];

        foreach (self::TIPOS_PERMITIDOS as $tipo) {
            $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
            
            if (Storage::disk('public')->exists($carpeta)) {
                $archivos = Storage::disk('public')->files($carpeta);
                $imagenes[$tipo] = array_map(function ($archivo) {
                    return Storage::disk('public')->url($archivo);
                }, $archivos);
            } else {
                $imagenes[$tipo] = [];
            }
        }

        return $imagenes;
    }

    /**
     * Obtener imágenes de un tipo específico
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @return array
     */
    public function obtenerImagenesPorTipo(int $cotizacionId, string $tipo): array
    {
        if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
            return [];
        }

        $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
        
        if (!Storage::disk('public')->exists($carpeta)) {
            return [];
        }

        $archivos = Storage::disk('public')->files($carpeta);
        return array_map(function ($archivo) {
            return Storage::disk('public')->url($archivo);
        }, $archivos);
    }

    /**
     * Eliminar imagen específica
     * 
     * @param string $rutaPublica Ruta pública de la imagen
     * @return bool
     */
    public function eliminarImagen(string $rutaPublica): bool
    {
        try {
            // Convertir URL pública a ruta de storage
            $ruta = str_replace('/storage/', '', $rutaPublica);
            
            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen', [
                'ruta' => $rutaPublica,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Eliminar todas las imágenes de una cotización
     * 
     * @param int $cotizacionId
     * @return bool
     */
    public function eliminarTodasLasImagenes(int $cotizacionId): bool
    {
        try {
            $carpetaPrincipal = self::BASE_PATH . '/' . $cotizacionId;
            
            if (Storage::disk('public')->exists($carpetaPrincipal)) {
                Storage::disk('public')->deleteDirectory($carpetaPrincipal);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Error al eliminar carpeta de cotización', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener ruta de carpeta para un tipo
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @return string
     */
    private function getCarpetaTipo(int $cotizacionId, string $tipo): string
    {
        return self::BASE_PATH . '/' . $cotizacionId . '/' . $tipo;
    }

    /**
     * Generar nombre único para archivo
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @param UploadedFile $archivo
     * @return string
     */
    private function generarNombreArchivo(int $cotizacionId, string $tipo, UploadedFile $archivo): string
    {
        $extension = $archivo->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return "{$cotizacionId}_{$tipo}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Validar archivo
     * 
     * @param UploadedFile $archivo
     * @return bool
     */
    public function validarArchivo(UploadedFile $archivo): bool
    {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($archivo->getClientOriginalExtension());
        $tamanioMaximo = 5 * 1024 * 1024; // 5MB

        if (!in_array($extension, $extensionesPermitidas)) {
            return false;
        }

        if ($archivo->getSize() > $tamanioMaximo) {
            return false;
        }

        return true;
    }

    /**
     * Obtener información de almacenamiento
     * 
     * @param int $cotizacionId
     * @return array
     */
    public function obtenerInfo(int $cotizacionId): array
    {
        $carpetaPrincipal = self::BASE_PATH . '/' . $cotizacionId;
        $imagenes = $this->obtenerImagenes($cotizacionId);
        
        $totalImagenes = 0;
        $tamanioTotal = 0;

        foreach ($imagenes as $tipo => $archivos) {
            $totalImagenes += count($archivos);
            
            foreach ($archivos as $url) {
                $ruta = str_replace('/storage/', '', $url);
                if (Storage::disk('public')->exists($ruta)) {
                    $tamanioTotal += Storage::disk('public')->size($ruta);
                }
            }
        }

        return [
            'cotizacion_id' => $cotizacionId,
            'total_imagenes' => $totalImagenes,
            'tamanio_total' => $tamanioTotal,
            'tamanio_total_mb' => round($tamanioTotal / 1024 / 1024, 2),
            'imagenes_por_tipo' => array_map('count', $imagenes),
            'existe_carpeta' => Storage::disk('public')->exists($carpetaPrincipal)
        ];
    }
}
