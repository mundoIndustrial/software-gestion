<?php

namespace App\Domain\Procesos\Services;

use Illuminate\Support\Facades\DB;

/**
 * Domain Service: AnularReciboProcesoService
 *
 * Anula un proceso (transición irrevocable a estado ANULADO).
 * Solo supervisores pueden ejecutar esta acción (validado en el controller).
 */
class AnularReciboProcesoService
{
    /**
     * @throws \DomainException Si el proceso no existe o ya está anulado
     */
    public function ejecutar(int $procesoId)
    {
        // Obtener proceso actual
        $proceso = DB::table('pedidos_procesos_prenda_detalles')
            ->where('id', $procesoId)
            ->first();

        if (!$proceso) {
            throw new \DomainException('Proceso no encontrado');
        }

        if ($proceso->estado === 'ANULADO') {
            throw new \DomainException('El proceso ya está anulado');
        }

        // Anular el proceso
        DB::table('pedidos_procesos_prenda_detalles')
            ->where('id', $procesoId)
            ->update([
                'estado' => 'ANULADO',
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
                'updated_at' => now(),
            ]);

        // Anular también el consecutivo recibo asociado
        $this->anularConsecutivoRecibo($proceso->prenda_pedido_id, $proceso->tipo_proceso_id);

        return DB::table('pedidos_procesos_prenda_detalles')->find($procesoId);
    }

    /**
     * Anula el consecutivo recibo asociado al proceso
     */
    private function anularConsecutivoRecibo(?int $prendaId, ?int $tipoProcesId): void
    {
        if (!$prendaId || !$tipoProcesId) {
            return;
        }

        try {
            // Obtener el tipo de recibo basado en el tipo de proceso
            $tipoRecibo = $this->obtenerTipoReciboPorTipoProceso($tipoProcesId);

            if (!$tipoRecibo) {
                return;
            }

            // Actualizar el consecutivo recibo
            DB::table('consecutivos_recibos_pedidos')
                ->where('prenda_id', $prendaId)
                ->where('tipo_recibo', $tipoRecibo)
                ->update([
                    'estado' => 'ANULADO',
                    'area' => 'ANULADO',
                    'activo' => 0,
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            // Log pero no fallar si hay error actualizando el consecutivo
            \Log::warning('[AnularReciboProcesoService] Error anulando consecutivo recibo', [
                'prenda_id' => $prendaId,
                'tipo_proceso_id' => $tipoProcesId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene el tipo de recibo según el tipo de proceso
     */
    private function obtenerTipoReciboPorTipoProceso(int $tipoProcesId): ?string
    {
        $tiposMapeo = [
            2 => 'BORDADO',      // Bordado
            3 => 'ESTAMPADO',    // Estampado
            4 => 'DTF',          // DTF
            5 => 'SUBLIMADO',    // Sublimado
        ];

        return $tiposMapeo[$tipoProcesId] ?? null;
    }
}
