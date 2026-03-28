<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Domain\Services\OrderCalculationService;
use Carbon\Carbon;

/**
 * UseCase: Guardar día de entrega y calcular fecha estimada en recibo
 * 
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
     * 
     * @param int $numeroPedido Número de pedido o ID del pedido
     * @param int|null $diaDeEntrega Días de entrega (1-35)
     * @param bool $calcularFechaEstimada Si calcular la fecha estimada
     * @return array
     */
    public function execute(int $numeroPedido, ?int $diaDeEntrega, bool $calcularFechaEstimada = true): array
    {
        // Obtener el pedido - buscar por número o ID
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->orWhere('id', $numeroPedido)
            ->firstOrFail();

        // Obtener todos los recibos de costura asociados a este pedido
        $recibos = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
            ->where('tipo_recibo', 'COSTURA')
            ->get();

        if ($recibos->isEmpty()) {
            throw new \InvalidArgumentException(
                'No se encontraron recibos de costura para este pedido'
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

        // Calcular fecha estimada
        if ($calcularFechaEstimada && $diaDeEntrega && $diaDeEntrega > 0) {
            $fechaInicio = $pedido->created_at ?? $pedido->created_at;
            
            if ($fechaInicio) {
                $fechaEstimada = $this->calculationService->calcularFechaEstimada(
                    Carbon::parse($fechaInicio),
                    $diaDeEntrega
                );
                $updateData['fecha_estimada_de_entrega'] = $fechaEstimada;
            }
        } else if (!$diaDeEntrega || $diaDeEntrega == 0) {
            $updateData['fecha_estimada_de_entrega'] = null;
        }

        // Actualizar todos los recibos
        $actualizados = 0;
        foreach ($recibos as $recibo) {
            $recibo->update($updateData);
            $actualizados++;
        }

        // Log
        \Log::info('[SaveDiaEntregaUseCase] Día de entrega actualizado en recibos', [
            'numero_pedido' => $pedido->numero_pedido,
            'pedido_id' => $pedido->id,
            'recibos_actualizados' => $actualizados,
            'dia_de_entrega' => $diaDeEntrega,
            'fecha_estimada_de_entrega' => $updateData['fecha_estimada_de_entrega'] ?? null,
            'usuario_id' => auth()->id()
        ]);

        // Recargar para obtener datos actualizados
        $recibos->each->refresh();

        return [
            'success' => true,
            'message' => "Día de entrega actualizado en {$actualizados} recibos",
            'data' => [
                'numero_pedido' => $pedido->numero_pedido,
                'dia_de_entrega' => $diaDeEntrega,
                'fecha_estimada_de_entrega' => $updateData['fecha_estimada_de_entrega'] ?? null,
                'recibos_actualizados' => $actualizados,
                'recibos' => $recibos->map(function($r) {
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


