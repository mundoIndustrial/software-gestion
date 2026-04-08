<?php

namespace App\Infrastructure\Services\Bodega;

use App\Domain\Bodega\Services\BodegaUpdateServiceContract;

use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetalleTalla;
use App\Models\BodegaDetalleVisto;
use App\Models\PedidoVistoSupervisor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BodegaUpdateService implements BodegaUpdateServiceContract
{
    public function __construct(
        private BodegaRoleService $roleService
    ) {}

    /**
     * Desmarcar pedido (opción de solo lectura check incluida)
     */
    public function desmarcar(int $pedidoId): array
    {
        try {
            // Validar que el usuario no sea de solo lectura
            $rolesDelUsuario = auth()->user()->getRoleNames()->toArray();
            $esReadOnly = $this->roleService->esReadOnly($rolesDelUsuario);
            
            if ($esReadOnly) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción.'
                ];
            }
            
            // Obtener el ReciboPrenda para conseguir el numero_pedido
            $reciboPrenda = ReciboPrenda::findOrFail($pedidoId);
            
            // Desmarcar pedido como visto (eliminar viewed_at)
            $actualizado = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)
                ->update(['viewed_at' => null]);
            
            if ($actualizado) {
                \Log::info("Pedido #{$reciboPrenda->numero_pedido} desmarcado como no visto por usuario " . auth()->user()->name);
                
                return [
                    'success' => true,
                    'message' => 'Pedido desmarcado correctamente.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo desmarcar el pedido.'
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en desmarcar: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al desmarcar el pedido: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marcar pedido como visto (solo para rol EPP-Bodega)
     */
    public function marcarVisto(int $pedidoId, bool $visto): array
    {
        try {
            // Validar que el usuario tenga el rol EPP-Bodega o bodeguero
            if (!auth()->user()->hasAnyRole(['EPP-Bodega', 'bodeguero'])) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción.'
                ];
            }

            $userId = auth()->id();
            
            // Obtener el pedido principal (verificar que existe)
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            if ($visto) {
                // Marcar como visto - crear registro en pedidos_vistos_supervisor
                // Usar firstOrCreate para evitar duplicados
                $registroVisto = PedidoVistoSupervisor::firstOrCreate([
                    'pedido_id' => $pedidoId,
                    'user_id' => $userId,
                ], [
                    'created_at' => now(),
                ]);
                
                $actualizado = $registroVisto->wasRecentlyCreated || $registroVisto->exists;
            } else {
                // Desmarcar como visto - eliminar registro
                $eliminados = PedidoVistoSupervisor::where('pedido_id', $pedidoId)
                    ->where('user_id', $userId)
                    ->delete();
                    
                $actualizado = $eliminados > 0;
            }
            
            if ($actualizado) {
                return [
                    'success' => true,
                    'message' => $visto ? 'Pedido marcado como visto.' : 'Pedido desmarcado como visto.',
                    'viewed_at' => $visto ? now()->toDateTimeString() : null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo actualizar el estado del pedido.'
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en marcarVisto: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marcar detalle de bodega como visto
     */
    public function marcarVistoDetalle(int $detalleId, bool $visto): array
    {
        try {
            // Validar que el usuario tenga permisos
            if (!auth()->user()->hasAnyRole(['EPP-Bodega', 'bodeguero'])) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción.'
                ];
            }

            $userId = auth()->id();
            
            // Verificar que el detalle existe
            BodegaDetalleTalla::findOrFail($detalleId);
            
            if ($visto) {
                // Marcar como visto - crear registro en bodega_detalles_visto
                $registroVisto = BodegaDetalleVisto::firstOrCreate([
                    'bodega_detalle_id' => $detalleId,
                    'user_id' => $userId,
                ]);
                
                $actualizado = $registroVisto->wasRecentlyCreated || $registroVisto->exists;
            } else {
                // Desmarcar como visto - eliminar registro
                $eliminados = BodegaDetalleVisto::where('bodega_detalle_id', $detalleId)
                    ->where('user_id', $userId)
                    ->delete();
                    
                $actualizado = $eliminados > 0;
            }
            
            if ($actualizado) {
                return [
                    'success' => true,
                    'message' => $visto ? 'Detalle marcado como visto.' : 'Detalle desmarcado como visto.',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo actualizar el estado del detalle.'
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en marcarVistoDetalle: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar el detalle: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar observaciones de un recibo de prenda
     */
    public function actualizarObservaciones(int $id, ?string $observaciones): array
    {
        // Validar que el usuario no sea de solo lectura
        $rolesDelUsuario = auth()->user()->getRoleNames()->toArray();
        if ($this->roleService->esReadOnly($rolesDelUsuario)) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción. Tu rol es de solo lectura.'
            ];
        }

        try {
            $reciboPrenda = ReciboPrenda::findOrFail($id);

            // Actualizar observaciones
            $reciboPrenda->update([
                'observaciones' => $observaciones,
            ]);

            return [
                'success' => true,
                'message' => 'Observaciones actualizadas correctamente',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al actualizar observaciones: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar observaciones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar fecha de entrega de un recibo de prenda
     */
    public function actualizarFecha(int $id, string $fechaEntrega): array
    {
        // Validar que el usuario no sea de solo lectura
        $rolesDelUsuario = auth()->user()->getRoleNames()->toArray();
        if ($this->roleService->esReadOnly($rolesDelUsuario)) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción. Tu rol es de solo lectura.'
            ];
        }

        try {
            $reciboPrenda = ReciboPrenda::findOrFail($id);

            // Actualizar fecha
            $reciboPrenda->update([
                'fecha_entrega' => $fechaEntrega,
            ]);

            return [
                'success' => true,
                'message' => 'Fecha de entrega actualizada correctamente',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al actualizar fecha: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar fecha: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar fecha de entrega a despacho
     */
    public function actualizarFechaEntregaDespacho(int $id, string $fechaEntregaDespacho): array
    {
        try {
            $bodegaDetalle = BodegaDetalleTalla::findOrFail($id);
            $bodegaDetalle->update([
                'fecha_entrega_despacho' => $fechaEntregaDespacho
            ]);

            return [
                'success' => true,
                'message' => 'Fecha de entrega a despacho actualizada correctamente',
                'fecha_entrega_despacho' => $bodegaDetalle->fecha_entrega_despacho?->format('d/m/Y')
            ];
        } catch (\Exception $e) {
            \Log::error('Error al actualizar fecha de entrega a despacho: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar la fecha'
            ];
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {BodegaUpdateService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
