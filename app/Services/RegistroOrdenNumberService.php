<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * RegistroOrdenNumberService
 * 
 * Responsabilidad: Gestión de números de pedido consecutivos y actualizaciones
 * Cumple con SRP: Solo maneja números de pedido
 * Cumple con DIP: Inyecta dependencias necesarias
 */
class RegistroOrdenNumberService
{
    /**
     * Obtener el próximo número de pedido disponible
     */
    public function getNextNumber(): int
    {
        $lastPedido = PedidoProduccion::max('numero_pedido');
        return $lastPedido ? $lastPedido + 1 : 1;
    }

    /**
     * Validar si un número de pedido es el siguiente esperado
     */
    public function isNextExpected(int $pedido): bool
    {
        $nextExpected = $this->getNextNumber();
        return $pedido === $nextExpected;
    }

    /**
     * Obtener información del siguiente pedido
     */
    public function getNextPedidoInfo(): array
    {
        return [
            'next_pedido' => $this->getNextNumber(),
            'last_pedido' => PedidoProduccion::max('numero_pedido') ?? 0
        ];
    }

    /**
     * Actualizar número de pedido
     */
    public function updatePedidoNumber(int $oldPedido, int $newPedido): void
    {
        DB::beginTransaction();

        try {
            // Verificar que la orden antigua existe
            $orden = PedidoProduccion::where('numero_pedido', $oldPedido)->firstOrFail();

            // Verificar que el nuevo pedido no existe ya
            $existingOrder = PedidoProduccion::where('numero_pedido', $newPedido)->first();
            if ($existingOrder) {
                throw new \InvalidArgumentException("El número de pedido {$newPedido} ya está en uso");
            }

            // Actualizar número de pedido
            $orden->update(['numero_pedido' => $newPedido]);

            // Invalidar caché para ambos pedidos
            $this->invalidateCacheDays($oldPedido);
            $this->invalidateCacheDays($newPedido);

            // Registrar evento
            $this->logPedidoNumberChange($oldPedido, $newPedido);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Invalidar caché de días calculados
     */
    private function invalidateCacheDays(int $numeroPedido): void
    {
        $hoy = now()->format('Y-m-d');
        $currentYear = now()->year;
        $festivos = FestivosColombiaService::obtenerFestivos($currentYear);
        $festivosCacheKey = md5(serialize($festivos));
        
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$numeroPedido}_{$estado}_{$hoy}_{$festivosCacheKey}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }
        
        for ($i = 1; $i <= 7; $i++) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            foreach ($estados as $estado) {
                $cacheKey = "orden_dias_{$numeroPedido}_{$estado}_{$fecha}_{$festivosCacheKey}";
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Registrar cambio de número de pedido
     */
    private function logPedidoNumberChange(int $oldPedido, int $newPedido): void
    {
        News::create([
            'event_type' => 'pedido_updated',
            'description' => "Número de pedido actualizado: {$oldPedido} → {$newPedido}",
            'user_id' => auth()->id(),
            'pedido' => $newPedido,
            'metadata' => ['old_pedido' => $oldPedido, 'new_pedido' => $newPedido]
        ]);
    }

    /**
     * Broadcast evento de pedido actualizado
     */
    public function broadcastPedidoUpdated(PedidoProduccion $orden): void
    {
        broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated'));
    }
}
