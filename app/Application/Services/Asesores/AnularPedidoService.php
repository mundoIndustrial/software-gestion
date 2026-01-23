<?php

namespace App\Application\Services\Asesores;

use App\Models\Pedidos;
use Illuminate\Support\Facades\Auth;
use App\Events\OrdenUpdated;

/**
 * AnularPedidoService
 * 
 * Servicio para anular pedidos con registro de novedad.
 * Encapsula la lÃ³gica de negocio de anulaciÃ³n.
 */
class AnularPedidoService
{
    /**
     * Anular un pedido
     */
    public function anular(int $numeroPedido, string $novedad): Pedidos
    {
        $pedido = Pedidos::where('numero_pedido', $numeroPedido)
            ->firstOrFail();

        // Verificar permisos
        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para anular este pedido', 403);
        }

        // Formatear novedad
        $nombreUsuario = Auth::user()->name;
        $fechaHora = now()->format('d-m-Y h:i:s A');
        $nuevaNovedad = "[{$nombreUsuario} - {$fechaHora}] {$novedad}";

        // Agregar a novedades existentes
        $novedadesActuales = $pedido->novedades ?? '';
        $novedadesActualizadas = trim($novedadesActuales) !== ''
            ? $novedadesActuales . "\n" . $nuevaNovedad
            : $nuevaNovedad;

        // Actualizar
        $pedido->update([
            'estado' => 'Anulada',
            'novedades' => $novedadesActualizadas,
        ]);

        // Disparar evento
        event(new OrdenUpdated($pedido, 'updated', ['estado', 'novedades']));

        // Log
        \Log::info("Pedido #{$pedido->numero_pedido} anulado por " . Auth::user()->name, [
            'novedad' => $novedad,
            'fecha' => now(),
        ]);

        return $pedido;
    }
}

