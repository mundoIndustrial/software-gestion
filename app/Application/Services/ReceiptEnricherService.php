<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;

/**
 * ReceiptEnricherService
 * 
 * Responsabilidad: Enriquecer recibos con información adicional
 * Orquesta cálculos y transformaciones de datos
 */
class ReceiptEnricherService
{
    public function __construct(
        private DiaLaboralCalculator $diaLaboralCalculator,
        private CantidadCalculator $cantidadCalculator
    ) {}

    /**
     * Enriquecer recibos con información de pedidos y cálculos
     * 
     * @param array $recibos Array de recibos sin enriquecer
     * @return array Recibos enriquecidos
     */
    public function enriquecer(array $recibos): array
    {
        return array_map(function($recibo) {
            $pedido = PedidoProduccion::with([
                'prendas.coloresTelas.tela',
                'prendas.coloresTelas.color',
                'prendas.tallas'
            ])->find($recibo['pedido_produccion_id']);

            return array_merge($recibo, [
                'pedido_info' => $pedido ? $this->extraerInfoPedido($pedido) : null,
                'descripcion_detallada' => $this->generarDescripcion($pedido, $recibo),
                'dias_calculados' => $pedido ? $this->diaLaboralCalculator->calcular($pedido->fecha_de_creacion_de_orden) : 0,
                'cantidad_total' => $this->cantidadCalculator->calcular($recibo),
            ]);
        }, $recibos);
    }

    /**
     * Extraer información relevante del pedido
     * 
     * @param PedidoProduccion $pedido
     * @return array
     */
    private function extraerInfoPedido(PedidoProduccion $pedido): array
    {
        return [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'estado' => $pedido->estado,
            'area' => $pedido->area,
            'dia_de_entrega' => $pedido->dia_de_entrega,
            'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega?->format('d/m/Y'),
            'fecha_creacion_orden' => $pedido->fecha_de_creacion_de_orden?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generar descripción detallada del recibo
     * 
     * @param PedidoProduccion|null $pedido
     * @param array $recibo
     * @return string
     */
    private function generarDescripcion($pedido, $recibo): string
    {
        if (!$pedido || !isset($recibo['prenda_id'])) {
            return '';
        }

        $prenda = $pedido->prendas->where('id', $recibo['prenda_id'])->first();
        if (!$prenda) {
            return '';
        }

        $desc = "PRENDA: " . $prenda->nombre_prenda;

        if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
            $tela = $prenda->coloresTelas->first();
            $desc .= " | TELA: " . ($tela->tela->nombre ?? 'Sin tela');
            $desc .= " | COLOR: " . ($tela->color->nombre ?? 'Sin color');
        }

        return $desc;
    }
}
