<?php

namespace App\Domain\PedidoProduccion\Listeners;

use App\Domain\PedidoProduccion\Events\PrendaPedidoAgregada;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Actualizar Estadísticas de Prendas
 * 
 * Se dispara cuando: Una prenda es agregada a un pedido (PrendaPedidoAgregada)
 * 
 * Responsabilidades:
 * - Actualizar estadísticas de prendas por tipo
 * - Invalidar cache de reportes
 * - Mantener contadores en Redis
 * 
 * Patrón: Observer
 * Tipo: Sincrónico (se ejecuta inmediatamente)
 * Criticidad: Baja (si falla no rompe el flujo principal)
 * 
 * Ejemplo de uso:
 * $eventDispatcher->dispatch(new PrendaPedidoAgregada(...));
 * // Automáticamente este listener se ejecuta
 */
class ActualizarEstadisticasPrendas
{
    /**
     * Manejar evento PrendaPedidoAgregada
     * 
     * @param PrendaPedidoAgregada $event
     * @return void
     */
    public function __invoke(PrendaPedidoAgregada $event): void
    {
        try {
            Log::info('📊 [ActualizarEstadisticasPrendas] Procesando evento', [
                'prenda_id' => $event->getPrendaId(),
                'nombre' => $event->getNombrePrenda(),
                'cantidad' => $event->getCantidad(),
            ]);

            // Invalidar cache de prendas por pedido
            cache()->forget("pedido_{$event->getPedidoId()}_prendas");

            // Invalidar cache de reportes de prendas
            cache()->forget('reportes_prendas_por_tipo');
            cache()->forget('reportes_prendas_por_genero');

            // Actualizar contador de prendas agregadas hoy
            $dateKey = "prendas_agregadas_" . now()->format('Y-m-d');
            $currentCount = (int) cache()->get($dateKey, 0);
            cache()->put($dateKey, $currentCount + 1, now()->addDay());

            // Actualizar estadísticas por tipo
            $tipoKey = "prendas_tipo_" . $event->getNombrePrenda();
            $currentQty = (int) cache()->get($tipoKey, 0);
            cache()->put($tipoKey, $currentQty + $event->getCantidad(), now()->addDay());

            Log::info(' Estadísticas actualizadas', [
                'prenda_id' => $event->getPrendaId(),
                'contador_hoy' => cache()->get($dateKey),
            ]);

        } catch (\Exception $e) {
            Log::error('⚠️ Error al actualizar estadísticas', [
                'error' => $e->getMessage(),
                'prenda_id' => $event->getPrendaId(),
            ]);
            // No relanzar: esto es una operación no crítica
        }
    }
}
