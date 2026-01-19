<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmarPedidoService
{
    /**
     * Confirmar un pedido borrador y asignarle un número de pedido
     * 
     * @param int $borradorId ID del pedido borrador
     * @param int $numeroPedido Número de pedido a asignar
     * @return PedidoProduccion
     * @throws \Exception
     */
    public function confirmar(int $borradorId, int $numeroPedido): PedidoProduccion
    {
        Log::info('🔐 [CONFIRMAR] Confirmando pedido borrador', [
            'borrador_id' => $borradorId,
            'numero_pedido' => $numeroPedido
        ]);

        DB::beginTransaction();
        try {
            // Obtener el pedido borrador
            $pedido = PedidoProduccion::findOrFail($borradorId);

            // Verificar que no tenga ya un número asignado
            if ($pedido->numero_pedido !== null) {
                throw new \Exception('Este pedido ya ha sido confirmado', 400);
            }

            // Verificar que el número no esté en uso
            if ($this->existeNumeroPedido($numeroPedido)) {
                throw new \Exception('El número de pedido ' . $numeroPedido . ' ya está en uso', 422);
            }

            // Actualizar con el número de pedido real
            $pedido->update([
                'numero_pedido' => $numeroPedido
            ]);

            DB::commit();

            Log::info('✅ [CONFIRMAR] Pedido confirmado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido
            ]);

            return $pedido;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [CONFIRMAR] Error al confirmar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Verificar si un número de pedido ya existe
     */
    private function existeNumeroPedido(int $numeroPedido): bool
    {
        return PedidoProduccion::where('numero_pedido', $numeroPedido)->exists();
    }

    /**
     * Confirmar múltiples pedidos en lote
     * 
     * @param array $pedidosAConfirmar Array de ['borrador_id' => numeroPedido]
     * @return array Resultados de la confirmación
     */
    public function confirmarLote(array $pedidosAConfirmar): array
    {
        Log::info('🔐 [CONFIRMAR-LOTE] Confirmando ' . count($pedidosAConfirmar) . ' pedidos');

        $resultados = [
            'exitosos' => [],
            'errores' => []
        ];

        foreach ($pedidosAConfirmar as $borradorId => $numeroPedido) {
            try {
                $pedido = $this->confirmar($borradorId, $numeroPedido);
                $resultados['exitosos'][$borradorId] = $pedido;
            } catch (\Exception $e) {
                $resultados['errores'][$borradorId] = $e->getMessage();
                Log::warning('⚠️ [CONFIRMAR-LOTE] Error en pedido ' . $borradorId, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('✅ [CONFIRMAR-LOTE] Completado', [
            'exitosos' => count($resultados['exitosos']),
            'errores' => count($resultados['errores'])
        ]);

        return $resultados;
    }

    /**
     * Validar si un pedido puede ser confirmado
     */
    public function puedeConfirmarse(int $borradorId): bool
    {
        $pedido = PedidoProduccion::find($borradorId);

        if (!$pedido) {
            Log::warning('⚠️ [PUEDE-CONFIRMAR] Pedido no encontrado: ' . $borradorId);
            return false;
        }

        if ($pedido->numero_pedido !== null) {
            Log::warning('⚠️ [PUEDE-CONFIRMAR] Pedido ya confirmado: ' . $borradorId);
            return false;
        }

        if ($pedido->prendas()->count() === 0) {
            Log::warning('⚠️ [PUEDE-CONFIRMAR] Pedido sin prendas: ' . $borradorId);
            return false;
        }

        Log::info('✅ [PUEDE-CONFIRMAR] Pedido listo para confirmar: ' . $borradorId);
        return true;
    }
}
