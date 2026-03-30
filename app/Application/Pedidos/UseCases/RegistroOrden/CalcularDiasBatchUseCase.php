<?php

namespace App\Application\Pedidos\UseCases\RegistroOrden;

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use App\Exceptions\CalcularDiasBatchException;

/**
 * CalcularDiasBatchUseCase
 * UseCase: Calcular días de múltiples órdenes
 * Capa: Application
 * Responsabilidad: Orquestar el cálculo de días hábiles para un lote de pedidos
 * Nota: Las excepciones son manejadas por el Handler que renderiza
 * respuestas JSON automáticamente. El UseCase solo lanza excepciones.
 */
class CalcularDiasBatchUseCase
{
    /**
     * Ejecutar cálculo batch de días
     * @param array $numeroPedidos
     * @return array ['dias' => array, 'total' => int, 'timestamp' => string]
     * @throws CalcularDiasBatchException
     */
    public function execute(array $numeroPedidos): array
    {
        // Validar entrada
        if (empty($numeroPedidos)) {
            throw CalcularDiasBatchException::listaPedidosVacia();
        }

        try {
            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Obtener todas las órdenes
            $ordenes = PedidoProduccion::whereIn('numero_pedido', $numeroPedidos)->get();
            if ($ordenes->isEmpty()) {
                throw CalcularDiasBatchException::ordenesNoEncontradas($numeroPedidos);
            }

            // Calcular días para todas
            $resultados = CacheCalculosService::getTotalDiasBatch($ordenes->toArray(), $festivos);

            // Formatear respuesta
            $dias = [];
            foreach ($numeroPedidos as $pedido) {
                $dias[$pedido] = intval($resultados[$pedido] ?? 0);
            }

            \Log::info('[CalcularDiasBatchUseCase] Cálculo exitoso', [
                'pedidos_procesados' => count($dias),
                'timestamp' => now()->toIso8601String()
            ]);

            return [
                'dias' => $dias,
                'total' => count($dias),
                'timestamp' => now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            // Si es excepción personalizada, re-lanzar directamente
            if ($e instanceof CalcularDiasBatchException) {
                throw $e;
            }

            \Log::error('[CalcularDiasBatchUseCase] Error: ' . $e->getMessage(), [
                'pedidos' => count($numeroPedidos),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw CalcularDiasBatchException::errorCalculo($e);
        }
    }
}

