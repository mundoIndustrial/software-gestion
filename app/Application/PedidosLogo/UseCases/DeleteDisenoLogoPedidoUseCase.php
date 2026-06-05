<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Application\PedidosLogo\Services\DisenoLogoBroadcastService;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Models\DisenoLogoPedido;
use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\LogoDesignStorageInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use Illuminate\Support\Facades\Validator;

final class DeleteDisenoLogoPedidoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private DisenoLogoPedidoRepositoryInterface $disenoRepository,
        private LogoDesignStorageInterface $storage,
        private TransactionManagerInterface $transactionManager,
        private DisenoLogoBroadcastService $broadcastService,
    ) {}

    public function execute(int $disenoId, array $payload): array
    {
        $validator = Validator::make($payload, [
            'pedido_id' => ['required', 'integer', 'min:1'],
            'proceso_prenda_detalle_id' => ['required', 'integer', 'min:1'],
        ]);

        $response = null;

        if ($validator->fails()) {
            $response = [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        } else {
            $pedidoId = (int) $payload['pedido_id'];
            $procesoId = (int) $payload['proceso_prenda_detalle_id'];

            $diseno = $this->disenoRepository->findById($disenoId);
            if (!$diseno) {
                $response = [
                    'ok' => false,
                    'status' => 404,
                    'message' => 'Diseño no encontrado.',
                ];
            } elseif ((int) $diseno->proceso_prenda_detalle_id !== $procesoId) {
                $response = [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'El diseño no pertenece al recibo indicado.',
                ];
            } else {
                $pedidoProduccionId = $this->procesoReadRepository->obtenerPedidoProduccionIdPorProceso($procesoId);
                if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
                    $response = [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'El recibo no pertenece al pedido indicado.',
                    ];
                } else {
                    $disenoModel = DisenoLogoPedido::query()->find($disenoId);
                    $snapshot = $disenoModel
                        ? $this->broadcastService->snapshotFromModel($disenoModel)
                        : null;

                    $this->transactionManager->run(function () use ($diseno, $disenoId): void {
                        $this->storage->deleteByUrl((string) $diseno->url);
                        $this->disenoRepository->eliminarPorId($disenoId);
                    });

                    if ($snapshot) {
                        $this->broadcastService->emit('eliminado', $snapshot, $snapshot['estado'] ?? null);
                    }

                    $response = [
                        'ok' => true,
                        'status' => 200,
                        'data' => [
                            'success' => true,
                        ],
                    ];
                }
            }
        }

        return $response;
    }
}
