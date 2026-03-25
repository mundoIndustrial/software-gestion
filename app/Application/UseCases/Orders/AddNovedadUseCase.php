<?php

namespace App\Application\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Models\AuditLog;
use App\Events\OrdenUpdated;
use Carbon\Carbon;

/**
 * UseCase: Agregar una novedad a una orden
 * 
 * Responsabilidades:
 * - Validar entrada
 * - Formatear novedad con usuario y fecha
 * - Registrar en auditoría
 */
class AddNovedadUseCase
{
    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $numeroPedido, string $novedad): array
    {
        // Buscar la orden
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->firstOrFail();

        // Obtener usuario
        $usuario = auth()->user()->name ?? auth()->user()->email ?? 'Usuario';
        
        // Formatear novedad con timestamp
        $fechaHora = Carbon::now()->format('d-m-Y h:i:s A');
        $novedadFormato = "[{$usuario} - {$fechaHora}] " . $novedad;
        
        // Obtener novedades actuales
        $novedadesActuales = $orden->novedades ?? '';
        
        // Concatenar con salto de línea
        $novedadesNuevas = !empty($novedadesActuales) 
            ? $novedadesActuales . "\n\n" . $novedadFormato
            : $novedadFormato;
        
        // Actualizar
        $orden->update(['novedades' => $novedadesNuevas]);
        
        // Registrar en auditoría
        if (class_exists(AuditLog::class)) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'add_novedad',
                'auditable_type' => PedidoProduccion::class,
                'auditable_id' => $orden->id,
                'changes' => ['novedad_agregada' => $novedadFormato]
            ]);
        }

        // Broadcast
        try {
            broadcast(new OrdenUpdated($orden->fresh(), 'updated', ['novedades']));
        } catch (\Exception $e) {
            \Log::warning("Error de broadcast para pedido {$numeroPedido}", [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'message' => 'Novedad agregada correctamente',
            'data' => [
                'numero_pedido' => $orden->numero_pedido,
                'novedades' => $orden->novedades
            ]
        ];
    }
}
