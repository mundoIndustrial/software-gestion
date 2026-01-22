<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use Illuminate\Support\Facades\DB;

/**
 * PedidoEppService
 * 
 * Servicio para manejar EPP agregados a pedidos
 */
class PedidoEppService
{
    /**
     * Guardar EPP agregados al pedido
     * 
     * @param PedidoProduccion $pedido
     * @param array $epps Array con EPP a guardar
     * @return array Array con los PedidoEpp creados
     */
    public function guardarEppsDelPedido(PedidoProduccion $pedido, array $epps): array
    {
        $pedidosEpp = [];

        foreach ($epps as $eppData) {
            $pedidoEpp = PedidoEpp::create([
                'pedido_produccion_id' => $pedido->id,
                'epp_id' => $eppData['epp_id'] ?? $eppData['id'],
                'cantidad' => $eppData['cantidad'] ?? 1,
                'observaciones' => $eppData['observaciones'] ?? null,
            ]);

            // Guardar imágenes si existen
            if (isset($eppData['imagenes']) && is_array($eppData['imagenes'])) {
                $this->guardarImagenesDelEpp($pedidoEpp, $eppData['imagenes']);
            }

            $pedidosEpp[] = $pedidoEpp;
        }

        return $pedidosEpp;
    }

    /**
     * Guardar imágenes de un EPP del pedido
     * 
     * @param PedidoEpp $pedidoEpp
     * @param array $imagenes Array con imágenes
     */
    private function guardarImagenesDelEpp(PedidoEpp $pedidoEpp, array $imagenes): void
    {
        foreach ($imagenes as $index => $imagen) {
            $archivo = null;
            $principal = false;
            $orden = $index;
            
            if (is_array($imagen)) {
                // Es un array con datos de imagen
                $archivo = $imagen['archivo'] ?? $imagen['ruta_original'] ?? $imagen['file'] ?? null;
                $principal = $imagen['principal'] ?? ($index === 0);
                $orden = $imagen['orden'] ?? $index;
            } else if (is_string($imagen)) {
                // Es un path o nombre de archivo
                $archivo = $imagen;
                $principal = $index === 0;
                $orden = $index;
            }
            
            if ($archivo) {
                // Si es UploadedFile, guardar en disco y transformar a webp
                if ($archivo instanceof \Illuminate\Http\UploadedFile) {
                    $pedidoId = $pedidoEpp->pedido_produccion_id;
                    $directorio = storage_path("app/public/pedidos/{$pedidoId}/epp");
                    
                    // Crear directorio si no existe
                    if (!is_dir($directorio)) {
                        mkdir($directorio, 0755, true);
                    }
                    
                    // Convertir a WebP usando ImageManager
                    try {
                        $imagen_obj = \Intervention\Image\ImageManager::gd()->read($archivo->getRealPath());
                    } catch (\Exception $e) {
                        try {
                            $imagen_obj = \Intervention\Image\ImageManager::imagick()->read($archivo->getRealPath());
                        } catch (\Exception $e2) {
                            \Log::error('Error transformando imagen EPP', ['error' => $e2->getMessage()]);
                            continue;
                        }
                    }
                    
                    // Redimensionar si es necesario
                    if ($imagen_obj->width() > 2000 || $imagen_obj->height() > 2000) {
                        $imagen_obj->scaleDown(width: 2000, height: 2000);
                    }
                    
                    // Convertir a WebP con calidad 80
                    $webp = $imagen_obj->toWebp(quality: 80);
                    $contenidoWebP = $webp->toString();
                    
                    // Generar nombre único
                    $timestamp = now()->format('YmdHis');
                    $random = substr(uniqid(), -6);
                    $nombreArchivo = "img_epp_{$index}_{$timestamp}_{$random}.webp";
                    $rutaCompleta = $directorio . '/' . $nombreArchivo;
                    
                    // Guardar archivo
                    file_put_contents($rutaCompleta, $contenidoWebP);
                    
                    // Rutas
                    $rutaOriginal = "pedidos/{$pedidoId}/epp/{$nombreArchivo}";
                    $rutaWeb = "/storage/pedidos/{$pedidoId}/epp/{$nombreArchivo}";
                } else {
                    // Es una ruta string
                    $rutaOriginal = $archivo;
                    $rutaWeb = str_starts_with($archivo, '/storage/') ? $archivo : '/storage/' . $archivo;
                }
                
                PedidoEppImagen::create([
                    'pedido_epp_id' => $pedidoEpp->id,
                    'ruta_original' => $rutaOriginal,
                    'ruta_web' => $rutaWeb,
                    'principal' => $principal,
                    'orden' => $orden,
                ]);
            }
        }
    }

    /**
     * Obtener EPP de un pedido con sus imágenes
     * 
     * @param PedidoProduccion $pedido
     * @return array
     */
    public function obtenerEppsDelPedido(PedidoProduccion $pedido): array
    {
        return $pedido->pedidosEpp()
            ->with(['epp', 'imagenes'])
            ->get()
            ->map(function ($pedidoEpp) {
                return [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'epp_nombre' => $pedidoEpp->epp->nombre,
                    'epp_codigo' => $pedidoEpp->epp->codigo,
                    'epp_categoria' => $pedidoEpp->epp->categoria->nombre,
                    'cantidad' => $pedidoEpp->cantidad,
                    'tallas_medidas' => $pedidoEpp->tallas_medidas,
                    'observaciones' => $pedidoEpp->observaciones,
                    'imagenes' => $pedidoEpp->imagenes->map(fn($img) => [
                        'id' => $img->id,
                        'archivo' => $img->archivo,
                        'principal' => $img->principal,
                        'orden' => $img->orden,
                    ])->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Actualizar un EPP del pedido
     * 
     * @param PedidoEpp $pedidoEpp
     * @param array $datos
     */
    public function actualizarEpp(PedidoEpp $pedidoEpp, array $datos): void
    {
        $pedidoEpp->update([
            'cantidad' => $datos['cantidad'] ?? $pedidoEpp->cantidad,
            'tallas_medidas' => $datos['tallas_medidas'] ?? $pedidoEpp->tallas_medidas,
            'observaciones' => $datos['observaciones'] ?? $pedidoEpp->observaciones,
        ]);
    }

    /**
     * Eliminar un EPP del pedido
     * 
     * @param PedidoEpp $pedidoEpp
     */
    public function eliminarEpp(PedidoEpp $pedidoEpp): void
    {
        $pedidoEpp->delete(); // Soft delete
    }

    /**
     * Serializar EPP para almacenar como JSON
     * 
     * @param PedidoProduccion $pedido
     * @return string JSON con los EPP del pedido
     */
    public function serializarEppsAJson(PedidoProduccion $pedido): string
    {
        return json_encode($this->obtenerEppsDelPedido($pedido));
    }
}
