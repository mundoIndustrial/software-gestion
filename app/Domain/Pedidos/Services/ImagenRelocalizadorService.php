<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * ImagenRelocalizadorService
 * 
 * Responsabilidad: Mover imágenes de temp/{uuid}/ a pedidos/{pedido_id}/{tipo}/
 * Usado cuando se crea un pedido para consolidar las imágenes subidas
 */
class ImagenRelocalizadorService
{
    /**
     * Mover imágenes de temp a estructura pedidos/{pedido_id}/{tipo}/
     * 
     * @param int $pedidoId
     * @param array $rutasTemp Rutas en formato: ['prendas/temp/uuid/file.webp', 'telas/temp/uuid/file.webp']
     * @param string|null $tipoEspecifico Fuerza un tipo específico ('prendas', 'telas', 'procesos')
     * @return array Rutas relocalizadas en formato: ['pedidos/{pedido_id}/prendas/file.webp']
     */
    public function relocalizarImagenes(int $pedidoId, array $rutasTemp, ?string $tipoEspecifico = null): array
    {
        $rutasRelocalizadas = [];

        foreach ($rutasTemp as $rutaTemp) {
            if (empty($rutaTemp)) {
                continue;
            }

            try {
                $rutaFinal = $this->moverImagen($pedidoId, $rutaTemp, $tipoEspecifico);
                if ($rutaFinal) {
                    $rutasRelocalizadas[] = $rutaFinal;
                }
            } catch (\Exception $e) {
                Log::warning('[ImagenRelocalizadorService] Error moviendo imagen', [
                    'pedido_id' => $pedidoId,
                    'ruta_temp' => $rutaTemp,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rutasRelocalizadas;
    }

    /**
     * Mover una imagen individual de temp a pedidos/{pedido_id}/{tipo}/
     * 
     * @param int $pedidoId
     * @param string $rutaTemp Ejemplo: 'prendas/temp/uuid-abc123/file_123_xyz.webp'
     * @param string|null $tipoEspecifico Fuerza un tipo específico
     * @return string|null Ruta final o null si falla
     */
    private function moverImagen(int $pedidoId, string $rutaTemp, ?string $tipoEspecifico = null): ?string
    {
        // Validar que existe el archivo temporal
        if (!Storage::disk('public')->exists($rutaTemp)) {
            Log::warning('[ImagenRelocalizadorService] Archivo temporal no existe', [
                'ruta_temp' => $rutaTemp,
            ]);
            return null;
        }

        // Extraer tipo (prenda, tela, proceso, etc.) desde la ruta temp
        $tipo = $tipoEspecifico ?? $this->extraerTipo($rutaTemp);
        if (!$tipo) {
            Log::warning('[ImagenRelocalizadorService] No se pudo extraer tipo de ruta', [
                'ruta_temp' => $rutaTemp,
                'tipo_especifico' => $tipoEspecifico,
            ]);
            return null;
        }

        // Generar nombre del archivo (sin la carpeta temp)
        $nombreArchivo = basename($rutaTemp);

        // Construir ruta final
        $rutaFinal = "pedidos/{$pedidoId}/{$tipo}/{$nombreArchivo}";

        try {
            // Crear directorio si no existe
            $directorio = "pedidos/{$pedidoId}/{$tipo}";
            if (!Storage::disk('public')->exists($directorio)) {
                Storage::disk('public')->makeDirectory($directorio, 0755, true);
            }

            // Leer contenido del archivo temporal
            $contenido = Storage::disk('public')->get($rutaTemp);

            // Guardar en ubicación final
            Storage::disk('public')->put($rutaFinal, $contenido);

            // Eliminar archivo temporal
            Storage::disk('public')->delete($rutaTemp);

            // Limpiar carpeta temp si queda vacía
            $carpetaTemp = dirname($rutaTemp);
            $this->limpiarCarpetaTempSiVacia($carpetaTemp);

            Log::info('[ImagenRelocalizadorService] Imagen relocalizada exitosamente', [
                'pedido_id' => $pedidoId,
                'ruta_temp' => $rutaTemp,
                'ruta_final' => $rutaFinal,
                'tipo' => $tipo,
            ]);

            return $rutaFinal;
        } catch (\Exception $e) {
            Log::error('[ImagenRelocalizadorService] Error durante relocalización', [
                'pedido_id' => $pedidoId,
                'ruta_temp' => $rutaTemp,
                'ruta_final' => $rutaFinal,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extraer tipo (prenda, tela, proceso) desde ruta temporal
     * 
     * Soporta TRES formatos:
     * 1. CENTRALIZADO: 'temp/uuid/prendas/webp/file.webp' → 'prendas'
     * 2. ANTIGUO TEMP: 'prendas/temp/uuid/file.webp' → 'prendas'
     * 3. ANTIGUO: 'prendas/2026/01/file.jfif' → 'prendas'
     * 
     * @param string $ruta
     * @return string|null
     */
    private function extraerTipo(string $ruta): ?string
    {
        $partes = explode('/', $ruta);
        
        if (empty($partes)) {
            return null;
        }

        // Formato CENTRALIZADO: temp/{uuid}/{tipo}/...
        if ($partes[0] === 'temp' && isset($partes[2])) {
            $tipo = $partes[2];
            $tiposValidos = ['prendas', 'telas', 'procesos', 'logos', 'reflectivos', 'epp'];
            if (in_array($tipo, $tiposValidos)) {
                return $tipo;
            }
        }

        // Formato ANTIGUO: {tipo}/temp/... o {tipo}/2026/...
        $tipo = $partes[0];
        $tiposValidos = ['prendas', 'telas', 'procesos', 'logos', 'reflectivos', 'epp'];
        if (in_array($tipo, $tiposValidos)) {
            return $tipo;
        }

        return null;
    }

    /**
     * Limpiar carpeta temporal si queda vacía
     * Limpia recursivamente hasta temp/{uuid}/ si todo queda vacío
     * 
     * @param string $carpeta
     */
    private function limpiarCarpetaTempSiVacia(string $carpeta): void
    {
        try {
            if (!Storage::disk('public')->exists($carpeta)) {
                return;
            }

            $archivos = Storage::disk('public')->files($carpeta);
            $subdirectorios = Storage::disk('public')->directories($carpeta);
            
            // Si está vacía (sin archivos ni subdirectorios), eliminarla
            if (empty($archivos) && empty($subdirectorios)) {
                Storage::disk('public')->deleteDirectory($carpeta);
                Log::debug('[ImagenRelocalizadorService] Carpeta temporal eliminada', [
                    'carpeta' => $carpeta,
                ]);

                // Limpiar carpeta padre si también queda vacía
                $carpetaPadre = dirname($carpeta);
                if ($carpetaPadre !== '.' && str_starts_with($carpetaPadre, 'temp/')) {
                    $this->limpiarCarpetaTempSiVacia($carpetaPadre);
                }
            }
        } catch (\Exception $e) {
            Log::debug('[ImagenRelocalizadorService] No se pudo limpiar carpeta temp', [
                'carpeta' => $carpeta,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Limpiar carpeta de temp completamente (para pedidos que fallan, etc.)
     * Limpia temp/{uuid}/ completo
     * 
     * @param string $uuid
     */
    public function limpiarCarpetaTempPorUuid(string $uuid): void
    {
        try {
            $carpeta = "temp/{$uuid}";
            
            if (Storage::disk('public')->exists($carpeta)) {
                Storage::disk('public')->deleteDirectory($carpeta);
                Log::info('[ImagenRelocalizadorService] Carpeta temp limpiada por UUID', [
                    'uuid' => $uuid,
                    'carpeta' => $carpeta,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('[ImagenRelocalizadorService] Error limpiando carpeta temp por UUID', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
