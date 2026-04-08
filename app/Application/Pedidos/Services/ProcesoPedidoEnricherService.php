<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use Illuminate\Support\Facades\Log;

class ProcesoPedidoEnricherService
{
    public function __construct(private PedidoDetalleReadService $readService)
    {
    }

    public function enriquecer(array &$datos): void
    {
        if (!isset($datos['prendas']) || !is_array($datos['prendas'])) {
            return;
        }

        foreach ($datos['prendas'] as &$prenda) {
            $this->enriquecerProcesosPrenda($prenda);
        }
        unset($prenda);
    }

    private function enriquecerProcesosPrenda(array &$prenda): void
    {
        if (!isset($prenda['procesos']) || !is_array($prenda['procesos'])) {
            return;
        }

        foreach ($prenda['procesos'] as &$proceso) {
            if (!isset($proceso['id'])) {
                continue;
            }

            $this->decodificarUbicacionesProceso($proceso);
            $resumenTallas = $this->construirResumenTallasProceso((int) $proceso['id']);

            $proceso['tallas'] = $resumenTallas['tallas'];
            $proceso['tallas_detalle'] = $resumenTallas['tallas_detalle'];

            if (($proceso['modo_tallas'] ?? null) === 'general') {
                $proceso['observaciones_por_talla'] = $resumenTallas['observaciones_por_talla'];
            }
        }
        unset($proceso);
    }

    private function decodificarUbicacionesProceso(array &$proceso): void
    {
        if (!isset($proceso['ubicaciones']) || !is_string($proceso['ubicaciones'])) {
            return;
        }

        $decodedUb = json_decode($proceso['ubicaciones'], true);
        if (!is_array($decodedUb)) {
            return;
        }

        $proceso['ubicaciones_array'] = $decodedUb;
    }

    /**
     * @return array{
     *   tallas: array<string, array<mixed>>,
     *   tallas_detalle: array<int, array<string, mixed>>,
     *   observaciones_por_talla: array<string, array<string, string>>
     * }
     */
    private function construirResumenTallasProceso(int $procesoId): array
    {
        $tallas = $this->readService->getTallasProceso($procesoId);

        Log::debug('[ObtenerPedidoTransformadoUseCase] Tallas obtenidas para proceso', [
            'proceso_id' => $procesoId,
            'tallas_count' => $tallas->count(),
        ]);

        $resumen = [
            'tallas' => $this->crearEstructuraGeneroVacia(),
            'tallas_detalle' => [],
            'observaciones_por_talla' => $this->crearEstructuraGeneroVacia(),
        ];

        foreach ($tallas as $talla) {
            $this->acumularResumenTalla($resumen, $talla);
        }

        return $resumen;
    }

    /**
     * @param array{
     *   tallas: array<string, array<mixed>>,
     *   tallas_detalle: array<int, array<string, mixed>>,
     *   observaciones_por_talla: array<string, array<string, string>>
     * } $resumen
     */
    private function acumularResumenTalla(array &$resumen, object $talla): void
    {
        $genero = $this->normalizarGenero($talla->genero ?? 'caballero');

        $resumen['tallas_detalle'][] = [
            'genero' => strtoupper((string) ($talla->genero ?? '')),
            'talla' => $talla->talla,
            'cantidad' => (int) ($talla->cantidad ?? 0),
            'es_sobremedida' => (int) ($talla->es_sobremedida ?? 0),
        ];

        $resumen['tallas'][$genero][$talla->talla] = $this->resolverColoresOTotal($talla);
        $this->agregarObservacionTalla($resumen['observaciones_por_talla'][$genero], $talla);
    }

    /**
     * @return array<int, array{color:mixed,cantidad:mixed}>|mixed
     */
    private function resolverColoresOTotal(object $talla): mixed
    {
        $colores = $this->readService->getColoresByProcesoTalla((int) $talla->id);

        if ($colores->isEmpty()) {
            return $talla->cantidad;
        }

        return $colores->map(fn($color) => [
            'color' => $color->color_nombre,
            'cantidad' => $color->cantidad,
        ])->toArray();
    }

    /**
     * @param array<string, string> $observacionesPorGenero
     */
    private function agregarObservacionTalla(array &$observacionesPorGenero, object $talla): void
    {
        $obs = trim((string) ($talla->observaciones ?? ''));
        if ($obs === '') {
            return;
        }

        $tallaKey = $talla->talla !== null ? (string) $talla->talla : 'SOBREMEDIDA';
        $observacionesPorGenero[$tallaKey] = $obs;
    }

    /**
     * @return array{dama: array<mixed>, caballero: array<mixed>, unisex: array<mixed>}
     */
    private function crearEstructuraGeneroVacia(): array
    {
        return [
            'dama' => [],
            'caballero' => [],
            'unisex' => [],
        ];
    }

    private function normalizarGenero(?string $genero): string
    {
        $generoNormalizado = strtolower($genero ?? 'caballero');
        if ($generoNormalizado === 'dama') {
            return 'dama';
        }
        if ($generoNormalizado === 'caballero') {
            return 'caballero';
        }

        return 'unisex';
    }
}
