<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Domain\PedidosLogo\Policies\AreasPermitidasPolicy;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\SeguimientoAreaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class GuardarAreaNovedadPedidoLogoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository,
        private SeguimientoAreaRepositoryInterface $seguimientoAreaRepository,
        private AreasPermitidasPolicy $areasPermitidasPolicy
    ) {}

    public function execute(array $payload): array
    {
        $validator = Validator::make($payload, [
            'proceso_prenda_detalle_id' => ['required', 'integer', 'min:1'],
            'area' => ['required', 'string'],
            'novedades' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return [
                'ok' => false,
                'status' => 422,
                'errors' => $validator->errors(),
            ];
        }

        $procesoId = (int) $payload['proceso_prenda_detalle_id'];
        $area = (string) $payload['area'];
        $novedades = isset($payload['novedades']) ? (string) $payload['novedades'] : null;

        $tipoProcesoId = $this->procesoReadRepository->obtenerTipoProcesoIdPorProceso($procesoId);
        if (!$tipoProcesoId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Proceso no encontrado.',
            ];
        }

        $filtro = in_array($tipoProcesoId, [3, 4, 5], true) ? 'estampado' : 'bordado';

        if (!$this->areasPermitidasPolicy->esAreaPermitida($area, $filtro)) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Área no permitida para esta sección.',
            ];
        }

        $prendaPedidoId = $this->procesoReadRepository->obtenerPrendaPedidoIdPorProceso($procesoId);
        if (!$prendaPedidoId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Proceso inválido.',
            ];
        }

        $now = now();
        $timestamp = $now->toDateTimeString();

        DB::transaction(function () use ($procesoId, $prendaPedidoId, $area, $novedades, $timestamp) {
            $existente = $this->seguimientoAreaRepository->obtenerPorProceso($procesoId);

            $fechasAreas = [];
            if ($existente && !empty($existente['fechas_areas'])) {
                $decoded = json_decode((string) $existente['fechas_areas'], true);
                if (is_array($decoded)) {
                    $fechasAreas = $decoded;
                }
            }

            $fechasAreas[$area] = $timestamp;

            $this->seguimientoAreaRepository->upsertSeguimiento(
                $procesoId,
                $prendaPedidoId,
                $area,
                $novedades,
                $fechasAreas,
                $timestamp
            );
        });

        $row = $this->seguimientoAreaRepository->obtenerPorProceso($procesoId);

        $fechasAreas = null;
        $fechaEntrega = null;
        if ($row && !empty($row['fechas_areas'])) {
            $decoded = json_decode((string) $row['fechas_areas'], true);
            if (is_array($decoded)) {
                $fechasAreas = $decoded;
                $fechaEntrega = $decoded['ENTREGADO'] ?? null;
            }
        }

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'success' => true,
                'fechas_areas' => $fechasAreas,
                'fecha_entrega' => $fechaEntrega,
            ],
        ];
    }
}
