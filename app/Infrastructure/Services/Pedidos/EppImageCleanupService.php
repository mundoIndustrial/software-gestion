<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidoEppImagen;
use Illuminate\Support\Facades\Storage;

/**
 * Maneja el cleanup de imagenes asociadas a un EPP de pedido.
 */
class EppImageCleanupService
{
    public function eliminarImagenes(int $pedidoEppId): int
    {
        $imagenes = PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)->get();
        $cantidad = 0;

        foreach ($imagenes as $imagen) {
            if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                Storage::disk('public')->delete($imagen->ruta_original);
            }

            if (
                $imagen->ruta_web &&
                $imagen->ruta_web !== $imagen->ruta_original &&
                Storage::disk('public')->exists($imagen->ruta_web)
            ) {
                Storage::disk('public')->delete($imagen->ruta_web);
            }

            $imagen->delete();
            $cantidad++;
        }

        return $cantidad;
    }
}
