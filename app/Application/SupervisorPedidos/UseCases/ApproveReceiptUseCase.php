<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ApproveReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveReceiptResponse;

class ApproveReceiptUseCase
{
    /**
     * Aprobar un recibo
     */
    public function execute(ApproveReceiptRequest $request): ApproveReceiptResponse
    {
        try {
            // Obtener el recibo
            $recibo = \DB::table('consecutivos_recibos_pedidos')
                ->where('id', $request->getReceiptId())
                ->first();
            
            if (!$recibo) {
                throw new \DomainException('Recibo no encontrado');
            }

            // Actualizar el proceso asociado si existe
            $actualizado = 0;
            if ($recibo->prenda_id) {
                $actualizado = \DB::table('pedidos_procesos_prenda_detalles')
                    ->where('prenda_pedido_id', $recibo->prenda_id)
                    ->where('estado', 'PENDIENTE')
                    ->update([
                        'estado' => 'COMPLETADO',
                        'updated_at' => now()
                    ]);
            }

            if ($actualizado > 0 || !$recibo->prenda_id) {
                // Marcar el recibo como inactivo
                \DB::table('consecutivos_recibos_pedidos')
                    ->where('id', $recibo->id)
                    ->update([
                        'activo' => 0,
                        'updated_at' => now()
                    ]);

                \Log::info('Recibo aprobado', [
                    'recibo_id' => $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'usuario' => auth()->user()?->name ?? 'N/A',
                    'fecha_aprobacion' => now()
                ]);

                return new ApproveReceiptResponse(
                    success: true,
                    message: 'Recibo aprobado correctamente',
                    receiptId: $recibo->id,
                    processesUpdated: $actualizado
                );
            } else {
                throw new \DomainException('No se pudo aprobar el recibo. Es posible que ya esté aprobado o no exista el proceso asociado.');
            }

        } catch (\Exception $e) {
            throw new \DomainException('Error al aprobar recibo: ' . $e->getMessage());
        }
    }
}
