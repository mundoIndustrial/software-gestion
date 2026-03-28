<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\EliminarImagenPedidoUseCaseContract;

use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcesoImagenes;
use Illuminate\Support\Facades\Storage;

/**
 * Use Case para eliminar una imagen individual de un pedido
 *
 * Soporta tres tipos: 'prenda', 'tela', 'proceso'
 * Elimina archivos físicos (original + webp) y el registro de BD (forceDelete)
 */
final class EliminarImagenPedidoUseCase implements EliminarImagenPedidoUseCaseContract
{
    public function ejecutar(int $id, string $tipo): array
    {
        $modelClass = match($tipo) {
            'prenda'  => PrendaFotoPedido::class,
            'tela'    => PrendaFotoTelaPedido::class,
            'proceso' => PedidosProcesoImagenes::class,
        };

        $imagen = $modelClass::findOrFail($id);

        $archivosEliminados = [];

        if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
            Storage::disk('public')->delete($imagen->ruta_original);
            $archivosEliminados[] = $imagen->ruta_original;
        }

        if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
            Storage::disk('public')->delete($imagen->ruta_webp);
            $archivosEliminados[] = $imagen->ruta_webp;
        }

        $imagen->forceDelete();

        return [
            'success'            => true,
            'message'            => 'Imagen eliminada correctamente',
            'archivos_eliminados' => $archivosEliminados,
        ];
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {EliminarImagenPedidoUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





