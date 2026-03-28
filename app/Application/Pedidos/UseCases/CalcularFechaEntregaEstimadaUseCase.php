<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\CalcularFechaEntregaEstimadaUseCaseContract;

use App\Models\PedidoProduccion;
use App\Services\CalculadorDiasService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CalcularFechaEntregaEstimadaUseCase
 * 
 * Caso de uso para calcular la fecha estimada de entrega basada en días hábiles.
 * 
 * Responsabilidades:
 * - Calcular fecha estimada considerando días hábiles
 * - Considerar festivos colombianos
 * - Guardar la fecha estimada en el pedido
 * 
 * Este UseCase centraliza la lógica de cálculo de fechas que estaba en el frontend.
 */
class CalcularFechaEntregaEstimadaUseCase implements CalcularFechaEntregaEstimadaUseCaseContract
{
    /**
     * Ejecuta el caso de uso
     * 
     * @param int $pedidoId
     * @param int $diasEstimados Número de días hábiles para la entrega
     * @return array Respuesta con fecha estimada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function ejecutar(int $pedidoId, int $diasEstimados): array
    {
        try {
            // Obtener pedido
            $pedido = PedidoProduccion::findOrFail($pedidoId);

            // Usar fecha de creación como base
            $fechaBase = $pedido->created_at ?? now();

            // Calcular fecha estimada agregando días hábiles
            $fechaEstimada = $this->calcularFechaEstimada($fechaBase, $diasEstimados);

            // Actualizar pedido
            $pedido->update([
                'fecha_estimada_de_entrega' => $fechaEstimada
            ]);

            Log::info('[CalcularFechaEntregaEstimadaUseCase] Fecha estimada calculada', [
                'pedido_id' => $pedidoId,
                'fecha_base' => $fechaBase->format('Y-m-d H:i:s'),
                'dias_estimados' => $diasEstimados,
                'fecha_estimada' => $fechaEstimada->format('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'pedido_id' => $pedidoId,
                'fecha_estimada' => $fechaEstimada->format('Y-m-d'),
                'fecha_estimada_formateada' => $this->formatearFecha($fechaEstimada),
                'dias_habiles' => $diasEstimados
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[CalcularFechaEntregaEstimadaUseCase] Pedido no encontrado', [
                'pedido_id' => $pedidoId
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('[CalcularFechaEntregaEstimadaUseCase] Error calculando fecha estimada', [
                'pedido_id' => $pedidoId,
                'dias_estimados' => $diasEstimados,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calcula la fecha estimada de entrega agregando días hábiles
     * 
     * @param Carbon $fechaBase Fecha desde la cual contar
     * @param int $diasEstimados Número de días hábiles a agregar
     * @return Carbon Fecha estimada
     */
    private function calcularFechaEstimada(Carbon $fechaBase, int $diasEstimados): Carbon
    {
        $actual = $fechaBase->copy()->startOfDay();
        $diasContados = 0;

        // Iterar hasta contar los días hábiles requeridos
        while ($diasContados < $diasEstimados) {
            $actual->addDay();

            // Verificar si es día hábil (no fin de semana y no festivo)
            if (!CalculadorDiasService::esFinDeSemana($actual) && 
                !CalculadorDiasService::esFestivo($actual)) {
                $diasContados++;
            }
        }

        return $actual;
    }

    /**
     * Formatea la fecha para visualización
     */
    private function formatearFecha(Carbon $fecha): string
    {
        return $fecha->format('d/m/Y');
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {CalcularFechaEntregaEstimadaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





