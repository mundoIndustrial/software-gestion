<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Application\Services\ImageUploadService;
use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

final class UploadDisenosLogoPedidoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private DisenoLogoPedidoRepositoryInterface $disenoRepository,
        private ImageUploadService $imageUploadService
    ) {}

    public function execute(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => 'required|integer|min:1',
            'proceso_prenda_detalle_id' => 'required|integer|min:1',
            'images' => 'required|array|min:1|max:3',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        }

        $pedidoId = (int) $request->input('pedido_id');
        $procesoId = (int) $request->input('proceso_prenda_detalle_id');

        $pedidoProduccionId = $this->procesoReadRepository->obtenerPedidoProduccionIdPorProceso($procesoId);
        if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'El recibo no pertenece al pedido indicado.',
            ];
        }

        $existingCount = $this->disenoRepository->contarPorProceso($procesoId);
        $incomingCount = count($request->file('images') ?? []);

        if ($existingCount + $incomingCount > 3) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Máximo 3 imágenes por recibo.',
            ];
        }

        $records = [];

        DB::beginTransaction();
        try {
            foreach (($request->file('images') ?? []) as $file) {
                $paths = $this->imageUploadService->guardarImagenDirecta($file, $pedidoId, 'diseños-logo');
                $url = Storage::url($paths['webp']);
                $records[] = $this->disenoRepository->crear($procesoId, $url);
            }

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
                'data' => [
                    'items' => $records,
                ],
            ],
        ];
    }
}
