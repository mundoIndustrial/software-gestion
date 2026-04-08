<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Domain\Services\OrderCalculationService;
use Carbon\Carbon;

/**
 * UseCase: Guardar día de entrega y calcular fecha estimada en recibo
 * Responsabilidades:
 * - Validar día de entrega
 * - Calcular fecha estimada con días hábiles
 * - Actualizar recibo (consecutivos_recibos_pedidos)
 * - Guardar auditoría
 */
class SaveDiaEntregaUseCase
{
    public function __construct(
        private OrderCalculationService $calculationService,
    ) {}

    /**
     * Ejecutar el caso de uso
     * @param int $numeroPedido ID del pedido
     * @param int|null $diaDeEntrega Días de entrega (1-35)
     * @param bool $calcularFechaEstimada Si calcular la fecha estimada
     * @param int|null $prendaId ID de la prenda (para actualizar solo esos recibos)
     * @return array
     */
    public function execute(int $numeroPedido, ?int $diaDeEntrega, bool $calcularFechaEstimada = true, ?int $prendaId = null): array
    {
        // Obtener el pedido solo por ID
        $pedido = PedidoProduccion::findOrFail($numeroPedido);

        \Log::info('[SaveDiaEntregaUseCase.execute] Pedido encontrado', [
            'numero_pedido_param' => $numeroPedido,
            'pedido_id' => $pedido->id,
            'prenda_id' => $prendaId
        ]);

        // Buscar recibos: si hay prenda_id, solo de esa prenda; si no, todos del pedido
        $query = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id);
        
        if ($prendaId) {
            $query = $query->where('prenda_id', $prendaId);
        }
        
        $recibos = $query->get();

        \Log::info('[SaveDiaEntregaUseCase.execute] Búsqueda de recibos', [
            'pedido_produccion_id' => $pedido->id,
            'prenda_id' => $prendaId,
            'recibos_encontrados' => $recibos->count()
        ]);

        if ($recibos->isEmpty()) {
            \Log::error('[SaveDiaEntregaUseCase.execute] No se encontraron recibos', [
                'numero_pedido_param' => $numeroPedido,
                'pedido_id' => $pedido->id,
                'prenda_id' => $prendaId
            ]);
            throw new \InvalidArgumentException(
                'No se encontraron recibos asociados a este pedido'
            );
        }

        // Preparar datos para actualizar
        $updateData = [];
        
        if ($diaDeEntrega !== null) {
            // Validar
            if (!$this->calculationService->validarDiaEntrega($diaDeEntrega)) {
                throw new \InvalidArgumentException(
                    'Día de entrega inválido. Debe estar entre 1 y 35'
                );
            }
            $updateData['dia_de_entrega'] = $diaDeEntrega;
        } else {
            $updateData['dia_de_entrega'] = null;
        }

        // Calcular fecha estimada para estos recibos específicos
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

        // Actualizar solo los recibos encontrados (de la prenda específica o todos)
        $actualizados = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id);
        
        if ($prendaId) {
            $actualizados = $actualizados->where('prenda_id', $prendaId);
        }
        
        $actualizados = $actualizados->update($updateData);

        // Log
        \Log::info('[SaveDiaEntregaUseCase] Día de entrega actualizado en recibos', [
            'numero_pedido' => $pedido->numero_pedido,
            'pedido_id' => $pedido->id,
            'prenda_id' => $prendaId,
            'recibos_actualizados' => $actualizados,
            'dia_de_entrega' => $diaDeEntrega
        ]);

        // **IMPORTANTE**: Calcular la fecha más lejana de TODOS los recibos del pedido
        $todoRecibos = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
            ->get();

        $fechaMaximaEstimada = null;
        foreach ($todoRecibos as $recibo) {
            if ($recibo->fecha_estimada_de_entrega) {
                $fechaRecibo = Carbon::parse($recibo->fecha_estimada_de_entrega);
                if (!$fechaMaximaEstimada || $fechaRecibo->isBefore($fechaMaximaEstimada)) {
                    $fechaMaximaEstimada = $fechaRecibo;
                }
            }
        }

        // Actualizar la fecha general del pedido con la fecha más lejana
        $pedidoUpdateData = [];
        if ($prendaId) {
            // Solo actualizamos dias_de_entrega del primer recibo (la lógica es que sea genérico)
            if ($diaDeEntrega !== null) {
                $pedidoUpdateData['dia_de_entrega'] = $diaDeEntrega;
            }
        }
        if ($fechaMaximaEstimada) {
            $pedidoUpdateData['fecha_estimada_de_entrega'] = $fechaMaximaEstimada;
        }

        if (!empty($pedidoUpdateData)) {
            $pedido->update($pedidoUpdateData);
            \Log::info('[SaveDiaEntregaUseCase] Pedido actualizado con fecha más lejana', [
                'pedido_id' => $pedido->id,
                'fecha_estimada_de_entrega' => $fechaMaximaEstimada
            ]);
        }

        // Recargar para obtener datos actualizados
        $recibosActualizados = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
            ->get();

        return [
            'success' => true,
            'message' => "Día de entrega actualizado en {$actualizados} recibos",
            'data' => [
                'numero_pedido' => $pedido->numero_pedido,
                'dia_de_entrega' => $diaDeEntrega,
                'fecha_estimada_de_entrega' => $fechaMaximaEstimada ? $fechaMaximaEstimada->format('Y-m-d') : null,
                'recibos_actualizados' => $actualizados,
                'recibos' => $recibosActualizados->map(function($r) {
                    return [
                        'id' => $r->id,
                        'consecutivo_actual' => $r->consecutivo_actual,
                        'dia_de_entrega' => $r->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $r->fecha_estimada_de_entrega,
                    ];
                })->toArray()
            ]
        ];
    }
}

