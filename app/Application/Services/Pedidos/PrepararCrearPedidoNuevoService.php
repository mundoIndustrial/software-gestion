<?php

namespace App\Application\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\Application\Services\Pedidos\Contracts\PrepararCrearPedidoServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * PrepararCrearPedidoNuevoService
 * 
 * PHASE 13 (Marzo 2026): Refactoring HTTP to Service
 * 
 * Responsabilidad ÚNICA: Preparar datos para la vista de crear pedido nuevo
 * 
 * Maneja:
 * 1. Obtener pedido existente si modo edición (?edit=ID)
 * 2. Mapear datos de edición
 * 3. Retornar datos formateados
 * 
 * Controller solo hace HTTP orchestration
 */
class PrepararCrearPedidoNuevoService implements PrepararCrearPedidoServiceInterface
{
    public function __construct(
        private MapearPedidoEdicionService $mapearPedidoEdicion,
    ) {}

    /**
     * Preparar datos según si es nuevo o edición
     * 
     * @param int|null $editId - ID del pedido a editar (si existe)
     * @return array [
     *   'modo_edicion' => bool,
     *   'pedido_editar' => ?PedidoProduccion,
     *   'pedido_editar_id' => ?int,
     *   'epps_editar' => array,
     * ]
     */
    public function ejecutar(?int $editId): array
    {
        // Por defecto: crear nuevo
        $modoEdicion = false;
        $pedidoEditar = null;
        $pedidoEditarId = null;
        $eppsEditar = [];

        // Si hay ID de edición, cargar ese pedido
        if ($editId) {
            $pedidoEditar = $this->obtenerPedidoParaEdicion($editId);
            
            if ($pedidoEditar) {
                $modoEdicion = true;
                $pedidoEditarId = $pedidoEditar->id;

                // ====== MAPEAR DATOS DE EDICIÓN ======
                $datosEdicion = $this->mapearPedidoEdicion->mapearPedidoParaEdicion($pedidoEditar);
                
                // Inyectar datos mapeados al modelo
                $pedidoEditar->cliente_nombre_display = $datosEdicion['cliente_nombre'];
                $pedidoEditar->prendas = collect($datosEdicion['prendas']);
                $eppsEditar = $datosEdicion['epps'];

                Log::info('[PrepararCrearPedidoNuevoService] ✏️ MODO EDICIÓN activado', [
                    'pedido_id' => $pedidoEditarId,
                    'prendas' => count($datosEdicion['prendas']),
                    'epps' => count($datosEdicion['epps']),
                    'mejora' => "Servicio encapsula lógica de edición",
                ]);
            } else {
                Log::warning('[PrepararCrearPedidoNuevoService] ⚠️ Pedido no encontrado', ['edit_id' => $editId]);
            }
        }

        return [
            'modo_edicion' => $modoEdicion,
            'pedido_editar' => $pedidoEditar,
            'pedido_editar_id' => $pedidoEditarId,
            'epps_editar' => $eppsEditar,
        ];
    }

    /**
     * Obtener pedido con todas sus relaciones para edición
     * 
     * @param int $pedidoId
     * @return ?PedidoProduccion
     */
    private function obtenerPedidoParaEdicion(int $pedidoId): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'prendas.tallas',
            'prendas.fotos',
            'prendas.coloresTelas',
            'prendas.procesos',
            'epps.epp',
            'epps.imagenes',
        ])->find($pedidoId);
    }
}
