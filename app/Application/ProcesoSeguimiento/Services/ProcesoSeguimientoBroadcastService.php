<?php

namespace App\Application\ProcesoSeguimiento\Services;

use App\Events\CorteAsignadoOperario;
use App\Events\OperarioRecibosActualizados;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Application Service: ProcesoSeguimientoBroadcastService
 *
 * Centraliza la lógica de broadcast para procesos de seguimiento.
 * Implementa Open/Closed: agregar un área nueva solo requiere extender
 * este servicio sin modificar los Use Cases que lo consumen.
 */
final class ProcesoSeguimientoBroadcastService
{
    /**
     * Dispara los broadcasts correspondientes según el área afectada.
     *
     * @param string $area         Área del proceso (e.g. 'Corte', 'Costura')
     * @param string $encargado    Nombre del encargado asignado
     * @param string $accion       'creado' | 'actualizado'
     * @param int    $numeroPedido Número del pedido de producción
     * @param int    $prendaId     ID de la prenda
     * @param int    $procesoId    ID del proceso guardado
     */
    public function disparar(
        string $area,
        string $encargado,
        string $accion,
        int    $numeroPedido,
        int    $prendaId,
        int    $procesoId,
    ): void {
        try {
            $areaNormalizada      = strtolower(trim($area));
            $encargadoNormalizado = strtolower(trim($encargado));

            if ($areaNormalizada === 'corte') {
                $this->broadcastCorte($area, $encargado, $encargadoNormalizado, $accion, $numeroPedido, $prendaId, $procesoId);
            }

            if ($areaNormalizada === 'costura' && $encargadoNormalizado !== '') {
                $this->broadcastCostura($area, $encargadoNormalizado, $accion, $numeroPedido, $prendaId, $procesoId);
            }
        } catch (\Exception $e) {
            // El fallo de broadcast no debe interrumpir la operación principal.
            Log::warning('[ProcesoSeguimientoBroadcastService] Error en broadcast: ' . $e->getMessage());
        }
    }

    // ── Handlers por área ───────────────────────────────────────────────────

    private function broadcastCorte(
        string $area,
        string $encargado,
        string $encargadoNormalizado,
        string $accion,
        int    $numeroPedido,
        int    $prendaId,
        int    $procesoId,
    ): void {
        // Canal público: el dashboard del cortador puede reaccionar por nombre
        broadcast(new CorteAsignadoOperario([
            'area'          => $area,
            'accion'        => $accion,
            'numero_pedido' => $numeroPedido,
            'prenda_id'     => $prendaId,
            'proceso_id'    => $procesoId,
            'encargado'     => $encargado,
        ]));

        if ($encargadoNormalizado === '') {
            return;
        }

        $operario = User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
            ->first();

        if ($operario && ($operario->hasRole('cortador') || $operario->hasRole('visualizador_plooter'))) {
            broadcast(new OperarioRecibosActualizados(
                userId: $operario->id,
                payload: [
                    'area'          => $area,
                    'accion'        => $accion,
                    'numero_pedido' => $numeroPedido,
                    'prenda_id'     => $prendaId,
                    'proceso_id'    => $procesoId,
                ]
            ));

            $rol = $operario->hasRole('visualizador_plooter') ? 'visualizador_plooter' : 'cortador';
            Log::info('[ProcesoSeguimientoBroadcastService] Canal privado emitido para ' . $rol, [
                'user_id' => $operario->id,
                'encargado' => $encargado,
                'rol' => $rol,
            ]);
        }
    }

    private function broadcastCostura(
        string $area,
        string $encargadoNormalizado,
        string $accion,
        int    $numeroPedido,
        int    $prendaId,
        int    $procesoId,
    ): void {
        $operario = User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
            ->first();

        if ($operario && $operario->hasRole('costura-reflectivo')) {
            broadcast(new OperarioRecibosActualizados(
                userId: $operario->id,
                payload: [
                    'area'          => $area,
                    'accion'        => $accion,
                    'numero_pedido' => $numeroPedido,
                    'prenda_id'     => $prendaId,
                    'proceso_id'    => $procesoId,
                ]
            ));

            Log::info('[ProcesoSeguimientoBroadcastService] Canal privado costura-reflectivo emitido', [
                'user_id' => $operario->id,
            ]);
        }
    }
}
