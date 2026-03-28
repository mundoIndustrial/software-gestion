<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\EliminarEppUseCaseContract;

use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Use Case para eliminar un EPP de un pedido
 *
 * Maneja: novedades, eliminación de imágenes físicas y soft delete del EPP
 */
final class EliminarEppUseCase implements EliminarEppUseCaseContract
{
    public function ejecutar(int $pedidoId, int $pedidoEppId, string $motivo): array
    {
        $pedidoEpp = PedidoEpp::findOrFail($pedidoEppId);
        $epp = $pedidoEpp->epp;
        $nombreEpp = $epp->nombre_completo ?? $epp->nombre ?? 'EPP Sin nombre';

        $pedido = PedidoProduccion::findOrFail($pedidoId);

        $mensaje = "[ELIMINADO EPP] {$nombreEpp} (Cantidad: {$pedidoEpp->cantidad}) - Motivo: {$motivo}";
        $pedido->novedades = $pedido->novedades
            ? $pedido->novedades . "\n\n" . $mensaje
            : $mensaje;
        $pedido->save();

        Log::info('[EliminarEppUseCase] Novedades actualizadas', ['pedido_id' => $pedidoId]);

        $imagenes = PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)->get();
        foreach ($imagenes as $imagen) {
            if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                Storage::disk('public')->delete($imagen->ruta_original);
            }
            if ($imagen->ruta_web && $imagen->ruta_web !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_web)) {
                Storage::disk('public')->delete($imagen->ruta_web);
            }
            $imagen->delete();
        }

        Log::info('[EliminarEppUseCase] Imágenes eliminadas', [
            'cantidad' => $imagenes->count(),
            'pedido_epp_id' => $pedidoEppId,
        ]);

        $pedidoEpp->delete();

        Log::info('[EliminarEppUseCase] EPP eliminado', [
            'pedido_epp_id' => $pedidoEppId,
            'nombre' => $nombreEpp,
        ]);

        return [
            'success' => true,
            'message' => 'EPP eliminado correctamente',
            'epp_id' => $pedidoEppId,
            'epp_nombre' => $nombreEpp,
            'motivo_registrado' => $motivo,
            'pedido_id' => $pedidoId,
        ];
    }
}





