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
        foreach ($procesosIds as $procesoId) {
            try {
                $proceso = $this->buscarProceso((int) $procesoId);
                $this->eliminarImagenesProceso($proceso);
                $this->eliminarTallasProceso($proceso);
                $proceso->forceDelete();

                $this->registrarProcesoEliminado((int) $procesoId, $proceso->tipo_recibo ?? null);
            } catch (\Exception $e) {
                $this->registrarErrorEliminacion((int) $procesoId, $e);
            }
        }
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




