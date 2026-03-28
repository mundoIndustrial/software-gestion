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
                $proceso = PedidosProcesosPrendaDetalle::findOrFail((int) $procesoId);

                if ($proceso->imagenes) {
                    foreach ($proceso->imagenes as $imagen) {
                        if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                            Storage::disk('public')->delete($imagen->ruta_original);
                        }
                        if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
                            Storage::disk('public')->delete($imagen->ruta_webp);
                        }
                        $imagen->forceDelete();
                    }
                }

                if ($proceso->tallas) {
                    foreach ($proceso->tallas as $talla) {
                        $talla->forceDelete();
                    }
                }

                $proceso->forceDelete();

                Log::info('[EliminarProcesosListaUseCase] Proceso eliminado', [
                    'id'         => $procesoId,
                    'tipo'       => $proceso->tipo_recibo ?? null,
                ]);
            } catch (\Exception $e) {
                Log::warning('[EliminarProcesosListaUseCase] Error eliminando proceso', [
                    'id'    => $procesoId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {EliminarProcesosListaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





