<?php

namespace App\Application\UseCases\RegistroOrden;

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use App\Exceptions\CalcularDiasException;

/**
 * CalcularDiasUseCase
 * 
 * UseCase: Calcular días de una orden específica
 * Capa: Application
 * Responsabilidad: Orquestar la lógica de cálculo de días hábiles para un pedido
 */
class CalcularDiasUseCase
{
    /**
     * Ejecutar cálculo de días para una orden
     * 
     * @param string $numeroPedido
     * @return array ['success' => bool, 'data' => mixed, 'message' => string]
     * @throws CalcularDiasException
     */
    public function execute(string $numeroPedido): array
    {
        try {
            if (empty($numeroPedido)) {
                throw CalcularDiasException::numeroPedidoInvalido();
            }

            $festivos = Festivo::pluck('fecha')->toArray();
            
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if (!$orden) {
                throw CalcularDiasException::ordenNoEncontrada($numeroPedido);
            }

            $resultado = CacheCalculosService::getTotalDiasBatch([$orden], $festivos);
            $diasCalculados = $resultado[$numeroPedido] ?? 0;

            return [
                'success' => true,
                'data' => [
                    'numero_pedido' => $numeroPedido,
                    'total_dias' => intval($diasCalculados),
                    'timestamp' => now()->toIso8601String()
                ],
                'message' => 'Días calculados exitosamente'
            ];
        } catch (CalcularDiasException $e) {
     
            
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado en CalcularDiasUseCase: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            throw CalcularDiasException::errorCalculo($e);
        }
    }
}
