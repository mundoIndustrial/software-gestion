<?php

namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * ImagenPedidoService
 * 
 * Servicio CENTRALIZADO para almacenar imágenes organizadas por pedido
 * 
 * Garantiza que TODAS las imágenes se guarden en:
 * storage/app/public/pedido/{pedidoId}/{tipo}/{subtipo}/
 * 
 * Soporta:
 * - Validación de UploadedFile
 * - Creación automática de directorios
 * - Manejo de excepciones
 * - Logging detallado
 */
class ImagenPedidoService
{
    private const DISK = 'public';
    private const BASE_PATH = 'pedido';
    
    /**
     * Guardar imagen en carpeta específica del pedido
     * 
     * @param UploadedFile $file Archivo subido
     * @param int $pedidoId ID del pedido
     * @param string $tipo Tipo: 'prendas', 'telas', 'procesos', 'epp'
     * @param string|null $subtipo Subtipo (opcional): para procesos es el nombre del proceso
     * @return string Ruta guardada relativa a storage/app/public
     * @throws \InvalidArgumentException Si el archivo no es válido
     * @throws \RuntimeException Si hay error al guardar
     */
    public function guardarImagen(
        UploadedFile $file,
        int $pedidoId,
        string $tipo,
        ?string $subtipo = null
    ): string {
        //  Validar que sea UploadedFile
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('Archivo debe ser instancia de UploadedFile');
        }
        
        //  Validar que el archivo sea válido
        if (!$file->isValid()) {
            $errorMsg = $file->getErrorMessage();
            Log::warning('[ImagenPedidoService] Archivo inválido', [
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'error' => $errorMsg,
            ]);
            throw new \RuntimeException('Archivo inválido: ' . $errorMsg);
        }
        
        //  Validar tipo
        $tiposValidos = ['prendas', 'telas', 'procesos', 'epp'];
        if (!in_array($tipo, $tiposValidos)) {
            throw new \InvalidArgumentException("Tipo inválido. Válidos: " . implode(', ', $tiposValidos));
        }
        
        //  Construir ruta
        $rutaBase = sprintf('%s/%d/%s', self::BASE_PATH, $pedidoId, $tipo);
        if ($subtipo) {
            // Sanitizar subtipo
            $subtipo = preg_replace('/[^a-zA-Z0-9_-]/', '', $subtipo);
            $rutaBase .= '/' . $subtipo;
        }
        
        //  Crear directorio si no existe
        try {
            if (!Storage::disk(self::DISK)->exists($rutaBase)) {
                Storage::disk(self::DISK)->makeDirectory($rutaBase, 0755, true);
                Log::debug('[ImagenPedidoService] Directorio creado', [
                    'ruta' => $rutaBase,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ImagenPedidoService] Error creando directorio', [
                'ruta' => $rutaBase,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('No se pudo crear directorio: ' . $e->getMessage());
        }
        
        //  Guardar archivo
        try {
            $ruta = $file->store($rutaBase, self::DISK);
            
            Log::info('[ImagenPedidoService] Imagen guardada exitosamente', [
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'subtipo' => $subtipo,
                'ruta' => $ruta,
                'tamaño' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);
            
            return $ruta;
        } catch (\Exception $e) {
            Log::error('[ImagenPedidoService] Error al guardar imagen', [
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'subtipo' => $subtipo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Error al guardar imagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Guardar múltiples imágenes en el mismo directorio
     * 
     * @param array $files Array de UploadedFile
     * @param int $pedidoId ID del pedido
     * @param string $tipo Tipo de imagen
     * @param string|null $subtipo Subtipo
     * @return array Rutas de archivos guardados
     */
    public function guardarMultiplesImagenes(
        array $files,
        int $pedidoId,
        string $tipo,
        ?string $subtipo = null
    ): array {
        $rutas = [];
        
        foreach ($files as $index => $file) {
            try {
                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    Log::warning('[ImagenPedidoService] Archivo skipped', [
                        'index' => $index,
                        'valid' => $file instanceof UploadedFile ? $file->isValid() : false,
                    ]);
                    continue;
                }
                
                $ruta = $this->guardarImagen($file, $pedidoId, $tipo, $subtipo);
                $rutas[] = $ruta;
            } catch (\Exception $e) {
                Log::error('[ImagenPedidoService] Error en lote', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // Continuar con siguiente archivo
                continue;
            }
        }
        
        Log::info('[ImagenPedidoService] Lote guardado', [
            'pedido_id' => $pedidoId,
            'total' => count($files),
            'exitosos' => count($rutas),
        ]);
        
        return $rutas;
    }
    
    /**
     * Obtener ruta pública de una imagen
     * 
     * @param string $ruta Ruta relativa (resultado de guardarImagen)
     * @return string URL pública
     */
    public function obtenerUrlPublica(string $ruta): string
    {
        return Storage::disk(self::DISK)->url($ruta);
    }
    
    /**
     * Eliminar imagen del pedido
     * 
     * @param string $ruta Ruta relativa
     * @return bool
     */
    public function eliminarImagen(string $ruta): bool
    {
        try {
            if (Storage::disk(self::DISK)->exists($ruta)) {
                Storage::disk(self::DISK)->delete($ruta);
                Log::info('[ImagenPedidoService] Imagen eliminada', ['ruta' => $ruta]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('[ImagenPedidoService] Error eliminando imagen', [
                'ruta' => $ruta,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Verificar si existe imagen
     * 
     * @param string $ruta Ruta relativa
     * @return bool
     */
    public function existeImagen(string $ruta): bool
    {
        try {
            return Storage::disk(self::DISK)->exists($ruta);
        } catch (\Exception $e) {
            Log::warning('[ImagenPedidoService] Error verificando imagen', [
                'ruta' => $ruta,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
