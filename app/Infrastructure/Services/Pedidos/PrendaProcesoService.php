<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta la creacion de procesos para una prenda agregada manualmente.
 */
class PrendaProcesoService
{
    public function __construct(
        private PedidoProcesoBuilder $pedidoProcesoBuilder,
        private PedidoProcesoTallaBuilder $pedidoProcesoTallaBuilder,
    ) {}

    public function crearProcesosCompletos(PrendaPedido $prenda, array $procesos, array $fotosProcesoNuevo = []): void
    {
        Log::info('[PrendaProcesoService] crearProcesosCompletos INICIADA', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($procesos),
        ]);

        foreach ($procesos as $procesoIdx => $proceso) {
            if (!is_array($proceso)) {
                continue;
            }

            $tipoProcesoId = $proceso['tipo_proceso_id'] ?? null;

            if (!$tipoProcesoId && isset($proceso['tipo'])) {
                $tipoProcesoId = $this->pedidoProcesoBuilder->resolverTipoProcesoId((string) $proceso['tipo']);
            }

            if (!$tipoProcesoId) {
                Log::warning('[PrendaProcesoService] Tipo de proceso no encontrado', [
                    'tipo_buscado' => $proceso['tipo'] ?? 'N/A',
                    'prenda_id' => $prenda->id,
                ]);
                continue;
            }

            $this->pedidoProcesoBuilder->eliminarDuplicado($prenda, $tipoProcesoId);

            $ubicaciones = $proceso['ubicaciones'] ?? [];
            if (is_string($ubicaciones)) {
                $ubicaciones = json_decode($ubicaciones, true) ?? [];
            }
            if (!is_array($ubicaciones)) {
                $ubicaciones = is_string($ubicaciones) ? [$ubicaciones] : [];
            }

            $observaciones = $proceso['observaciones'] ?? null;
            if (is_string($observaciones)) {
                $observaciones = trim($observaciones);
                $observaciones = empty($observaciones) ? null : $observaciones;
            }

            $procesoCreado = $this->pedidoProcesoBuilder->crearBase(
                $prenda,
                $tipoProcesoId,
                $ubicaciones,
                $observaciones,
                $proceso['modoTallas'] ?? 'generico',
                $proceso,
                $proceso['estado'] ?? 'PENDIENTE'
            );

            if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                $datosExtendidos = $proceso['datosExtendidos'] ?? [];
                $this->pedidoProcesoTallaBuilder->crearDesdeMapaSimple(
                    $procesoCreado,
                    $proceso['tallas'],
                    $datosExtendidos
                );
            }

            if (!empty($fotosProcesoNuevo) && isset($fotosProcesoNuevo[$procesoIdx])) {
                foreach ($fotosProcesoNuevo[$procesoIdx] as $rutasFoto) {
                    $procesoCreado->imagenes()->create([
                        'ruta_original' => $rutasFoto['ruta_original'] ?? null,
                        'ruta_webp' => $rutasFoto['ruta_webp'] ?? $rutasFoto['ruta_original'] ?? null,
                        'orden' => 1,
                    ]);
                }
            }

            Log::info('[PrendaProcesoService] Proceso creado', [
                'prenda_id' => $prenda->id,
                'proceso_id' => $procesoCreado->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'tipo' => $proceso['tipo'] ?? 'N/A',
            ]);
        }

        Log::info('[PrendaProcesoService] crearProcesosCompletos TERMINADA', [
            'prenda_id' => $prenda->id,
        ]);
    }
}
