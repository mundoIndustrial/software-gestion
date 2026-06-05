<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Application\PedidosLogo\Services\DisenoLogoBroadcastService;
use App\Application\Services\ImageUploadService;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Models\DisenoLogoPedido;
use App\Models\DisenoLogoPedidoNovedad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

final class ReemplazarDisenoLogoPedidoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private DisenoLogoPedidoRepositoryInterface $disenoRepository,
        private ImageUploadService $imageUploadService,
        private TransactionManagerInterface $transactionManager,
        private DisenoLogoBroadcastService $broadcastService,
    ) {}

    public function execute(Request $request, int $disenoId): array
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $response = null;

        if ($validator->fails()) {
            $response = [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        } else {
            $diseno = $this->disenoRepository->findById($disenoId);
            if (!$diseno) {
                $response = [
                    'ok' => false,
                    'status' => 404,
                    'message' => 'Diseño no encontrado.',
                ];
            } else {
                $pedidoProduccionId = $this->procesoReadRepository->obtenerPedidoProduccionIdPorProceso($diseno->proceso_prenda_detalle_id);
                if (!$pedidoProduccionId) {
                    $response = [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'No se encontró el pedido asociado.',
                    ];
                } else {
                    $estadoAnterior = (string) $diseno->estado;

                    $this->transactionManager->run(function () use ($request, $disenoId, $pedidoProduccionId, $diseno): void {
                        $file = $request->file('image');
                        $paths = $this->imageUploadService->guardarImagenDirecta($file, $pedidoProduccionId, 'diseños-logo');
                        $url = Storage::url($paths['webp']);
                        
                        // Update the design
                        $this->disenoRepository->actualizar($disenoId, $url);

                        // Create novedad in the new historial table
                        $user = Auth::user();
                        $userName = $user?->name ?? 'Usuario Desconocido';

                        DisenoLogoPedidoNovedad::create([
                            'diseno_logo_pedido_id' => $disenoId,
                            'novedad' => "la imagen del diseño aneterior fue reemplazada por la actual por el  {$userName}.",
                            'usuario_id' => $user?->id,
                            'tipo_novedad' => 'reemplazo_imagen',
                        ]);
                    });

                    $disenoActualizado = DisenoLogoPedido::query()->find($disenoId);
                    if ($disenoActualizado) {
                        $this->broadcastService->emit('reemplazado', $disenoActualizado, $estadoAnterior);
                    }

                    $response = [
                        'ok' => true,
                        'status' => 200,
                        'data' => [
                            'success' => true,
                            'message' => 'Imagen reemplazada exitosamente.',
                        ],
                    ];
                }
            }
        }

        return $response;
    }
}
