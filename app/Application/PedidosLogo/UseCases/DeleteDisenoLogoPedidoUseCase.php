<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\LogoDesignStorageInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class DeleteDisenoLogoPedidoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private DisenoLogoPedidoRepositoryInterface $disenoRepository,
        private LogoDesignStorageInterface $storage
    ) {}

    public function execute(int $disenoId, array $payload): array
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

        $diseno = $this->disenoRepository->findById($disenoId);
        if (!$diseno) {
            return [
                'ok' => false,
                'status' => 404,
                'message' => 'Diseño no encontrado.',
            ];
        }

        if ((int) $diseno->proceso_prenda_detalle_id !== $procesoId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'El diseño no pertenece al recibo indicado.',
            ];
        }

        $pedidoProduccionId = $this->procesoReadRepository->obtenerPedidoProduccionIdPorProceso($procesoId);
        if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'El recibo no pertenece al pedido indicado.',
            ];
        }

        DB::beginTransaction();
        try {
            $this->storage->deleteByUrl((string) $diseno->url);
            $this->disenoRepository->eliminarPorId($disenoId);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'success' => true,
            ],
        ];
    }
}
