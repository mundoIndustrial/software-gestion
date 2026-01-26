<?php

namespace App\Domain\Pedidos\Listeners;

use App\Domain\Pedidos\Events\PrendaPedidoAgregada;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Actualizar EstadÃ­sticas de Prendas
 * 
 * Se dispara cuando: Una prenda es agregada a un pedido (PrendaPedidoAgregada)
 * 
 * Responsabilidades:
 * - Actualizar estadÃ­sticas de prendas por tipo
 * - Invalidar cache de reportes
 * - Mantener contadores en Redis
 * 
 * Patrón: Observer
 * Tipo: Sincrónico (se ejecuta inmediatamente)
 * Criticidad: Baja (si falla no rompe el flujo principal)
 * 
 * Ejemplo de uso:
 * $eventDispatcher->dispatch(new PrendaPedidoAgregada(...));
 * // AutomÃ¡ticamente este listener se ejecuta
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
            Log::info(' [ActualizarEstadisticasPrendas] Procesando evento', [
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

            // Actualizar estadÃ­sticas por tipo
            $tipoKey = "prendas_tipo_" . $event->getNombrePrenda();
            $currentQty = (int) cache()->get($tipoKey, 0);
            cache()->put($tipoKey, $currentQty + $event->getCantidad(), now()->addDay());

            Log::info(' EstadÃ­sticas actualizadas', [
                'prenda_id' => $event->getPrendaId(),
                'contador_hoy' => cache()->get($dateKey),
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al actualizar estadÃ­sticas', [
                'error' => $e->getMessage(),
                'prenda_id' => $event->getPrendaId(),
            ]);
            // No relanzar: esto es una operación no crÃ­tica
        }
    }
}

