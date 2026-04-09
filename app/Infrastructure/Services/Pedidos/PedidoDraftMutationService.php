<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\Services\PedidoCreationCoordinator;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Models\PedidoEpp;
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
        $this->sincronizarEppsEliminados($pedidoId, $eppsCrudos);

        if (empty($eppsCrudos)) {
            return;
        }

        foreach ($eppsCrudos as $eppIdx => $eppData) {
            $eppId = $eppData['epp_id'] ?? null;
            $pedidoEppId = (int) ($eppData['pedido_epp_id'] ?? 0);
            $cantidad = $eppData['cantidad'] ?? 1;
            $observaciones = $eppData['observaciones'] ?? '';

            if (!$eppId) {
                continue;
            }

            $pedidoEppRef = null;
            if ($pedidoEppId > 0) {
                $pedidoEppModel = PedidoEpp::where('pedido_produccion_id', $pedidoId)
                    ->where('id', $pedidoEppId)
                    ->with(['imagenes'])
                    ->first();

                if ($pedidoEppModel) {
                    $pedidoEppRef = new \App\Domain\Pedidos\ReadModels\PedidoEppRef(
                        pedidoEppId: (int) $pedidoEppModel->id,
                        pedidoId: (int) $pedidoEppModel->pedido_produccion_id,
                        eppId: (int) $pedidoEppModel->epp_id,
                        cantidad: (int) $pedidoEppModel->cantidad,
                        observaciones: $pedidoEppModel->observaciones,
                        imagenesCount: $pedidoEppModel->imagenes->count(),
                    );
                }
            }

            if (!$pedidoEppRef) {
                $pedidoEppRef = $this->pedidoRepository->obtenerEppConImagenes($pedidoId, $eppId);
            }
            
            // Si el EPP es NUEVO (no existe en el pedido), crearlo primero
            if (!$pedidoEppRef) {
                Log::info('[PedidoDraftMutationService] EPP nuevo detectado, creando relación', [
                    'pedido_id' => $pedidoId,
                    'epp_id' => $eppId,
                ]);
                
                // Crear el EPP en la tabla pedido_epp
                $this->pedidoImagenesService->procesarYAsignarEpps($request, $pedidoId, [$eppData]);
                
                // Recargar la referencia
                $pedidoEppRef = $this->pedidoRepository->obtenerEppConImagenes($pedidoId, $eppId);
                
                if (!$pedidoEppRef) {
                    Log::warning('[PedidoDraftMutationService] No se pudo crear EPP nuevo', [
                        'pedido_id' => $pedidoId,
                        'epp_id' => $eppId,
                    ]);
                    continue;
                }
            }

            if ($this->debeRefrescarImagenesEpp($request, (int) $eppIdx, $eppData)) {
                $cantidadEliminada = $this->eppImageCleanupService->eliminarImagenes($pedidoEppRef->pedidoEppId);
                if ($cantidadEliminada > 0) {
                    Log::info('[PedidoDraftMutationService] Imagenes antiguas de EPP eliminadas', [
                        'pedido_epp_id' => $pedidoEppRef->pedidoEppId,
                        'epp_id' => $eppId,
                        'imagenes_eliminadas' => $cantidadEliminada,
                    ]);
                }
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

    private function sincronizarEppsEliminados(int $pedidoId, array $eppsCrudos): void
    {
        $idsRelacionMantener = [];
        $idsCatalogoMantener = [];

        foreach ($eppsCrudos as $eppData) {
            $pedidoEppId = (int) ($eppData['pedido_epp_id'] ?? 0);
            $eppId = (int) ($eppData['epp_id'] ?? 0);

            if ($pedidoEppId > 0) {
                $idsRelacionMantener[] = $pedidoEppId;
            }
            if ($eppId > 0) {
                $idsCatalogoMantener[] = $eppId;
            }
        }

        $existentes = PedidoEpp::where('pedido_produccion_id', $pedidoId)->get();
        foreach ($existentes as $pedidoEpp) {
            $debeMantenerPorRelacion = in_array((int) $pedidoEpp->id, $idsRelacionMantener, true);
            $debeMantenerPorCatalogo = in_array((int) $pedidoEpp->epp_id, $idsCatalogoMantener, true);

            if ($debeMantenerPorRelacion || $debeMantenerPorCatalogo) {
                continue;
            }

            $imagenesEliminadas = $this->eppImageCleanupService->eliminarImagenes((int) $pedidoEpp->id);
            $pedidoEpp->delete();

            Log::info('[PedidoDraftMutationService] EPP eliminado por sincronizacion de borrador', [
                'pedido_id' => $pedidoId,
                'pedido_epp_id' => (int) $pedidoEpp->id,
                'epp_id' => (int) $pedidoEpp->epp_id,
                'imagenes_eliminadas' => $imagenesEliminadas,
            ]);
        }
    }

    private function debeRefrescarImagenesEpp($request, int $eppIdx, array $eppData): bool
    {
        $modo = strtolower(trim((string) ($eppData['modo_imagenes'] ?? '')));
        if (in_array($modo, ['upload', 'reuse'], true)) {
            return true;
        }

        $prefijo = "epps_{$eppIdx}_imagenes_";
        foreach ((array) $request->allFiles() as $campo => $archivo) {
            if (is_string($campo) && str_starts_with($campo, $prefijo)) {
                return true;
            }
        }

        return false;
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
