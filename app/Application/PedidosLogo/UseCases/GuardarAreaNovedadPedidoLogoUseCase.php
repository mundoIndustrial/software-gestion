<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\PedidosLogo\Policies\AreasPermitidasPolicy;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\SeguimientoAreaRepositoryInterface;
use Illuminate\Support\Facades\Validator;

final class GuardarAreaNovedadPedidoLogoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private SeguimientoAreaRepositoryInterface $seguimientoAreaRepository,
        private AreasPermitidasPolicy $areasPermitidasPolicy,
        private TransactionManagerInterface $transactionManager
    ) {}

    public function execute(array $payload): array
    {
        \Log::info('GuardarAreaNovedadPedidoLogoUseCase - Payload recibido:', $payload);
        
        $validator = Validator::make($payload, [
            'proceso_prenda_detalle_id' => ['required', 'integer', 'min:1'],
            'area' => ['required', 'string'],
            'novedades' => ['nullable', 'string'],
            'pedido_parcial_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $response = null;

        if ($validator->fails()) {
            $response = [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        } else {
            $procesoId = (int) $payload['proceso_prenda_detalle_id'];
            $area = (string) $payload['area'];
            $novedades = isset($payload['novedades']) ? (string) $payload['novedades'] : null;
            $pedidoParcialId = isset($payload['pedido_parcial_id']) ? (int) $payload['pedido_parcial_id'] : null;

            \Log::info('GuardarAreaNovedadPedidoLogoUseCase - Datos extraídos:', [
                'procesoId' => $procesoId,
                'area' => $area,
                'novedades' => $novedades,
                'pedidoParcialId' => $pedidoParcialId,
            ]);

            $validacion = $this->validarContextoProceso($procesoId, $area);
            if ($validacion['error'] !== null) {
                $response = $validacion['error'];
            } else {
                $prendaPedidoId = (int) $validacion['prenda_pedido_id'];
                $timestamp = now()->toDateTimeString();

                $this->transactionManager->run(function () use ($procesoId, $prendaPedidoId, $area, $novedades, $pedidoParcialId, $timestamp): void {
                    $existente = $this->seguimientoAreaRepository->obtenerPorProceso($procesoId);
                    $fechasAreas = $this->extraerFechasAreas($existente);
                    $fechasAreas[$area] = $timestamp;

                    \Log::info('GuardarAreaNovedadPedidoLogoUseCase - Llamando upsertSeguimiento con:', [
                        'procesoId' => $procesoId,
                        'prendaPedidoId' => $prendaPedidoId,
                        'area' => $area,
                        'pedidoParcialId' => $pedidoParcialId,
                    ]);

                    $this->seguimientoAreaRepository->upsertSeguimiento(
                        $procesoId,
                        $prendaPedidoId,
                        $area,
                        $novedades,
                        $fechasAreas,
                        $timestamp,
                        $pedidoParcialId
                    );
                });

                $row = $this->seguimientoAreaRepository->obtenerPorProceso($procesoId);
                $fechasAreas = $this->extraerFechasAreas($row);

                $response = [
                    'ok' => true,
                    'status' => 200,
                    'data' => [
                        'success' => true,
                        'fechas_areas' => !empty($fechasAreas) ? $fechasAreas : null,
                        'fecha_entrega' => $fechasAreas['ENTREGADO'] ?? null,
                    ],
                ];
            }
        }

        return $response;
    }

    /**
     * @return array{error:?array, prenda_pedido_id:?int}
     */
    private function validarContextoProceso(int $procesoId, string $area): array
    {
        $error = null;
        $prendaPedidoId = null;

        $tipoProcesoId = $this->procesoReadRepository->obtenerTipoProcesoIdPorProceso($procesoId);
        if (!$tipoProcesoId) {
            $error = [
                'ok' => false,
                'status' => 422,
                'message' => 'Proceso no encontrado.',
            ];
        } else {
            $filtro = in_array($tipoProcesoId, [3, 4, 5], true) ? 'estampado' : 'bordado';
            if (!$this->areasPermitidasPolicy->esAreaPermitida($area, $filtro)) {
                $error = [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Area no permitida para esta seccion.',
                ];
            } else {
                $prendaPedidoId = $this->procesoReadRepository->obtenerPrendaPedidoIdPorProceso($procesoId);
                if (!$prendaPedidoId) {
                    $error = [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Proceso invalido.',
                    ];
                }
            }
        }

        return [
            'error' => $error,
            'prenda_pedido_id' => $prendaPedidoId !== null ? (int) $prendaPedidoId : null,
        ];
    }

    private function extraerFechasAreas(?array $row): array
    {
        if (!$row || empty($row['fechas_areas'])) {
            return [];
        }

        $decoded = json_decode((string) $row['fechas_areas'], true);

        return is_array($decoded) ? $decoded : [];
    }
}
