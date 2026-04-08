<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Domain\Services\OrderCalculationService;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use Carbon\Carbon;

/**
 * UseCase: Guardar dia de entrega y calcular fecha estimada en recibo
 */
class SaveDiaEntregaUseCase
{
    public function __construct(
        private OrderCalculationService $calculationService,
    ) {}

    /**
     * @param int $numeroPedido ID del pedido
     * @param int|null $diaDeEntrega Dias de entrega (1-35)
     * @param bool $calcularFechaEstimada Si calcular la fecha estimada
     * @param int|null $prendaId ID de la prenda (para actualizar solo esos recibos)
     */
    public function execute(int $numeroPedido, ?int $diaDeEntrega, bool $calcularFechaEstimada = true, ?int $prendaId = null): array
    {
        $pedido = PedidoProduccion::findOrFail($numeroPedido);

        \Log::info('[SaveDiaEntregaUseCase.execute] Pedido encontrado', [
            'numero_pedido_param' => $numeroPedido,
            'pedido_id' => $pedido->id,
            'prenda_id' => $prendaId,
        ]);

        // Si hay prenda_id, solo recibos COSTURA/COSTURA-BODEGA de esa prenda.
        $query = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id);
        if ($prendaId) {
            $query = $query
                ->where('prenda_id', $prendaId)
                ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA']);
        }

        $recibos = $query->get();

        \Log::info('[SaveDiaEntregaUseCase.execute] Busqueda de recibos', [
            'pedido_produccion_id' => $pedido->id,
            'prenda_id' => $prendaId,
            'recibos_encontrados' => $recibos->count(),
        ]);

        if ($recibos->isEmpty()) {
            \Log::error('[SaveDiaEntregaUseCase.execute] No se encontraron recibos', [
                'numero_pedido_param' => $numeroPedido,
                'pedido_id' => $pedido->id,
                'prenda_id' => $prendaId,
            ]);

            throw new \InvalidArgumentException('No se encontraron recibos asociados a este pedido');
        }

        $updateData = [];

        if ($diaDeEntrega !== null) {
            if (!$this->calculationService->validarDiaEntrega($diaDeEntrega)) {
                throw new \InvalidArgumentException('Dia de entrega invalido. Debe estar entre 1 y 35');
            }
            $updateData['dia_de_entrega'] = $diaDeEntrega;
        } else {
            $updateData['dia_de_entrega'] = null;
        }

        if ($calcularFechaEstimada && $diaDeEntrega && $diaDeEntrega > 0) {
            $fechaInicio = $pedido->created_at;
            if ($fechaInicio) {
                $fechaEstimada = $this->calculationService->calcularFechaEstimada(
                    Carbon::parse($fechaInicio),
                    $diaDeEntrega
                );
                $updateData['fecha_estimada_de_entrega'] = $fechaEstimada;
            }
        } elseif (!$diaDeEntrega || $diaDeEntrega === 0) {
            $updateData['fecha_estimada_de_entrega'] = null;
        }

        $fechaEstimadaReciboActualizado = $updateData['fecha_estimada_de_entrega'] ?? null;

        $actualizadosQuery = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id);
        if ($prendaId) {
            $actualizadosQuery = $actualizadosQuery
                ->where('prenda_id', $prendaId)
                ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA']);
        }
        $actualizados = $actualizadosQuery->update($updateData);

        \Log::info('[SaveDiaEntregaUseCase] Dia de entrega actualizado en recibos', [
            'numero_pedido' => $pedido->numero_pedido,
            'pedido_id' => $pedido->id,
            'prenda_id' => $prendaId,
            'recibos_actualizados' => $actualizados,
            'dia_de_entrega' => $diaDeEntrega,
            'update_data' => $updateData,
        ]);

        // Calcular fecha mas lejana (maxima) entre TODOS los recibos del pedido.
        $todoRecibos = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)->get();

        $fechaMaximaEstimada = null;
        $debugFechasRecibos = [];

        foreach ($todoRecibos as $recibo) {
            if (!$recibo->fecha_estimada_de_entrega) {
                continue;
            }

            $fechaRecibo = Carbon::parse($recibo->fecha_estimada_de_entrega);
            $debugFechasRecibos[] = [
                'recibo_id' => $recibo->id,
                'tipo_recibo' => $recibo->tipo_recibo,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'prenda_id' => $recibo->prenda_id,
                'fecha_estimada_de_entrega' => $fechaRecibo->format('Y-m-d'),
            ];

            if (!$fechaMaximaEstimada || $fechaRecibo->isAfter($fechaMaximaEstimada)) {
                $fechaMaximaEstimada = $fechaRecibo;
            }
        }

        \Log::info('[SaveDiaEntregaUseCase] Calculo fecha maxima por recibos', [
            'pedido_id' => $pedido->id,
            'prenda_id_actualizada' => $prendaId,
            'fechas_recibos' => $debugFechasRecibos,
            'fecha_maxima_estimada_resultado' => $fechaMaximaEstimada?->format('Y-m-d'),
        ]);

        // IMPORTANTE: no tocar dia_de_entrega del pedido en updates por prenda.
        $pedidoUpdateData = [];
        if ($fechaMaximaEstimada) {
            $pedidoUpdateData['fecha_estimada_de_entrega'] = $fechaMaximaEstimada;
        }

        if (!empty($pedidoUpdateData)) {
            $pedido->update($pedidoUpdateData);
            \Log::info('[SaveDiaEntregaUseCase] Pedido actualizado con fecha mas lejana', [
                'pedido_id' => $pedido->id,
                'pedido_update_data' => $pedidoUpdateData,
                'fecha_estimada_de_entrega' => $fechaMaximaEstimada?->format('Y-m-d'),
            ]);
        }

        $recibosActualizados = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)->get();

        return [
            'success' => true,
            'message' => "Dia de entrega actualizado en {$actualizados} recibos",
            'data' => [
                'numero_pedido' => $pedido->numero_pedido,
                'dia_de_entrega' => $diaDeEntrega,
                'fecha_estimada_recibo_actualizado' => $fechaEstimadaReciboActualizado
                    ? Carbon::parse($fechaEstimadaReciboActualizado)->format('Y-m-d')
                    : null,
                'fecha_estimada_de_entrega' => $fechaMaximaEstimada ? $fechaMaximaEstimada->format('Y-m-d') : null,
                'recibos_actualizados' => $actualizados,
                'recibos' => $recibosActualizados->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'consecutivo_actual' => $r->consecutivo_actual,
                        'dia_de_entrega' => $r->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $r->fecha_estimada_de_entrega,
                    ];
                })->toArray(),
            ],
        ];
    }
}
