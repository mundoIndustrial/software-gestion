<?php

namespace App\Application\Pedidos\Services;

use App\Models\PedidoProduccion;

/**
 * Servicio de Aplicación para generar descripciones de pedidos
 *
 * Responsabilidades:
 * - Generar descripciones detalladas de prendas
 * - Calcular cantidades totales
 * - Centralizar lógica de presentación de pedidos
 *
 * Principios DDD:
 * - Lógica de negocio en capa de aplicación
 * - Modelo permanece como entidad pura
 */
class PedidoDescriptionService
{
    /**
     * Generar descripción completa de todas las prendas de un pedido
     *
     * @param PedidoProduccion $pedido
     * @return string
     */
    public function generatePrendasDescription(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '';
        }

        // Generar descripción detallada para TODAS las prendas
        // (tenga cotización o no)
        $descripciones = $pedido->prendas->map(function($prenda, $index) {
            return $prenda->generarDescripcionDetallada($index + 1);
        })->toArray();

        $resultado = implode("\n\n", $descripciones);

        \Log::info(' [PedidoDescriptionService] Descripción generada:', [
            'numero_pedido' => $pedido->numero_pedido,
            'total_prendas' => count($descripciones),
            'primeros_100_caracteres' => substr($resultado, 0, 100),
        ]);

        return $resultado;
    }

    /**
     * Calcular cantidad total de prendas en un pedido
     *
     * @param PedidoProduccion $pedido
     * @return int
     */
    public function getTotalPrendasQuantity(PedidoProduccion $pedido): int
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($pedido->prendas as $prenda) {
            $total += $prenda->cantidad_total;
        }

        return $total;
    }

    /**
     * Generar descripción resumida de prendas (primeras N prendas)
     *
     * @param PedidoProduccion $pedido
     * @param int $maxPrendas
     * @return string
     */
    public function generatePrendasSummary(PedidoProduccion $pedido, int $maxPrendas = 3): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '';
        }

        $prendas = $pedido->prendas->take($maxPrendas);
        $totalPrendas = $pedido->prendas->count();

        $descripciones = $prendas->map(function($prenda, $index) {
            return $prenda->generarDescripcionDetallada($index + 1);
        })->toArray();

        $resultado = implode("\n\n", $descripciones);

        if ($totalPrendas > $maxPrendas) {
            $resultado .= "\n\n... y " . ($totalPrendas - $maxPrendas) . " prendas más";
        }

        return $resultado;
    }
}