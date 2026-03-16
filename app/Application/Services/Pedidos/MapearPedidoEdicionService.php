<?php

namespace App\Application\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

/**
 * MapearPedidoEdicionService
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Transformar datos de pedido existente para modo edición
 * - Mapear prendas con sus relaciones
 * - Preparar EPPs para edición
 * 
 * SACADO DEL CONTROLLER (Refactor Fase 9):
 * Antes: Lógica de mapeo inline en crearNuevo() cuando ?edit=ID
 * Ahora: Servicio especializado
 */
class MapearPedidoEdicionService
{
    /**
     * Preparar datos de pedido para modo edición
     * 
     * @param PedidoProduccion $pedido
     * @return array [
     *   'cliente_nombre' => string,
     *   'prendas' => array,
     *   'epps' => array
     * ]
     */
    public function mapearPedidoParaEdicion(PedidoProduccion $pedido): array
    {
        $inicioMapeo = microtime(true);

        // Obtener nombre del cliente
        $clienteNombre = $this->obtenerClienteNombre($pedido);

        // Mapear prendas
        $prendasMapeadas = $this->mapearPrendas($pedido);

        // Mapear EPPs
        $eppsMapeados = $this->mapearEpps($pedido);

        $tiempoMapeo = round((microtime(true) - $inicioMapeo) * 1000, 2);
        Log::info('[MapearPedidoEdicionService] Pedido mapeado para edición', [
            'pedido_id' => $pedido->id,
            'prendas' => count($prendasMapeadas),
            'epps' => count($eppsMapeados),
            'tiempo_ms' => $tiempoMapeo,
        ]);

        return [
            'cliente_nombre' => $clienteNombre,
            'prendas' => $prendasMapeadas,
            'epps' => $eppsMapeados,
        ];
    }

    /**
     * Obtener nombre del cliente desde pedido
     * 
     * @param PedidoProduccion $pedido
     * @return string
     */
    private function obtenerClienteNombre(PedidoProduccion $pedido): string
    {
        // Primero intentar obtener del campo cliente (string) de la tabla
        $nombre = $pedido->getOriginal('cliente');
        
        if (!empty($nombre)) {
            return $nombre;
        }

        // Si no existe, obtener del cliente_id (relación)
        if ($pedido->cliente_id) {
            $cliente = Cliente::find($pedido->cliente_id);
            return $cliente?->nombre ?? '';
        }

        return '';
    }

    /**
     * Mapear prendas del pedido
     * 
     * @param PedidoProduccion $pedido
     * @return array
     */
    private function mapearPrendas(PedidoProduccion $pedido): array
    {
        return $pedido->prendas->map(function ($prenda) {
            return [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre_prenda,
                'genero' => $prenda->genero,
                'color' => $prenda->color,
                'observaciones' => $prenda->observaciones,
                
                // Cantidades por talla
                'cantidadesPorTalla' => $prenda->tallas->pluck('cantidad', 'talla')->toArray(),
                'generosConTallas' => $prenda->tallas
                    ->groupBy('genero')
                    ->map(fn($tallasGenero) => $tallasGenero->pluck('cantidad', 'talla'))
                    ->toArray(),

                // Telas/colores
                'telasAgregadas' => $prenda->coloresTelas->map(function ($ct) {
                    return [
                        'tela' => $ct->tela?->nombre ?? '',
                        'nombre_tela' => $ct->tela?->nombre ?? '',
                        'color' => $ct->color?->nombre ?? '',
                        'color_nombre' => $ct->color?->nombre ?? '',
                        'referencia' => $ct->referencia ?? '',
                    ];
                })->toArray(),

                // Imágenes
                'fotos' => $prenda->fotos->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url,
                        'principal' => $foto->principal ?? false,
                    ];
                })->toArray(),

                // Procesos
                'procesos' => $prenda->procesos->map(function ($proceso) {
                    return [
                        'id' => $proceso->id,
                        'nombre' => $proceso->nombre,
                        'tecnica' => $proceso->tecnica,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Mapear EPPs del pedido para modo edición
     * 
     * @param PedidoProduccion $pedido
     * @return array
     */
    private function mapearEpps(PedidoProduccion $pedido): array
    {
        return $pedido->epps->map(function ($pedidoEpp) {
            $nombre = $pedidoEpp->epp?->nombre_completo ?? 'EPP #' . $pedidoEpp->epp_id;
            
            return [
                'epp_id' => $pedidoEpp->epp_id,
                'nombre_completo' => $nombre,
                'nombre_epp' => $nombre,
                'tipo' => 'epp',
                'cantidad' => $pedidoEpp->cantidad,
                'observaciones' => $pedidoEpp->observaciones,
                'imagenes' => $this->mapearImagenesEpp($pedidoEpp),
            ];
        })->toArray();
    }

    /**
     * Mapear imágenes de un EPP
     * 
     * @param mixed $pedidoEpp
     * @return array
     */
    private function mapearImagenesEpp($pedidoEpp): array
    {
        return $pedidoEpp->imagenes->map(function ($img) {
            return [
                'id' => $img->id,
                'ruta_web' => $img->ruta_web,
                'principal' => $img->principal ?? false,
            ];
        })->toArray();
    }
}
