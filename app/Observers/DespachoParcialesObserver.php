<?php

namespace App\Observers;

use App\Models\DesparChoParcialesModel;
use App\Domain\Pedidos\Despacho\Services\DespachoEstadoService;
use Illuminate\Support\Facades\Log;

/**
 * Observer para DesparChoParcialesModel
 * 
 * Se dispara cuando hay cambios en los despachos parciales
 * y verifica si el pedido debe marcarse como "Entregado"
 */
class DespachoParcialesObserver
{
    public function __construct(
        private DespachoEstadoService $despachoEstadoService
    ) {}

    /**
     * Handle the DesparChoParcialesModel "updated" event.
     * 
     * Se dispara cuando se actualiza un despacho parcial
     * 
     * @param DesparChoParcialesModel $despacho
     * @return void
     */
    public function updated(DesparChoParcialesModel $despacho): void
    {
        // Solo procesar si el campo 'entregado' fue modificado
        if ($despacho->isDirty('entregado')) {
            $this->verificarYActualizarEstadoPedido($despacho->pedido_id);
        }
    }

    /**
     * Handle the DesparChoParcialesModel "saved" event.
     * 
     * Se dispara cuando se crea o actualiza un despacho parcial
     * 
     * @param DesparChoParcialesModel $despacho
     * @return void
     */
    public function saved(DesparChoParcialesModel $despacho): void
    {
        // Verificar después de guardar (para nuevos registros o actualizaciones)
        $this->verificarYActualizarEstadoPedido($despacho->pedido_id);
    }

    /**
     * Verificar y actualizar el estado del pedido si corresponde
     * 
     * @param int $pedidoId
     * @return void
     */
    private function verificarYActualizarEstadoPedido(int $pedidoId): void
    {
        try {
            Log::debug('Verificando estado del pedido por cambio en despacho', [
                'pedido_id' => $pedidoId
            ]);

            // Usar el servicio para verificar y cambiar estado si corresponde
            $cambiadoAEntregado = $this->despachoEstadoService->cambiarEstadoAEntregadoSiCorresponde($pedidoId);
            $cambiadoAPendiente = $this->despachoEstadoService->cambiarEstadoAPendienteSiCorresponde($pedidoId);

            if ($cambiadoAEntregado) {
                Log::info('Pedido marcado como Entregado automáticamente por Observer', [
                    'pedido_id' => $pedidoId,
                    'evento' => 'DespachoParcialesObserver'
                ]);
            } elseif ($cambiadoAPendiente) {
                Log::info('Pedido marcado como Pendiente automáticamente por Observer', [
                    'pedido_id' => $pedidoId,
                    'evento' => 'DespachoParcialesObserver'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error en Observer al verificar estado de pedido', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
