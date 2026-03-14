<?php

namespace App\Application\Pedidos\UseCases;

use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * Use Case para homologar un EPP de un pedido
 *
 * Maneja: novedades, soft delete del EPP anterior, creación del EPP nuevo
 * y duplicación de imágenes al nuevo registro.
 */
final class HomologarEppUseCase
{
    public function ejecutar(
        int $pedidoId,
        int $pedidoEppIdAnterior,
        string $motivo,
        int $cantidadNueva,
        ?string $observacionesNuevas,
        ?int $eppIdNuevo
    ): array {
        $pedidoEppAnterior = PedidoEpp::findOrFail($pedidoEppIdAnterior);
        $epp = $pedidoEppAnterior->epp;
        $nombreEpp = $epp->nombre_completo ?? $epp->nombre ?? 'EPP Sin nombre';

        $pedido = PedidoProduccion::findOrFail($pedidoId);

        $mensaje = "[HOMOLOGADO EPP] {$nombreEpp} (Cantidad anterior: {$pedidoEppAnterior->cantidad} → Nueva: {$cantidadNueva}) - Motivo: {$motivo}";
        $pedido->novedades = $pedido->novedades
            ? $pedido->novedades . "\n\n" . $mensaje
            : $mensaje;
        $pedido->save();

        Log::info('[HomologarEppUseCase] Novedades actualizadas', ['pedido_id' => $pedidoId]);

        $pedidoEppAnterior->delete();

        Log::info('[HomologarEppUseCase] EPP anterior marcado como eliminado', [
            'pedido_epp_id' => $pedidoEppIdAnterior,
        ]);

        $eppNuevo = new PedidoEpp();
        $eppNuevo->pedido_produccion_id = $pedidoEppAnterior->pedido_produccion_id;
        $eppNuevo->epp_id = $eppIdNuevo ?? $pedidoEppAnterior->epp_id;
        $eppNuevo->cantidad = $cantidadNueva;
        $eppNuevo->observaciones = $observacionesNuevas ?? '';
        $eppNuevo->homologado_de = $pedidoEppIdAnterior;
        $eppNuevo->save();

        Log::info('[HomologarEppUseCase] EPP nuevo creado', [
            'pedido_epp_id_nuevo' => $eppNuevo->id,
            'cantidad' => $cantidadNueva,
        ]);

        $imagenesAntiguas = PedidoEppImagen::where('pedido_epp_id', $pedidoEppIdAnterior)->get();
        foreach ($imagenesAntiguas as $imagenAntigua) {
            PedidoEppImagen::create([
                'pedido_epp_id' => $eppNuevo->id,
                'ruta_original' => $imagenAntigua->ruta_original,
                'ruta_web' => $imagenAntigua->ruta_web,
                'principal' => $imagenAntigua->principal,
                'orden' => $imagenAntigua->orden,
            ]);
        }

        Log::info('[HomologarEppUseCase] Imágenes duplicadas', [
            'cantidad' => $imagenesAntiguas->count(),
            'pedido_epp_id_nuevo' => $eppNuevo->id,
        ]);

        return [
            'success' => true,
            'message' => 'EPP homologado correctamente',
            'epp_id_anterior' => $pedidoEppIdAnterior,
            'epp_id_nuevo' => $eppNuevo->id,
            'epp_nombre' => $nombreEpp,
            'motivo_registrado' => $motivo,
            'pedido_id' => $pedidoId,
            'cambios' => [
                'cantidad_anterior' => $pedidoEppAnterior->cantidad,
                'cantidad_nueva' => $cantidadNueva,
                'epp_id_nuevo' => $eppIdNuevo,
            ],
        ];
    }
}
