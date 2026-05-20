<?php

namespace App\Infrastructure\Services\Operario;

use App\Infrastructure\Repositories\Operario\OperarioRecibosRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\User;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosListadoService
{
    public function __construct(
        private readonly OperarioRecibosRepository $operarioRecibosRepository,
        private readonly ObtenerPrendasRecibosSupportService $supportService,
        private readonly ObtenerPrendasRecibosParcialesService $parcialesService
    ) {}

    public function obtenerPrendasConRecibos(User $usuario, ?string $filtroRecibo = null): Collection
    {
        $tipoOperario = $this->supportService->obtenerTipoOperario($usuario);
        $this->supportService->logTipoOperario($usuario, $tipoOperario);
        $tiposRecibo = $this->supportService->resolverTiposRecibo($tipoOperario, $filtroRecibo);

        if ($tipoOperario === 'vista-costura' && $filtroRecibo === 'bodega') {
            return $this->obtenerPrendasConRecibosBodegaVistaCostura();
        }

        if (empty($tiposRecibo)) {
            return collect();
        }

        $recibos = $this->supportService->cargarRecibosBaseParaOperario($usuario, $tipoOperario, $tiposRecibo);
        $recibos = $this->supportService->filtrarRecibosVisualizadorPlooter($recibos, $usuario, $tipoOperario);
        $recibos = $this->agregarReflectivosAprobadosSiAplica($recibos, $usuario, $tipoOperario);
        $recibos = $this->deduplicarRecibos($recibos);
        $recibos = $this->aplicarFiltroAreaFinalPorTipoOperario($recibos, $tipoOperario);

        if (config('app.debug') && request()->boolean('debug_operario_dashboard')) {
            \Log::info(' [ObtenerPrendasRecibosService] Recibos encontrados', [
                'total_recibos' => $recibos->count(),
                'tipos_buscados' => $tiposRecibo,
                'areas_permitidas' => ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad'],
                'prenda_ids' => $recibos->pluck('prenda_id')->toArray(),
                'tipo_operario' => $tipoOperario,
                'incluye_reflectivos_aprobados' => ($tipoOperario === 'costura-reflectivo' || $tipoOperario === 'vista-costura') ? 'SI' : 'NO',
            ]);
        }

        $auxiliares = $this->construirAuxiliaresRecibosOperario($recibos);
        $prendasAgrupadas = $recibos
            ->groupBy(fn ($recibo) => $this->obtenerClaveAgrupacionOperario($recibo, $tipoOperario))
            ->flatMap(fn ($recibosDelaPrenda) => $this->mapearGrupoRecibosOperario(
                $recibosDelaPrenda,
                $tipoOperario,
                $usuario,
                $auxiliares['completados_corte'],
                $auxiliares['completados_costura'],
                $auxiliares['fechas_costura'],
                $auxiliares['completados_control_calidad'],
                $auxiliares['parcial_id_por_recibo'],
                $auxiliares['parcial_created_at'],
                $auxiliares['recibo_por_partes_key_map']
            ))
            ->values();

        $resultadoFinal = $prendasAgrupadas;
        if ($tipoOperario !== 'vista-costura') {
            $resultadoFinal = $resultadoFinal->concat($this->parcialesService->obtenerPrendasParcialesCostura($usuario, false));
        }

        $resultadoFinal = $resultadoFinal->unique(function ($item) {
            $parcialId = $item['pedido_parcial_id'] ?? null;
            $isReciboPorPartes = $item['es_recibo_por_partes'] ?? false;
            $prefix = $isReciboPorPartes ? 'rx_' : 'an_';
            return $item['prenda_id'] . ($parcialId ? '_' . $prefix . $parcialId : '');
        });

        return $this->supportService->ordenarResultadoFinalPorTipoOperario($resultadoFinal, $tipoOperario);
    }

    public function obtenerPrendasConRecibosBodegaVistaCostura(bool $desglosarParciales = false): Collection
    {
        $recibos = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['costura', 'control calidad'])
                    ->orWhereRaw('LOWER(TRIM(area)) = ?', ['control de calidad']);
            })
            ->whereNotNull('prenda_bodega_id')
            ->with([
                'prendaBodega:id,nombre,descripcion,created_at',
            ])
            ->select([
                'id',
                'prenda_bodega_id',
                'pedido_produccion_id',
                'tipo_recibo',
                'consecutivo_actual',
                'consecutivo_inicial',
                'notas',
                'area',
                'estado',
                'created_at',
                'activo',
            ])
            ->orderByDesc('created_at')
            ->get();

        if ($recibos->isEmpty()) {
            return collect();
        }

        $procesos = ProcesoPrenda::query()
            ->whereIn('prenda_bodega_id', $recibos->pluck('prenda_bodega_id')->filter()->unique()->values())
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('prenda_bodega_id');

        return $recibos
            ->groupBy('prenda_bodega_id')
            ->flatMap(function (Collection $recibosDePrenda) use ($procesos, $desglosarParciales) {
                $reciboPrincipal = $recibosDePrenda->first();
                $prendaBodega = $reciboPrincipal->prendaBodega;
                $procesoCostura = $procesos->get($reciboPrincipal->prenda_bodega_id)?->first();
                $pedidoIdBodega = (int) ($reciboPrincipal->pedido_produccion_id ?? 0);
                $consecutivoOriginalBodega = (string) ($reciboPrincipal->consecutivo_actual ?? '');
                $prendaBodegaId = (int) ($reciboPrincipal->prenda_bodega_id ?? 0);

                $tieneParcialesBodega = false;
                if ($pedidoIdBodega > 0) {
                    $tieneParcialesBodega = $this->operarioRecibosRepository->existeReciboPorPartes(
                        $pedidoIdBodega,
                        'CORTE-PARA-BODEGA',
                        $consecutivoOriginalBodega,
                        $prendaBodegaId
                    );
                } elseif ($prendaBodegaId > 0 && $consecutivoOriginalBodega !== '') {
                    // Recibos internos: fallback sin pedido_produccion_id.
                    $tieneParcialesBodega = \Illuminate\Support\Facades\DB::table('recibo_por_partes')
                        ->where('prenda_pedido_id', $prendaBodegaId)
                        ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                        ->where('consecutivo_original', $consecutivoOriginalBodega)
                        ->exists();
                }

                $consecutivoActual = (string) ($reciboPrincipal->consecutivo_actual ?? '');
                $nombrePrenda = (string) ($prendaBodega->nombre ?? 'N/A');
                $descripcion = (string) ($prendaBodega->descripcion ?? '');

                if ($desglosarParciales && $tieneParcialesBodega) {
                    $parcialesQuery = \Illuminate\Support\Facades\DB::table('recibo_por_partes')
                        ->where('prenda_pedido_id', $prendaBodegaId)
                        ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                        ->where('consecutivo_original', $consecutivoOriginalBodega)
                        ->orderBy('consecutivo_parcial');

                    if ($pedidoIdBodega > 0) {
                        $parcialesQuery->where('pedido_produccion_id', $pedidoIdBodega);
                    }

                    $parciales = $parcialesQuery->get();

                    return $parciales->map(function ($parcial) use ($prendaBodegaId, $pedidoIdBodega, $nombrePrenda, $descripcion) {
                        $consecutivoParcialRaw = (string) ($parcial->consecutivo_parcial ?? '');
                        $consecutivoParcial = str_contains($consecutivoParcialRaw, '.')
                            ? rtrim(rtrim($consecutivoParcialRaw, '0'), '.')
                            : $consecutivoParcialRaw;

                        $procesoParcial = \App\Models\ProcesoPrenda::query()
                            ->where('prenda_bodega_id', $prendaBodegaId)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                            ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                            ->orderByDesc('created_at')
                            ->first();

                        $tallas = \Illuminate\Support\Facades\DB::table('recibos_por_partes_tallas')
                            ->where('recibo_por_partes_id', (int) $parcial->id)
                            ->get(['id', 'genero', 'talla', 'cantidad', 'color_nombre'])
                            ->map(function ($t) {
                                return [
                                    'id' => (int) ($t->id ?? 0),
                                    'genero' => $t->genero ?? null,
                                    'talla' => $t->talla ?? null,
                                    'cantidad' => (int) ($t->cantidad ?? 0),
                                    'tipo_talla' => null,
                                    'es_sobremedida' => false,
                                    'tela' => null,
                                    'colores' => !empty($t->color_nombre) ? [(string) $t->color_nombre] : [],
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'prenda_id' => $prendaBodegaId,
                            'pedido_id' => $pedidoIdBodega > 0 ? $pedidoIdBodega : null,
                            'pedido_parcial_id' => (int) $parcial->id,
                            'es_recibo_por_partes' => true,
                            'es_parcial' => true,
                            'numero_pedido' => $consecutivoParcial,
                            'cliente' => 'BODEGA',
                            'nombre_prenda' => $nombrePrenda,
                            'descripcion' => $descripcion,
                            'de_bodega' => true,
                            'tallas' => $tallas,
                            'tiene_parciales' => true,
                            'estado_pedido' => 'PENDIENTE',
                            'fecha_creacion' => $parcial->created_at ?? now(),
                            'tipo_recibo' => 'CORTE-PARA-BODEGA',
                            'proceso_actual' => 'Costura',
                            'encargado_costura' => $procesoParcial?->encargado,
                            'proceso_id_costura' => $procesoParcial?->id,
                            'recibos' => [[
                                'id' => null,
                                'tipo_recibo' => 'CORTE-PARA-BODEGA',
                                'consecutivo_actual' => $consecutivoParcial,
                                'consecutivo_inicial' => (string) ($parcial->consecutivo_original ?? ''),
                                'consecutivo_parcial' => $consecutivoParcial,
                                'area' => 'Costura',
                                'estado' => 'En Ejecución',
                                'prenda_bodega_id' => $prendaBodegaId,
                                'pedido_produccion_id' => $pedidoIdBodega > 0 ? $pedidoIdBodega : null,
                                'encargado_costura' => $procesoParcial?->encargado,
                                'proceso_id_costura' => $procesoParcial?->id,
                                'tiene_parciales' => true,
                                'created_at' => $parcial->created_at ?? now(),
                                'creado_en' => $parcial->created_at ?? now(),
                                'pedido_parcial_id' => (int) $parcial->id,
                                'completado_corte' => false,
                                'completado_costura' => false,
                                'completado_control_calidad' => false,
                            ]],
                            'total_recibos' => 1,
                        ];
                    })->values();
                }

                return collect([[
                    'prenda_id' => (int) ($reciboPrincipal->prenda_bodega_id ?? 0),
                    'pedido_id' => $pedidoIdBodega > 0 ? $pedidoIdBodega : null,
                    'numero_pedido' => $consecutivoActual,
                    'cliente' => 'BODEGA',
                    'nombre_prenda' => $nombrePrenda,
                    'descripcion' => $descripcion,
                    'de_bodega' => true,
                    'tiene_parciales' => $tieneParcialesBodega,
                    'es_parcial' => false,
                    'parcial_id' => null,
                    'estado_pedido' => (string) ($reciboPrincipal->estado ?? 'PENDIENTE'),
                    'fecha_creacion' => $reciboPrincipal->created_at,
                    'tipo_recibo' => 'CORTE-PARA-BODEGA',
                    'proceso_actual' => 'Bodega',
                    'encargado_costura' => $procesoCostura?->encargado,
                    'proceso_id_costura' => $procesoCostura?->id,
                    'recibos' => [[
                        'id' => $reciboPrincipal->id,
                        'tipo_recibo' => 'CORTE-PARA-BODEGA',
                        'consecutivo_actual' => $consecutivoActual,
                        'consecutivo_inicial' => (string) ($reciboPrincipal->consecutivo_inicial ?? $consecutivoActual),
                        'area' => $reciboPrincipal->area,
                        'estado' => $reciboPrincipal->estado,
                        'prenda_bodega_id' => $reciboPrincipal->prenda_bodega_id,
                        'pedido_produccion_id' => $pedidoIdBodega > 0 ? $pedidoIdBodega : null,
                        'encargado_costura' => $procesoCostura?->encargado,
                        'proceso_id_costura' => $procesoCostura?->id,
                        'tiene_parciales' => $tieneParcialesBodega,
                        'created_at' => $reciboPrincipal->created_at,
                        'creado_en' => $reciboPrincipal->created_at,
                        'pedido_parcial_id' => null,
                        'completado_corte' => false,
                        'completado_costura' => false,
                        'completado_control_calidad' => false,
                    ]],
                ]]);
            })
            ->values();
    }

    private function mapearGrupoRecibosOperario(
        Collection $recibosDelaPrenda,
        string $tipoOperario,
        User $usuario,
        Collection $completadosCorteMap,
        Collection $completadosCosturaMap,
        Collection $fechaCompletadoCosturaMap,
        Collection $completadosControlCalidadMap,
        array $parcialIdByReciboId,
        Collection $parcialCreatedAtMap,
        array $reciboPorPartesKeyMap
    ): array {
        $primeRecibo = $recibosDelaPrenda->first();
        [$prenda, $pedido] = $this->resolverGrupoPrendaPedidoOperario($recibosDelaPrenda);

        if (!$prenda || !$pedido) {
            if (config('app.debug') && request()->boolean('debug_operario_dashboard')) {
                \Log::info(' [Filtro] No se pudo obtener prenda o pedido');
            }
            return [];
        }

        $resultados = [];
        foreach ($this->agruparRecibosDelGrupoPorTipo($recibosDelaPrenda) as $tipoRecibo => $recibosDelTipo) {
            $recibosDelTipo = $this->filtrarRecibosDelTipoGrupo($recibosDelTipo, $tipoOperario, $usuario, $prenda, $pedido);
            if ($recibosDelTipo->isEmpty()) {
                continue;
            }

            $parcialIdPadre = $this->obtenerParcialIdPadreDelGrupo($recibosDelTipo);
            $recibosDelTipo = $this->ordenarRecibosDelGrupo($recibosDelTipo, $tipoOperario);
            $fechaOrdenPrincipal = $this->obtenerFechaOrdenPrincipalDelGrupo($recibosDelTipo, $tipoOperario);

            $resultados[] = $this->construirTarjetaGrupoRecibosOperario(
                $prenda,
                $pedido,
                $parcialIdPadre,
                $recibosDelTipo,
                $fechaOrdenPrincipal,
                $tipoOperario,
                $completadosCorteMap,
                $completadosCosturaMap,
                $fechaCompletadoCosturaMap,
                $completadosControlCalidadMap,
                $parcialIdByReciboId,
                $parcialCreatedAtMap,
                $reciboPorPartesKeyMap
            );
        }

        return $resultados;
    }

    private function mapearReciboDetalleOperario(
        mixed $recibo,
        string $tipoOperario,
        Collection $recibosDelTipo,
        Collection $completadosCorteMap,
        Collection $completadosCosturaMap,
        Collection $fechaCompletadoCosturaMap,
        Collection $completadosControlCalidadMap,
        array $parcialIdByReciboId,
        Collection $parcialCreatedAtMap,
        array $reciboPorPartesKeyMap
    ): array {
        $contexto = $this->supportService->resolverContextoProcesosRecibo($recibo);
        $procesoCostura = $contexto['proceso_costura'];
        $procesoCorte = $contexto['proceso_corte'];
        $procesoControlCalidad = $contexto['proceso_control_calidad'];

        $rid = (int) $recibo->id;
        $completadoCorte = $completadosCorteMap->has($rid);
        $completadoCostura = $completadosCosturaMap->has($rid);
        $completadoControlCalidad = $completadosControlCalidadMap->has($rid);

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
            'created_at' => $recibo->created_at,
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

    private function resolverGrupoPrendaPedidoOperario(Collection $recibosDelaPrenda): array
    {
        $primeRecibo = $recibosDelaPrenda->first();

        if ($primeRecibo->prenda_id && $primeRecibo->prenda) {
            $prenda = $primeRecibo->prenda;
            $pedido = $prenda->pedidoProduccion;
            return [$prenda, $pedido];
        }

        if ($primeRecibo->pedido_produccion_id && $primeRecibo->pedido) {
            $pedido = $primeRecibo->pedido;
            $prenda = $pedido->prendas->first();
            return [$prenda, $pedido];
        }

        return [null, null];
    }

    private function agruparRecibosDelGrupoPorTipo(Collection $recibosDelaPrenda): Collection
    {
        return $recibosDelaPrenda->groupBy('tipo_recibo');
    }

    private function filtrarRecibosDelTipoGrupo(
        Collection $recibosDelTipo,
        string $tipoOperario,
        User $usuario,
        mixed $prenda,
        mixed $pedido
    ): Collection {
        return $recibosDelTipo->filter(function ($recibo) use ($tipoOperario, $usuario, $prenda, $pedido, $recibosDelTipo) {
            if ($tipoOperario !== 'vista-costura') {
                return true;
            }

            $notas = (string) ($recibo->notas ?? '');
            $esParcial = preg_match('/parcial_id:(\d+)/i', $notas) === 1;
            if (!$esParcial) {
                return true;
            }

            $hayPadre = $recibosDelTipo->contains(function ($r) {
                $notasR = (string) ($r->notas ?? '');
                return preg_match('/parcial_id:(\d+)/i', $notasR) !== 1;
            });

            return !$hayPadre;
        })->values();
    }

    private function filtrarRecibosTarjetaVistaCostura(Collection $recibosDelTipo, string $tipoOperario): Collection
    {
        return $recibosDelTipo->filter(function ($recibo) use ($tipoOperario, $recibosDelTipo) {
            if ($tipoOperario !== 'vista-costura') {
                return true;
            }

            $notas = (string) ($recibo->notas ?? '');
            $esParcial = preg_match('/parcial_id:(\d+)/i', $notas) === 1;
            if (!$esParcial) {
                return true;
            }

            $hayPadre = $recibosDelTipo->contains(function ($r) {
                $notasR = (string) ($r->notas ?? '');
                return preg_match('/parcial_id:(\d+)/i', $notasR) !== 1;
            });

            return !$hayPadre;
        })->values();
    }

    private function obtenerParcialIdPadreDelGrupo(Collection $recibosDelTipo): ?int
    {
        foreach ($recibosDelTipo as $recibo) {
            $notas = (string) ($recibo->notas ?? '');
            if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function ordenarRecibosDelGrupo(Collection $recibosDelTipo, string $tipoOperario): Collection
    {
        return $recibosDelTipo->sortBy(function ($recibo) use ($tipoOperario) {
            if ($tipoOperario === 'vista-costura') {
                return $recibo->created_at;
            }

            $fechaCorte = $this->supportService->obtenerFechaLlegadaACorte($recibo);
            return $this->supportService->normalizarFechaAOrdenable($fechaCorte ?? $recibo->created_at);
        })->values();
    }

    private function obtenerFechaOrdenPrincipalDelGrupo(Collection $recibosDelTipo, string $tipoOperario): mixed
    {
        $primer = $recibosDelTipo->first();
        if (!$primer) {
            return null;
        }

        if ($tipoOperario === 'vista-costura') {
            return $primer->created_at;
        }

        $fechaCortePrincipal = $this->supportService->obtenerFechaLlegadaACorte($primer);
        return !empty($fechaCortePrincipal) ? $fechaCortePrincipal : $primer->created_at;
    }

    private function construirTarjetaGrupoRecibosOperario(
        mixed $prenda,
        mixed $pedido,
        ?int $parcialIdPadre,
        Collection $recibosDelTipo,
        mixed $fechaOrdenPrincipal,
        string $tipoOperario,
        Collection $completadosCorteMap,
        Collection $completadosCosturaMap,
        Collection $fechaCompletadoCosturaMap,
        Collection $completadosControlCalidadMap,
        array $parcialIdByReciboId,
        Collection $parcialCreatedAtMap,
        array $reciboPorPartesKeyMap
    ): array {
        return [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'pedido_parcial_id' => $parcialIdPadre,
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
            'recibos' => $this->filtrarRecibosTarjetaVistaCostura($recibosDelTipo, $tipoOperario)
                ->map(fn ($recibo) => $this->mapearReciboDetalleOperario(
                    $recibo,
                    $tipoOperario,
                    $recibosDelTipo,
                    $completadosCorteMap,
                    $completadosCosturaMap,
                    $fechaCompletadoCosturaMap,
                    $completadosControlCalidadMap,
                    $parcialIdByReciboId,
                    $parcialCreatedAtMap,
                    $reciboPorPartesKeyMap
                ))->toArray(),
            'fecha_creacion' => $fechaOrdenPrincipal ?? $prenda->created_at,
        ];
    }

    private function construirAuxiliaresRecibosOperario(Collection $recibos): array
    {
        $reciboIds = $recibos->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $completadosRows = $this->operarioRecibosRepository->obtenerCompletadosPorReciboIds($reciboIds);

        $completadosCorteMap = $completadosRows
            ->filter(fn ($r) => strtolower(trim((string) ($r->area ?? ''))) === 'corte')
            ->pluck('id_recibo')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $completadosCosturaRows = $completadosRows
            ->filter(fn ($r) => strtolower(trim((string) ($r->area ?? ''))) === 'costura')
            ->values();
        $completadosCosturaMap = $completadosCosturaRows
            ->pluck('id_recibo')
            ->map(fn ($id) => (int) $id)
            ->flip();
        $fechaCompletadoCosturaMap = $completadosCosturaRows
            ->mapWithKeys(fn ($r) => [(int) $r->id_recibo => $r->fecha_completado]);

        $completadosControlCalidadMap = $completadosRows
            ->filter(function ($r) {
                $area = strtolower(trim((string) ($r->area ?? '')));
                return $area === 'control calidad' || $area === 'control de calidad';
            })
            ->pluck('id_recibo')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $parcialIdByReciboId = [];
        foreach ($recibos as $reciboParse) {
            $notasParse = isset($reciboParse->notas) ? (string) $reciboParse->notas : '';
            if ($notasParse !== '' && preg_match('/parcial_id:(\d+)/i', $notasParse, $matches)) {
                $parcialIdByReciboId[(int) $reciboParse->id] = (int) $matches[1];
            }
        }

        return [
            'completados_corte' => $completadosCorteMap,
            'completados_costura' => $completadosCosturaMap,
            'fechas_costura' => $fechaCompletadoCosturaMap,
            'completados_control_calidad' => $completadosControlCalidadMap,
            'parcial_id_por_recibo' => $parcialIdByReciboId,
            'parcial_created_at' => $this->operarioRecibosRepository->obtenerPedidosParcialesCreatedAtMap(array_values($parcialIdByReciboId)),
            'recibo_por_partes_key_map' => $this->operarioRecibosRepository->obtenerReciboPorPartesKeys(),
        ];
    }

    private function obtenerClaveAgrupacionOperario(mixed $recibo, string $tipoOperario): string
    {
        $parcialId = '';
        if ($tipoOperario !== 'vista-costura') {
            $notas = (string) ($recibo->notas ?? '');
            if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialId = '_' . $matches[1];
            }
        }

        if ($recibo->prenda_id) {
            return 'prenda_' . $recibo->prenda_id . $parcialId;
        }

        return 'pedido_' . $recibo->pedido_produccion_id . $parcialId;
    }

    private function agregarReflectivosAprobadosSiAplica(Collection $recibos, User $usuario, string $tipoOperario): Collection
    {
        if (!in_array($tipoOperario, ['costura-reflectivo', 'vista-costura'], true)) {
            return $recibos;
        }

        if (config('app.debug') && request()->boolean('debug_operario_dashboard')) {
            \Log::info(' [REFLECTIVO APROBADOS] BUSCANDO prendas con PROCESO REFLECTIVO APROBADO en pedidos_procesos_prenda_detalles', [
                'usuario' => $usuario->name,
                'recibos_costura_actuales' => $recibos->count(),
            ]);
        }

        $prendasReflectivoAprobadas = PedidosProcesosPrendaDetalle::where('tipo_proceso_id', 1)
            ->where('estado', 'APROBADO')
            ->with(['prenda', 'prenda.pedidoProduccion'])
            ->get()
            ->pluck('prenda')
            ->unique('id');

        if (config('app.debug') && request()->boolean('debug_operario_dashboard')) {
            \Log::info(' [REFLECTIVO APROBADOS] Prendas con PROCESO REFLECTIVO aprobado encontradas', [
                'total_prendas_reflectivo_aprobadas' => count($prendasReflectivoAprobadas),
            ]);
        }

        $prendasIds = $prendasReflectivoAprobadas->pluck('id')->all();
        $recibosReflectivosMap = [];
        if (!empty($prendasIds)) {
            $recibosReflectivos = ConsecutivoReciboPedido::query()
                ->whereIn('prenda_id', $prendasIds)
                ->where('tipo_recibo', 'REFLECTIVO')
                ->where('activo', 1)
                ->get();

            foreach ($recibosReflectivos as $recibo) {
                $recibosReflectivosMap[$recibo->prenda_id] = $recibo;
            }
        }

        foreach ($prendasReflectivoAprobadas as $prendaAprobada) {
            if (!$prendaAprobada || !$prendaAprobada->pedidoProduccion) {
                continue;
            }

            $reciboReflectivo = $recibosReflectivosMap[$prendaAprobada->id] ?? null;
            if (!$reciboReflectivo) {
                continue;
            }

            if ($tipoOperario === 'costura-reflectivo') {
                $area = strtolower(trim((string) ($reciboReflectivo->area ?? '')));
                if ($area !== 'costura') {
                    continue;
                }
            }

            $recibos->push($reciboReflectivo);
        }

        $recibosReflectivoAnexos = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', 'REFLECTIVO')
            ->whereNotNull('notas')
            ->whereRaw('LOWER(notas) LIKE ?', ['%parcial_id:%']);
        if ($tipoOperario === 'costura-reflectivo') {
            $recibosReflectivoAnexos = $recibosReflectivoAnexos->whereRaw('LOWER(TRIM(area)) = ?', ['costura']);
        }

        $recibosReflectivoAnexos = $recibosReflectivoAnexos->get();
        if ($recibosReflectivoAnexos->isNotEmpty()) {
            $recibos = $recibos->concat($recibosReflectivoAnexos);
        }

        return $recibos->sortByDesc('created_at')
            ->unique(fn ($recibo) => $this->buildUniqueReciboKey($recibo, false))
            ->sortBy('created_at')
            ->values();
    }

    private function deduplicarRecibos(Collection $recibos): Collection
    {
        return $recibos->sortByDesc('created_at')
            ->unique(fn ($recibo) => $this->buildUniqueReciboKey($recibo, true))
            ->sortBy('created_at')
            ->values();
    }

    private function aplicarFiltroAreaFinalPorTipoOperario(Collection $recibos, string $tipoOperario): Collection
    {
        if ($tipoOperario === 'vista-costura') {
            return $recibos->filter(function ($recibo) {
                $area = strtolower(trim((string) ($recibo->area ?? '')));
                $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));
                if ($tipoRecibo === 'COSTURA' || $tipoRecibo === 'COSTURA-BODEGA') {
                    return in_array($area, ['costura', 'corte'], true);
                }
                return $tipoRecibo === 'REFLECTIVO';
            })->values();
        }

        if ($tipoOperario === 'costura-reflectivo') {
            return $recibos->filter(function ($recibo) {
                $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));
                $area = strtolower(trim((string) ($recibo->area ?? '')));
                if ($tipoRecibo === 'COSTURA' || $tipoRecibo === 'COSTURA-BODEGA') {
                    return $area === 'costura';
                }
                if ($tipoRecibo === 'REFLECTIVO') {
                    return $area === 'costura';
                }
                return false;
            })->values();
        }

        if ($tipoOperario === 'bodeguero') {
            return $recibos->filter(function ($recibo) {
                $area = strtolower(trim((string) ($recibo->area ?? '')));
                $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));
                return $tipoRecibo === 'COSTURA-BODEGA' && $area === 'costura';
            })->values();
        }

        return $recibos;
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
