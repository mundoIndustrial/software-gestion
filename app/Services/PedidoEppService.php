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
                'tallas_medidas' => $eppData['tallas_medidas'] ?? null,
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
            // $imagen puede ser:
            // 1. Un string (path) si viene del controlador
            // 2. Un array con 'archivo', 'principal', 'orden' si viene del controlador
            // 3. Un array con 'file' (UploadedFile) si viene de otra fuente
            
            $archivo = null;
            $principal = false;
            $orden = $index;
            
            if (is_array($imagen)) {
                // Es un array con datos de imagen
                $archivo = $imagen['archivo'] ?? $imagen['file'] ?? null;
                $principal = $imagen['principal'] ?? ($index === 0);
                $orden = $imagen['orden'] ?? $index;
            } else if (is_string($imagen)) {
                // Es un path o nombre de archivo
                $archivo = $imagen;
                $principal = $index === 0;
                $orden = $index;
            }
            
            if ($archivo) {
                PedidoEppImagen::create([
                    'pedido_epp_id' => $pedidoEpp->id,
                    'archivo' => $archivo,
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
