<?php

namespace App\Application\Services;

use App\Repositories\PrendaPedidoTallaRepository;

/**
 * CantidadCalculator
 * 
 * Responsabilidad: Calcular cantidad total de una prenda
 * Soporta dos flujos: talla-color y normal
 */
class CantidadCalculator
{
    public function __construct(
        private PrendaPedidoTallaRepository $prendaTallaRepository
    ) {}

    /**
     * Calcular cantidad total de un recibo específico
     *
     * @param array $recibo Datos del recibo (con prenda_id y consecutivo_actual)
     * @return int Cantidad total del recibo
     */
    public function calcular(array $recibo): int
    {
        if (empty($recibo['prenda_id'])) {
            return 0;
        }

        $prendaId = (int) $recibo['prenda_id'];
        $pedidoProduccionId = isset($recibo['pedido_produccion_id']) ? (int) $recibo['pedido_produccion_id'] : null;
        $tipoRecibo = isset($recibo['tipo_recibo']) ? (string) $recibo['tipo_recibo'] : null;
        $numeroRecibo = $recibo['consecutivo_actual'] ?? null;

        if ($numeroRecibo !== null) {
            return $this->prendaTallaRepository->calcularCantidadPorRecibo(
                prendaPedidoId: $prendaId,
                numeroRecibo: $numeroRecibo,
                pedidoProduccionId: $pedidoProduccionId,
                tipoRecibo: $tipoRecibo
            );
        }

        return $this->prendaTallaRepository->calcularCantidadTotalPrenda($prendaId);
    }

    /**
     * Calcular cantidades para varios recibos en bloque.
     *
     * @param array $recibos
     * @return array<string, int> Mapa por key: pedido|prenda|tipo|consecutivo
     */
    public function calcularMasivo(array $recibos): array
    {
        return $this->prendaTallaRepository->calcularCantidadesPorRecibos($recibos);
    }
}
