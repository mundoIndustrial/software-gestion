<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\Services\PedidoCreationCoordinator;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * Encapsula mutaciones operativas sobre borradores de pedidos.
 */
class PedidoDraftMutationService
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoRepository,
        private EppImageCleanupService $eppImageCleanupService,
        private PedidoImagenesService $pedidoImagenesService,
        private PedidoCreationCoordinator $pedidoCreationCoordinator,
    ) {}

    public function actualizarEpps(int $pedidoId, array $eppsCrudos, $request): void
    {
        if (empty($eppsCrudos)) {
            return;
        }

        foreach ($eppsCrudos as $eppData) {
            $eppId = $eppData['epp_id'] ?? null;
            $cantidad = $eppData['cantidad'] ?? 1;
            $observaciones = $eppData['observaciones'] ?? '';

            if (!$eppId) {
                continue;
            }

            $pedidoEppRef = $this->pedidoRepository->obtenerEppConImagenes($pedidoId, $eppId);
            if (!$pedidoEppRef) {
                continue;
            }

            $cantidadEliminada = $this->eppImageCleanupService->eliminarImagenes($pedidoEppRef->pedidoEppId);
            if ($cantidadEliminada > 0) {
                Log::info('[PedidoDraftMutationService] Imagenes antiguas de EPP eliminadas', [
                    'pedido_epp_id' => $pedidoEppRef->pedidoEppId,
                    'epp_id' => $eppId,
                    'imagenes_eliminadas' => $cantidadEliminada,
                ]);
            }

            $this->pedidoRepository->actualizarDatosEpp($pedidoEppRef->pedidoEppId, [
                'cantidad' => $cantidad,
                'observaciones' => $observaciones,
            ]);

            Log::info('[PedidoDraftMutationService] EPP actualizado', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'cantidad' => $cantidad,
            ]);
        }

        $this->pedidoImagenesService->procesarImagenesDeEpps($request, $pedidoId, $eppsCrudos);
    }

    /**
     * @return int[]
     */
    public function crearNuevasPrendas(object $pedido, array $nuevasPrendas): array
    {
        if (empty($nuevasPrendas)) {
            return [];
        }

        $pedidoModelo = $pedido instanceof PedidoProduccion
            ? $pedido
            : PedidoProduccion::findOrFail($pedido->pedidoId ?? 0);

        $nuevasPrendasIds = [];

        foreach ($nuevasPrendas as $index => $itemData) {
            $prendaCreada = $this->pedidoCreationCoordinator->agregarItemAPedido($pedidoModelo, $itemData, (int) $index);
            $nuevasPrendasIds[] = $prendaCreada->id;
        }

        Log::info('[PedidoDraftMutationService] Nuevas prendas creadas', [
            'pedido_id' => $pedidoModelo->id,
            'cantidad' => count($nuevasPrendasIds),
        ]);

        return $nuevasPrendasIds;
    }

    public function procesarImagenesNuevasPrendas($request, array $nuevasPrendasIds, array $nuevasPrendas): void
    {
        if (empty($nuevasPrendasIds) || empty($nuevasPrendas)) {
            return;
        }

        $this->pedidoImagenesService->procesarImagenesNuevasPrendas(
            $request,
            $nuevasPrendasIds,
            $nuevasPrendas
        );
    }

    public function procesarImagenesDeProcesos($request, int $pedidoId, array $prendasData): void
    {
        if (empty($prendasData)) {
            return;
        }

        foreach ($prendasData as $prendaIndex => $prendaData) {
            $procesos = $prendaData['procesos'] ?? [];
            if (empty($procesos)) {
                continue;
            }

            $this->pedidoImagenesService->procesarImagenesDeProcesos(
                $request,
                $pedidoId,
                $procesos,
                (int) $prendaIndex
            );
        }
    }
}
