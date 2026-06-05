<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\EliminarEppUseCaseContract;

use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\News;
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

        if (!$this->esPedidoBorrador($pedido)) {
            $mensaje = "[ELIMINADO EPP] {$nombreEpp} (Cantidad: {$pedidoEpp->cantidad}) - Motivo: {$motivo}";
            $pedido->novedades = $pedido->novedades
                ? $pedido->novedades . "\n\n" . $mensaje
                : $mensaje;
            $pedido->save();

            Log::info('[EliminarEppUseCase] Novedades actualizadas', ['pedido_id' => $pedidoId]);
        }

        $imagenes = PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)->get();
        foreach ($imagenes as $imagen) {
            if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                Storage::disk('public')->delete($imagen->ruta_original);
            }
            if ($imagen->ruta_web && $imagen->ruta_web !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_web)) {
                Storage::disk('public')->delete($imagen->ruta_web);
            }
        }

        PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)->delete();

        Log::info('[EliminarEppUseCase] Imágenes eliminadas', [
            'cantidad' => $imagenes->count(),
            'pedido_epp_id' => $pedidoEppId,
        ]);

        PedidoEpp::where('id', $pedidoEppId)->delete();

        Log::info('[EliminarEppUseCase] EPP eliminado', [
            'pedido_epp_id' => $pedidoEppId,
            'nombre' => $nombreEpp,
        ]);

        try {
            $usuario = auth()?->user();
            $nombreUsuario = $usuario?->name ?? 'Sistema';
            $roles = $usuario?->getRoleNames()?->toArray() ?? [];
            $rolUsuario = count($roles) > 0 ? implode(', ', $roles) : 'Asesor';
            $datosAsesor = $rolUsuario !== 'Asesor'
                ? "{$nombreUsuario} ({$rolUsuario})"
                : $nombreUsuario;
            $numeroPedido = $pedido->numero_pedido ?? $pedidoId;

            News::create([
                'event_type' => 'epp_eliminado',
                'table_name' => 'pedido_epp',
                'record_id' => $pedidoEppId,
                'description' => "{$datosAsesor} elimino EPP en Pedido #{$numeroPedido} ({$nombreEpp}) - Motivo: {$motivo}",
                'user_id' => $usuario?->id,
                'pedido' => $numeroPedido,
                'metadata' => [
                    'tipo' => 'epp_eliminado',
                    'pedido_id' => $pedidoId,
                    'pedido_epp_id' => $pedidoEppId,
                    'epp_nombre' => $nombreEpp,
                    'cantidad' => $pedidoEpp->cantidad,
                    'motivo' => $motivo,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('[EliminarEppUseCase] Error creando News de eliminacion', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedidoId,
                'pedido_epp_id' => $pedidoEppId,
            ]);
        }

        return [
            'success' => true,
            'message' => 'EPP eliminado correctamente',
            'epp_id' => $pedidoEppId,
            'epp_nombre' => $nombreEpp,
            'motivo_registrado' => $motivo,
            'pedido_id' => $pedidoId,
        ];
    }

    private function esPedidoBorrador(PedidoProduccion $pedido): bool
    {
        if ($pedido->numero_pedido === null) {
            return true;
        }

        return strtolower((string) $pedido->estado) === 'borrador';
    }
}

