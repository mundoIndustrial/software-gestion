<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\EliminarProcesosListaUseCaseContract;

use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Use Case para eliminar una lista de procesos durante la actualización de prenda
 *
 * Realiza forceDelete (eliminación permanente) de imágenes, tallas y el proceso.
 * Usado desde actualizarPrendaCompleta cuando se pasa 'procesos_a_eliminar'.
 */
final class EliminarProcesosListaUseCase implements EliminarProcesosListaUseCaseContract
{
    public function ejecutar(array $procesosIds): void
    {
        $idsNormalizados = $this->normalizarIds($procesosIds);

        Log::info('[EliminarProcesosListaUseCase] Procesos a eliminar (normalizados)', [
            'recibidos' => $procesosIds,
            'ids_normalizados' => $idsNormalizados,
        ]);

        foreach ($idsNormalizados as $procesoId) {
            try {
                $proceso = $this->buscarProceso($procesoId);
                $this->eliminarImagenesProceso($proceso);
                $this->eliminarTallasProceso($proceso);
                $proceso->forceDelete();

                $this->registrarProcesoEliminado($procesoId, $proceso->tipo_recibo ?? null);
            } catch (\Exception $e) {
                $this->registrarErrorEliminacion($procesoId, $e);
            }
        }
    }

    /**
     * @param array<int, mixed> $procesosIds
     * @return array<int, int>
     */
    private function normalizarIds(array $procesosIds): array
    {
        $ids = [];

        foreach ($procesosIds as $entry) {
            $id = null;

            if (is_numeric($entry)) {
                $id = (int) $entry;
            } elseif (is_array($entry)) {
                $id = (int) ($entry['id'] ?? $entry['proceso_prenda_detalle_id'] ?? 0);
            } elseif (is_object($entry)) {
                $id = (int) ($entry->id ?? $entry->proceso_prenda_detalle_id ?? 0);
            }

            if ($id && $id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function buscarProceso(int $procesoId): PedidosProcesosPrendaDetalle
    {
        return PedidosProcesosPrendaDetalle::findOrFail($procesoId);
    }

    private function eliminarImagenesProceso(PedidosProcesosPrendaDetalle $proceso): void
    {
        foreach ($proceso->imagenes ?? [] as $imagen) {
            $this->eliminarArchivoSiExiste($imagen->ruta_original);
            $this->eliminarWebpSiCorresponde($imagen->ruta_webp, $imagen->ruta_original);
            $imagen->forceDelete();
        }
    }

    private function eliminarTallasProceso(PedidosProcesosPrendaDetalle $proceso): void
    {
        foreach ($proceso->tallas ?? [] as $talla) {
            $talla->forceDelete();
        }
    }

    private function eliminarArchivoSiExiste(?string $ruta): void
    {
        if ($ruta && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    private function eliminarWebpSiCorresponde(?string $rutaWebp, ?string $rutaOriginal): void
    {
        if ($rutaWebp && $rutaWebp !== $rutaOriginal) {
            $this->eliminarArchivoSiExiste($rutaWebp);
        }
    }

    private function registrarProcesoEliminado(int $procesoId, ?string $tipoRecibo): void
    {
        Log::info('[EliminarProcesosListaUseCase] Proceso eliminado', [
            'id' => $procesoId,
            'tipo' => $tipoRecibo,
        ]);
    }

    private function registrarErrorEliminacion(int $procesoId, \Exception $exception): void
    {
        Log::warning('[EliminarProcesosListaUseCase] Error eliminando proceso', [
            'id' => $procesoId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {EliminarProcesosListaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}



