<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Application\Services\ImageUploadService;
use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

final class UploadDisenosLogoPedidoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private DisenoLogoPedidoRepositoryInterface $disenoRepository,
        private ImageUploadService $imageUploadService,
        private TransactionManagerInterface $transactionManager
    ) {}

    public function execute(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => 'required|integer|min:1',
            'proceso_prenda_detalle_id' => 'required|integer|min:1',
            'images' => 'required|array|min:1|max:3',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $response = null;

        if ($validator->fails()) {
            $response = [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        } else {
            $pedidoId = (int) $request->input('pedido_id');
            $procesoId = (int) $request->input('proceso_prenda_detalle_id');

            $pedidoProduccionId = $this->procesoReadRepository->obtenerPedidoProduccionIdPorProceso($procesoId);
            if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
                $response = [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'El recibo no pertenece al pedido indicado.',
                ];
            } else {
                $existingCount = $this->disenoRepository->contarPorProceso($procesoId);
                $incomingCount = count($request->file('images') ?? []);

                if ($existingCount + $incomingCount > 3) {
                    $response = [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Máximo 3 imágenes por recibo.',
                    ];
                } else {
                    $records = [];

                    $this->transactionManager->run(function () use ($request, $pedidoId, $procesoId, &$records): void {
                        foreach (($request->file('images') ?? []) as $file) {
                            $paths = $this->imageUploadService->guardarImagenDirecta($file, $pedidoId, 'diseños-logo');
                            $url = Storage::url($paths['webp']);
                            $records[] = $this->disenoRepository->crear($procesoId, $url);
                        }
                    });

                    $response = [
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
        }

        return $response;
    }
}
