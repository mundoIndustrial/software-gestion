<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use Illuminate\Support\Facades\Validator;

final class ListDisenosLogoPedidoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private DisenoLogoPedidoRepositoryInterface $disenoRepository
    ) {}

    public function execute(array $payload): array
    {
        $validator = Validator::make($payload, [
            'pedido_id' => ['required', 'integer', 'min:1'],
            'proceso_prenda_detalle_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        }

        $pedidoId = (int) $payload['pedido_id'];
        $procesoId = (int) $payload['proceso_prenda_detalle_id'];

        $pedidoProduccionId = $this->procesoReadRepository->obtenerPedidoProduccionIdPorProceso($procesoId);
        if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'El recibo no pertenece al pedido indicado.',
            ];
        }

        $items = $this->disenoRepository->listarPorProceso($procesoId);

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'success' => true,
                'data' => [
                    'items' => $items->values(),
                ],
            ],
        ];
    }
}
