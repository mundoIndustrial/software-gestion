<?php

namespace App\Infrastructure\Services\Operario;

use App\Infrastructure\Repositories\Operario\OperarioRecibosRepository;
use App\Models\User;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosGlobalCosturaService
{
    public function __construct(
        private readonly OperarioRecibosRepository $operarioRecibosRepository,
        private readonly ObtenerPrendasRecibosSupportService $supportService,
        private readonly ObtenerPrendasRecibosParcialesService $parcialesService
    ) {}

    public function obtenerPrendasConRecibosTodosCostura(): Collection
    {
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        $usuariosCosturaReflectivo = $this->obtenerUsuariosCosturaReflectivo();
        $recibos = $this->operarioRecibosRepository->obtenerRecibosActivosPorTiposYAreas($tiposRecibo, ['Costura', 'Corte']);
        $recibos = $this->deduplicarRecibos($recibos);

        $prendasAgrupadas = $recibos
            ->groupBy(fn ($recibo) => $this->obtenerClaveAgrupacionGlobalCostura($recibo))
            ->flatMap(fn ($grupo) => $this->mapearGrupoGlobalCostura($grupo, $usuariosCosturaReflectivo))
            ->values();

        $resultadoFinal = $prendasAgrupadas;
        if (!(auth()->check() && auth()->user()->hasRole('vista-costura'))) {
            $resultadoFinal = $resultadoFinal->concat($this->parcialesService->obtenerPrendasParcialesCostura(null, true));
        }

        return $resultadoFinal
            ->sortBy(fn ($item) => $item['fecha_creacion'] ?? null)
            ->values();
    }

    private function obtenerUsuariosCosturaReflectivo(): Collection
    {
        $rolCosturaReflectivoId = $this->operarioRecibosRepository->obtenerRolIdPorNombre('costura-reflectivo');
        if (!$rolCosturaReflectivoId) {
            return collect();
        }

        return $this->operarioRecibosRepository->obtenerNombresUsuariosPorRolId($rolCosturaReflectivoId);
    }

    private function obtenerClaveAgrupacionGlobalCostura(mixed $recibo): string
    {
        return $recibo->prenda_id
            ? 'prenda_' . $recibo->prenda_id
            : 'pedido_' . $recibo->pedido_produccion_id;
    }

    private function mapearGrupoGlobalCostura(Collection $recibosDelaPrenda, Collection $usuariosCosturaReflectivo): array
    {
        $primerRecibo = $recibosDelaPrenda->first();
        [$prenda, $pedido] = $this->resolverPrendaPedidoGlobalCostura($primerRecibo);
        if (!$prenda || !$pedido) {
            return [];
        }

        $resultados = [];
        foreach ($recibosDelaPrenda->groupBy('tipo_recibo') as $tipoRecibo => $recibosDelTipo) {
            if (!in_array(strtoupper((string) $tipoRecibo), ['COSTURA', 'COSTURA-BODEGA'], true)) {
                continue;
            }

            $recibosConEncargado = $this->filtrarRecibosConEncargadoGlobalCostura($recibosDelTipo, $usuariosCosturaReflectivo);
            if ($recibosConEncargado->isEmpty()) {
                continue;
            }

            $recibosConEncargado = $recibosConEncargado
                ->sortBy(fn ($recibo) => $this->supportService->normalizarFechaAOrdenable($this->supportService->obtenerFechaLlegadaACorte($recibo) ?? $recibo->created_at))
                ->values();

            $fechaOrdenPrincipal = optional($recibosConEncargado->first())?->created_at;
            if ($recibosConEncargado->isNotEmpty()) {
                $fechaCortePrincipal = $this->supportService->obtenerFechaLlegadaACorte($recibosConEncargado->first());
                if (!empty($fechaCortePrincipal)) {
                    $fechaOrdenPrincipal = $fechaCortePrincipal;
                }
            }

            $resultados[] = [
                'prenda_id' => $prenda->id,
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'nombre_prenda' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion,
                'de_bodega' => $prenda->de_bodega,
                'tallas' => $prenda->tallas ? $prenda->tallas->map(function ($talla) {
                    return [
                        'id' => $talla->id,
                        'genero' => $talla->genero,
                        'talla' => $talla->talla,
                        'cantidad' => $talla->cantidad,
                        'tipo_talla' => $talla->tipo_talla,
                        'es_sobremedida' => $talla->es_sobremedida,
                        'tela' => $talla->tela,
                        'colores' => $talla->colores,
                    ];
                })->toArray() : [],
                'recibos' => $this->mapearRecibosGlobalCostura($recibosConEncargado),
                'total_recibos' => $recibosConEncargado->count(),
                'fecha_creacion' => $fechaOrdenPrincipal ?? $prenda->created_at,
            ];
        }

        return $resultados;
    }

    private function resolverPrendaPedidoGlobalCostura(mixed $primerRecibo): array
    {
        if ($primerRecibo->prenda_id && $primerRecibo->prenda) {
            $prenda = $primerRecibo->prenda;
            $pedido = $prenda->pedidoProduccion;
            return [$prenda, $pedido];
        }

        if ($primerRecibo->pedido_produccion_id && $primerRecibo->pedido) {
            $pedido = $primerRecibo->pedido;
            $prenda = $pedido->prendas->first();
            return [$prenda, $pedido];
        }

        return [null, null];
    }

    private function filtrarRecibosConEncargadoGlobalCostura(Collection $recibosDelTipo, Collection $usuariosCosturaReflectivo): Collection
    {
        return $recibosDelTipo->filter(function ($recibo) use ($usuariosCosturaReflectivo) {
            $contexto = $this->supportService->resolverContextoProcesosRecibo($recibo);
            $procesos = $contexto['procesos'];
            $areaRecibo = strtolower(trim((string) ($recibo->area ?? '')));

            if ($areaRecibo === 'costura') {
                $proceso = $contexto['proceso_costura'];
            } elseif ($areaRecibo === 'corte') {
                $proceso = $procesos
                    ->filter(fn ($p) => is_string($p->proceso ?? null) && strtolower(trim((string) $p->proceso)) === 'corte')
                    ->sortByDesc(fn ($p) => $p->created_at)
                    ->first();
            } else {
                return false;
            }

            if (!$proceso || empty($proceso->encargado)) {
                return false;
            }

            $encargadoNormalizado = strtolower(trim((string) $proceso->encargado));
            if ($usuariosCosturaReflectivo->contains($encargadoNormalizado)) {
                if (config('app.debug') && request()->boolean('debug_operario_dashboard')) {
                    \Log::info(' [administrador-costura] Excluyendo recibo asignado a costura-reflectivo', [
                        'recibo_id' => $recibo->id,
                        'area' => $areaRecibo,
                        'encargado' => $proceso->encargado,
                        'prenda_id' => $recibo->prenda_id,
                    ]);
                }

                return false;
            }

            return true;
        })->values();
    }

    private function mapearRecibosGlobalCostura(Collection $recibosConEncargado): array
    {
        $reciboIds = $recibosConEncargado->pluck('id')->filter()->values()->all();
        $completadosRows = $this->operarioRecibosRepository->obtenerCompletadosPorReciboIds($reciboIds);

        $completadosCorte = $completadosRows
            ->filter(fn ($r) => strtolower(trim((string) ($r->area ?? ''))) === 'corte')
            ->pluck('id_recibo')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $completadosCostura = $completadosRows
            ->filter(fn ($r) => strtolower(trim((string) ($r->area ?? ''))) === 'costura')
            ->pluck('id_recibo')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $completadosControlCalidad = $completadosRows
            ->filter(function ($r) {
                $a = strtolower(trim((string) ($r->area ?? '')));
                return $a === 'control calidad' || $a === 'control de calidad';
            })
            ->pluck('id_recibo')
            ->map(fn ($id) => (int) $id)
            ->flip();

        return $recibosConEncargado
            ->filter(function ($recibo) {
                if (auth()->check() && auth()->user()->hasRole('vista-costura')) {
                    $notas = (string) ($recibo->notas ?? '');
                    return !preg_match('/parcial_id:(\d+)/i', $notas);
                }

                return true;
            })
            ->map(fn ($recibo) => $this->mapearReciboGlobalCosturaDetalle($recibo, $completadosCorte, $completadosCostura, $completadosControlCalidad))
            ->toArray();
    }

    private function mapearReciboGlobalCosturaDetalle(mixed $recibo, Collection $completadosCorte, Collection $completadosCostura, Collection $completadosControlCalidad): array
    {
        $contexto = $this->supportService->resolverContextoProcesosRecibo($recibo);
        $procesoCostura = $contexto['proceso_costura'];
        $procesoCorte = $contexto['proceso_corte'];
        $procesoControlCalidad = $contexto['proceso_control_calidad'];

        $rid = (int) $recibo->id;
        $completadoCorte = $completadosCorte->has($rid);
        $completadoCostura = $completadosCostura->has($rid);
        $completadoControlCalidad = $completadosControlCalidad->has($rid);

        $tieneParciales = $this->operarioRecibosRepository->existeReciboPorPartes(
            (int) $recibo->pedido_produccion_id,
            (string) $recibo->tipo_recibo,
            (string) $recibo->consecutivo_actual
        );

        $parcialId = $contexto['parcial_id'];
        $esParcial = $contexto['es_parcial'];
        $creadoEn = $this->supportService->resolverCreadoEnRecibo($recibo, $parcialId);

        return [
            'id' => $recibo->id,
            'tipo_recibo' => $recibo->tipo_recibo,
            'consecutivo_actual' => $recibo->consecutivo_actual,
            'consecutivo_inicial' => $recibo->consecutivo_inicial,
            'notas' => $recibo->notas,
            'creado_en' => $creadoEn,
            'fecha_inicio_proceso' => $procesoCostura?->fecha_inicio
                ?? $procesoCorte?->fecha_inicio
                ?? $procesoControlCalidad?->fecha_inicio
                ?? null,
            'fecha_asignacion_costura' => $procesoCostura?->fecha_de_asignacion_encargado ?? null,
            'fecha_proceso_costura_created_at' => $procesoCostura?->created_at ?? null,
            'fecha_asignacion_corte' => $procesoCorte?->fecha_de_asignacion_encargado ?? null,
            'fecha_proceso_corte_created_at' => $procesoCorte?->created_at ?? null,
            'fecha_proceso_created_at' => $procesoCostura?->created_at
                ?? $procesoCorte?->created_at
                ?? $procesoControlCalidad?->created_at
                ?? null,
            'fecha_asignacion_proceso' => $procesoCostura?->fecha_de_asignacion_encargado
                ?? $procesoCorte?->fecha_de_asignacion_encargado
                ?? $procesoControlCalidad?->fecha_de_asignacion_encargado
                ?? null,
            'area' => $recibo->area,
            'proceso_id_costura' => $procesoCostura ? $procesoCostura->id : null,
            'encargado_costura' => $procesoCostura ? $procesoCostura->encargado : null,
            'encargado_corte' => $procesoCorte ? $procesoCorte->encargado : null,
            'encargado_control_calidad' => $procesoControlCalidad ? $procesoControlCalidad->encargado : null,
            'completado_corte' => $completadoCorte,
            'completado_costura' => $completadoCostura,
            'completado_control_calidad' => $completadoControlCalidad,
            'es_parcial' => $esParcial,
            'pedido_parcial_id' => $parcialId,
            'tiene_parciales' => $tieneParciales,
        ];
    }

    private function deduplicarRecibos(Collection $recibos): Collection
    {
        return $recibos->sortByDesc('created_at')
            ->unique(fn ($recibo) => $this->buildUniqueReciboKey($recibo, true))
            ->sortBy('created_at')
            ->values();
    }

    private function buildUniqueReciboKey(mixed $recibo, bool $includeFallbackPedido): string
    {
        $key = $recibo->prenda_id ? (string) $recibo->prenda_id : ($includeFallbackPedido ? 'pedido_' . $recibo->pedido_produccion_id : '');
        $notas = (string) ($recibo->notas ?? '');
        $parcialSuffix = '';
        if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
            $parcialSuffix = '_p' . $matches[1];
        }

        return $key . '_' . $recibo->tipo_recibo . $parcialSuffix;
    }
}
