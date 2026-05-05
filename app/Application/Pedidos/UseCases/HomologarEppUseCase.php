<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\HomologarEppUseCaseContract;

use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\News;
use App\Models\PedidoProduccion;
use App\Models\Epp;
use Illuminate\Support\Facades\Log;

/**
 * Use Case para homologar un EPP de un pedido
 *
 * Maneja: novedades, soft delete del EPP anterior, creacion del EPP nuevo
 * y duplicacion de imagenes al nuevo registro.
 */
final class HomologarEppUseCase implements HomologarEppUseCaseContract
{
    public function ejecutar(
        int $pedidoId,
        int $pedidoEppIdAnterior,
        string $motivo,
        int $cantidadNueva,
        ?string $observacionesNuevas,
        ?int $eppIdNuevo,
        ?string $nombreAsesor = null,
        $timestamp = null,
        ?string $rolAsesor = null
    ): array {
        $pedidoEppAnterior = PedidoEpp::findOrFail($pedidoEppIdAnterior);
        $epp = $pedidoEppAnterior->epp;
        $nombreEpp = $epp->nombre_completo ?? $epp->nombre ?? 'EPP Sin nombre';

        $pedido = PedidoProduccion::findOrFail($pedidoId);

        // Si no se proporciona nombre de asesor ni timestamp, usar actuales
        if (!$nombreAsesor) {
            $nombreAsesor = auth()?->user()?->name ?? 'Sistema';
        }
        if (!$rolAsesor && auth()?->user()) {
            // Intentar obtener el rol del usuario (roles en Laravel)
            $roles = auth()->user()->getRoleNames() ?? [];
            $rolAsesor = count($roles) > 0 ? implode(', ', $roles->toArray()) : 'Asesor';
        }
        if (!$rolAsesor) {
            $rolAsesor = 'Asesor';
        }
        if (!$timestamp) {
            $timestamp = now();
        }

        $fechaFormato = is_string($timestamp) 
            ? $timestamp 
            : (is_object($timestamp) ? $timestamp->format('d/m/Y, g:i:s a') : now()->format('d/m/Y, g:i:s a'));

        $datosAsesor = "{$nombreAsesor}";
        if ($rolAsesor && $rolAsesor !== 'Asesor') {
            $datosAsesor .= " ({$rolAsesor})";
        }

        if (!$this->esPedidoBorrador($pedido)) {
            $mensaje = "[HOMOLOGADO EPP] {$nombreEpp} (Cantidad anterior: {$pedidoEppAnterior->cantidad} -> Nueva: {$cantidadNueva}) - Motivo: {$motivo}\n({$datosAsesor} - {$fechaFormato})";
            $pedido->novedades = $pedido->novedades
                ? $pedido->novedades . "\n\n" . $mensaje
                : $mensaje;
            $pedido->save();
    
            Log::info('[HomologarEppUseCase] Novedades actualizadas', ['pedido_id' => $pedidoId]);
        }

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

        Log::info('[HomologarEppUseCase] Imagenes duplicadas', [
            'cantidad' => $imagenesAntiguas->count(),
            'pedido_epp_id_nuevo' => $eppNuevo->id,
        ]);

        try {
            $eppNombreNuevo = $nombreEpp;
            if ($eppIdNuevo) {
                $eppModelNuevo = Epp::find($eppIdNuevo);
                $eppNombreNuevo = $eppModelNuevo?->nombre_completo ?? $eppModelNuevo?->nombre ?? $nombreEpp;
            }

            $cambioEppTexto = $eppNombreNuevo !== $nombreEpp
                ? " (EPP: {$nombreEpp} -> {$eppNombreNuevo})"
                : " (EPP: {$eppNombreNuevo})";

            News::create([
                'event_type' => 'epp_homologado',
                'table_name' => 'pedido_epp',
                'record_id' => $eppNuevo->id,
                'description' => "{$datosAsesor} homologo EPP en Pedido #{$pedido->numero_pedido}{$cambioEppTexto} y cambio cantidad de {$pedidoEppAnterior->cantidad} a {$cantidadNueva}",
                'user_id' => auth()?->id(),
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'epp_homologado',
                    'pedido_id' => $pedidoId,
                    'pedido_epp_id_anterior' => $pedidoEppIdAnterior,
                    'pedido_epp_id_nuevo' => $eppNuevo->id,
                    'epp_nombre_anterior' => $nombreEpp,
                    'epp_nombre_nuevo' => $eppNombreNuevo,
                    'cantidad_anterior' => $pedidoEppAnterior->cantidad,
                    'cantidad_nueva' => $cantidadNueva,
                    'motivo' => $motivo,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('[HomologarEppUseCase] Error creando News de homologacion', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedidoId,
                'pedido_epp_id_nuevo' => $eppNuevo->id,
            ]);
        }

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

    private function esPedidoBorrador(PedidoProduccion $pedido): bool
    {
        if ($pedido->numero_pedido === null) {
            return true;
        }

        return strtolower((string) $pedido->estado) === 'borrador';
    }
}



