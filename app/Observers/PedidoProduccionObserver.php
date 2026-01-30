<?php

namespace App\Observers;

use App\Events\PedidoActualizado;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Services\ConsecutivosRecibosService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoProduccionObserver
{
    /**
     * Handle the PedidoProduccion "updated" event.
     */
    public function updated(PedidoProduccion $pedido): void
    {
        Log::info('🔢 [PedidoProduccionObserver] UPDATED method called', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'estado_anterior' => $pedido->getOriginal('estado'),
            'estado_nuevo' => $pedido->estado,
            'changed_fields' => $pedido->getDirty()
        ]);
        
        // Lógica existente de notificaciones
        $this->handleFechaEstimadaNotification($pedido);
        
        // Nueva lógica de broadcasting para cambios importantes
        $this->handleBroadcastingChanges($pedido);
        
        // Generar consecutivos cuando el estado cambia a PENDIENTE_INSUMOS
        $this->handleGeneracionConsecutivos($pedido);
    }

    /**
     * Handle the PedidoProduccion "created" event.
     */
    public function created(PedidoProduccion $pedido): void
    {
        // TEMPORALMENTE DESHABILITADO PARA EVITAR TIMEOUT DE WEBSOCKET
        // $this->broadcastPedidoChange($pedido, 'created');
        
        Log::warning('[PedidoProduccionObserver] Broadcasting deshabilitado temporalmente para evitar timeout', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
    }

    /**
     * Handle the PedidoProduccion "deleted" event.
     */
    public function deleted(PedidoProduccion $pedido): void
    {
        $this->broadcastPedidoChange($pedido, 'deleted');
    }

    /**
     * Manejar notificaciones de fecha estimada (lógica existente)
     */
    private function handleFechaEstimadaNotification(PedidoProduccion $pedido): void
    {
        // Detectar si se asignó la fecha estimada por primera vez
        if ($pedido->wasChanged('fecha_estimada_de_entrega')) {
            
            // Solo crear notificación si la fecha fue cambiada de null a algo
            if (is_null($pedido->getOriginal('fecha_estimada_de_entrega')) && 
                !is_null($pedido->fecha_estimada_de_entrega)) {
                
                // No crear notificación para el mismo asesor que creó el pedido
                $usuarioActual = Auth::id();
                
                if ($pedido->asesor_id && $pedido->asesor_id !== $usuarioActual) {
                    // Crear notificación en la tabla notifications de Laravel
                    DB::table('notifications')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id' => $pedido->asesor_id,
                        'type' => 'App\\Notifications\\FechaEstimadaAsignada',
                        'data' => json_encode([
                            'pedido_id' => $pedido->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'fecha_estimada' => $pedido->fecha_estimada_de_entrega->format('d/m/Y'),
                            'usuario_que_genero_id' => $usuarioActual,
                            'usuario_que_genero_nombre' => Auth::user()->name ?? 'Sistema',
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    \Log::info('Notificación de fecha estimada creada', [
                        'pedido_id' => $pedido->id,
                        'asesor_id' => $pedido->asesor_id,
                        'fecha_estimada' => $pedido->fecha_estimada_de_entrega,
                    ]);
                }
            }
        }
    }

    /**
     * Manejar broadcasting de cambios importantes
     */
    private function handleBroadcastingChanges(PedidoProduccion $pedido): void
    {
        // Detectar campos que cambiaron
        $changedFields = $this->getChangedFields($pedido);
        
        if (!empty($changedFields)) {
            $this->broadcastPedidoChange($pedido, 'updated', $changedFields);
        }
    }

    /**
     * Broadcast cambios de pedido
     */
    private function broadcastPedidoChange(PedidoProduccion $pedido, string $action, array $changedFields = []): void
    {
        try {
            // Obtener el asesor del pedido
            $asesor = $this->getAsesorFromPedido($pedido);
            
            if (!$asesor) {
                Log::warning('No se encontró asesor para el pedido', [
                    'pedido_id' => $pedido->id,
                    'asesor_id' => $pedido->asesor_id,
                ]);
                return;
            }

            // Emitir evento de broadcasting
            PedidoActualizado::dispatch($pedido, $asesor, $changedFields, $action);

            Log::info('PedidoActualizado event dispatched', [
                'pedido_id' => $pedido->id,
                'asesor_id' => $asesor->id,
                'action' => $action,
                'changed_fields' => $changedFields,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al broadcastear cambio de pedido', [
                'pedido_id' => $pedido->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener los campos que cambiaron
     */
    private function getChangedFields(PedidoProduccion $pedido): array
    {
        $importantFields = [
            'estado',
            'novedades',
            'forma_pago',
            'fecha_estimada',
            'cliente',
            'descripcion',
            'area',
        ];

        $changedFields = [];
        
        foreach ($importantFields as $field) {
            if ($pedido->wasChanged($field)) {
                $changedFields[$field] = [
                    'old' => $pedido->getOriginal($field),
                    'new' => $pedido->$field,
                ];
            }
        }

        return $changedFields;
    }

    /**
     * Obtener el asesor del pedido
     */
    private function getAsesorFromPedido(PedidoProduccion $pedido): ?User
    {
        // Intentar obtener desde la relación
        if ($pedido->relationLoaded('asesor') && $pedido->asesor) {
            return $pedido->asesor;
        }

        // Intentar obtener desde el campo asesor_id
        if ($pedido->asesor_id) {
            return User::find($pedido->asesor_id);
        }

        // Si no hay asesor_id, usar el usuario autenticado (si aplica)
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            return Auth::user();
        }

        return null;
    }
    
    /**
     * Manejar generación de consecutivos cuando el estado cambia a PENDIENTE_INSUMOS
     */
    private function handleGeneracionConsecutivos(PedidoProduccion $pedido): void
    {
        // Solo ejecutar si el campo 'estado' fue modificado
        if (!$pedido->wasChanged('estado')) {
            return;
        }
        
        $estadoAnterior = $pedido->getOriginal('estado');
        $estadoNuevo = $pedido->estado;
        
        Log::info('🔢 [PedidoProduccionObserver] Detectando cambio de estado para consecutivos', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
        ]);
        
        try {
            $consecutivosService = new ConsecutivosRecibosService();
            $generado = $consecutivosService->generarConsecutivosSiAplica($pedido, $estadoAnterior, $estadoNuevo);
            
            if ($generado) {
                Log::info('🔢 [PedidoProduccionObserver] Consecutivos generados exitosamente', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('🔢 [PedidoProduccionObserver] Error al generar consecutivos', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
