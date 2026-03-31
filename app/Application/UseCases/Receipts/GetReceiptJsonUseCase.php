<?php

namespace App\Application\UseCases\Receipts;

use App\Application\Services\WorkingDaysCalculator;
use App\Models\ReciboPorPartes;
use App\Repositories\ConsecutivoReciboPedidoRepository;

/**
 * UseCase: Obtener datos de un recibo como JSON
 *
 * Responsabilidades:
 * - Buscar el recibo por ID y tipo (COSTURA, REFLECTIVO, etc.)
 * - Calcular días hábiles desde la fecha de creación de la orden
 * - Devolver el array de datos listo para serializar
 */
class GetReceiptJsonUseCase
{
    public function __construct(
        private ConsecutivoReciboPedidoRepository $reciboRepository,
        private WorkingDaysCalculator $workingDays,
    ) {}

    /**
     * @param int    $reciboId
     * @param string $tipoRecibo  'COSTURA' | 'REFLECTIVO'
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  nunca lanza; devuelve null en not found
     */
    public function execute(int $reciboId, string $tipoRecibo): ?array
    {
        $recibo = $this->reciboRepository->findByIdAndTipo($reciboId, $tipoRecibo);

        if (!$recibo) {
            return null;
        }

        $pedido = $recibo->pedido;

        $diasCalculados = 0;
        if ($pedido && $pedido->created_at) {
            $diasCalculados = $this->workingDays->desdeInicio($pedido->created_at);
        }

        $nombrePrenda = 'Sin prendas';
        if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
            $primeraPrenda = $pedido->prendas->first();
            $nombrePrenda = $primeraPrenda->nombre_prenda ?? $primeraPrenda->nombre ?? 'Prenda';
        }

        $totalParciales = ReciboPorPartes::query()
            ->where('pedido_produccion_id', $recibo->pedido_produccion_id)
            ->where('prenda_pedido_id', $recibo->prenda_id)
            ->where('tipo_recibo', $recibo->tipo_recibo)
            ->where('consecutivo_original', $recibo->consecutivo_actual)
            ->count();

        return [
            'id'                   => $recibo->id,
            'consecutivo_actual'   => $recibo->consecutivo_actual,
            'pedido_produccion_id' => $recibo->pedido_produccion_id,
            'prenda_id'            => $recibo->prenda_id,
            'tipo_recibo'          => $recibo->tipo_recibo,
            'estado'               => $recibo->estado ?? 'PENDIENTE_INSUMOS',
            'area'                 => $recibo->area ?? 'Insumos',
            'dias_calculados'      => $diasCalculados,
            'nombre_prenda'        => $nombrePrenda,
            'cliente'              => $pedido ? $pedido->cliente : '',
            'numero_pedido'        => $pedido ? $pedido->numero_pedido : '',
            'fecha_creacion'       => $pedido && $pedido->created_at
                                        ? $pedido->created_at->format('d/m/Y')
                                        : '-',
            'created_at'           => $recibo->created_at,
            'tiene_parciales'      => $totalParciales > 0,
            'total_parciales'      => $totalParciales,
        ];
    }
}
