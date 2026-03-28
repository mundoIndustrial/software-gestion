<?php

namespace App\Application\Pedidos\UseCases\RegistroOrden;

use App\Models\PedidoProduccion;
use App\Exceptions\CalcularFechaEstimadaException;

/**
 * CalcularFechaEstimadaUseCase
 * 
 * UseCase: Calcular fecha estimada de entrega de una orden
 * Capa: Application
 * Responsabilidad: Orquestar el cálculo de fecha estimada basado en días de entrega
 * 
 * Nota: Las excepciones son manejadas por el Handler que renderiza
 * respuestas JSON automáticamente. El UseCase solo lanza excepciones.
 */
class CalcularFechaEstimadaUseCase
{
    /**
     * Ejecutar cálculo de fecha estimada
     * 
     * @param int $orderId
     * @param int $diaDeEntrega
     * @return array ['fecha_estimada' => string, 'fecha_estimada_iso' => string, 'dias' => int, ...]
     * @throws CalcularFechaEstimadaException
     */
    public function execute(int $orderId, int $diaDeEntrega): array
    {
        // Validar entrada
        if ($diaDeEntrega < 1) {
            throw CalcularFechaEstimadaException::diaInvalido($diaDeEntrega);
        }

        try {
            // Obtener la orden
            $orden = PedidoProduccion::find($orderId);
            if (!$orden) {
                throw CalcularFechaEstimadaException::ordenNoEncontrada($orderId);
            }

            // Validar fecha de creación
            if (!$orden->created_at) {
                throw CalcularFechaEstimadaException::sinFechaCreacion($orderId);
            }

            // Calcular fecha estimada
            $fechaEstimada = $this->_calcularFecha($orden, $diaDeEntrega);

            \Log::info('[CalcularFechaEstimadaUseCase] Fecha estimada calculada', [
                'orden_id' => $orderId,
                'numero_pedido' => $orden->numero_pedido,
                'dias' => $diaDeEntrega,
                'fecha_estimada' => $fechaEstimada->format('d/m/Y')
            ]);

            return [
                'fecha_estimada' => $fechaEstimada->format('d/m/Y'),
                'fecha_estimada_iso' => $fechaEstimada->toIso8601String(),
                'dias' => $diaDeEntrega,
                'fecha_creacion' => $orden->created_at->format('d/m/Y'),
                'orden_id' => $orden->id,
                'numero_pedido' => $orden->numero_pedido
            ];
        } catch (\Exception $e) {
            // Si es excepción personalizada, re-lanzar directamente
            if ($e instanceof CalcularFechaEstimadaException) {
                throw $e;
            }

            \Log::error('[CalcularFechaEstimadaUseCase] Error: ' . $e->getMessage(), [
                'orden_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);

            throw CalcularFechaEstimadaException::errorConsulta($e);
        }
    }

    /**
     * Calcular fecha estimada basada en días
     * 
     * @param PedidoProduccion $orden
     * @param int $diaDeEntrega
     * @return \Carbon\Carbon
     * @throws CalcularFechaEstimadaException
     */
    private function _calcularFecha(PedidoProduccion $orden, int $diaDeEntrega): \Carbon\Carbon
    {
        // Asignar temporalmente el día de entrega para usar el método del modelo
        $orden->dia_de_entrega = $diaDeEntrega;
        
        $fechaEstimada = $orden->calcularFechaEstimada();

        if (!$fechaEstimada) {
            throw CalcularFechaEstimadaException::errorCalculo($orden->id);
        }

        return $fechaEstimada;
    }
}

