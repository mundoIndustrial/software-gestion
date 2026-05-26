<?php

namespace App\Application\Operario\Services;

use App\Infrastructure\Repositories\Operario\OperarioDashboardRepository;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OperarioDashboardVistaCosturaService
{
    public function __construct(
        private OperarioDashboardRepository $operarioDashboardRepository,
    ) {}

    public function formatearRecibosControlCalidadParaDashboard(array $recibos, string $tipoRecibo): Collection
    {
        return collect($recibos)
            ->map(function (array $item) use ($tipoRecibo) {
                $reciboPrincipal = collect($item['recibos'] ?? [])->first() ?? [];
                $fechaCreacion = $item['fecha_creacion'] ?? ($reciboPrincipal['creado_en'] ?? null);
                $consecutivoActual = $reciboPrincipal['consecutivo_actual']
                    ?? $item['consecutivo_actual']
                    ?? '';
                $consecutivoInicial = $reciboPrincipal['consecutivo_inicial']
                    ?? $item['consecutivo_inicial']
                    ?? $consecutivoActual;
                $esParcial = (bool) ($item['es_parcial'] ?? ($reciboPrincipal['es_parcial'] ?? false));
                $parcialId = $item['parcial_id'] ?? ($reciboPrincipal['parcial_id'] ?? null);

                return [
                    'prenda_id' => (int) ($item['prenda_id'] ?? 0),
                    'pedido_id' => (int) ($item['pedido_id'] ?? 0),
                    'numero_pedido' => (string) ($item['numero_pedido'] ?? ''),
                    'cliente' => (string) ($item['cliente'] ?? ''),
                    'nombre_prenda' => (string) ($item['nombre_prenda'] ?? ''),
                    'descripcion' => (string) ($item['descripcion'] ?? ''),
                    'proceso_actual' => (string) ($item['proceso_actual'] ?? 'Control Calidad'),
                    'de_bodega' => $item['de_bodega'] ?? null,
                    'tiene_parciales' => (bool) ($item['tiene_parciales'] ?? false),
                    'es_parcial' => $esParcial,
                    'parcial_id' => $parcialId,
                    'estado_pedido' => (string) ($item['estado_pedido'] ?? 'Pendiente'),
                    'fecha_creacion' => $fechaCreacion,
                    'tipo_recibo' => $tipoRecibo,
                    'recibos' => [[
                        'id' => (int) ($reciboPrincipal['id'] ?? ($item['id'] ?? 0)),
                        'tipo_recibo' => $tipoRecibo,
                        'consecutivo_actual' => $consecutivoActual,
                        'consecutivo_inicial' => $consecutivoInicial,
                        'notas' => (string) ($reciboPrincipal['notas'] ?? ''),
                        'creado_en' => $fechaCreacion,
                        'created_at' => $fechaCreacion,
                        'area' => (string) ($reciboPrincipal['area'] ?? 'Control Calidad'),
                        'es_parcial' => $esParcial,
                        'parcial_id' => $parcialId,
                        'pedido_parcial_id' => $parcialId,
                        'tiene_parciales' => (bool) ($item['tiene_parciales'] ?? false),
                        'encargado_costura' => $reciboPrincipal['encargado_costura'] ?? null,
                        'encargado_corte' => $reciboPrincipal['encargado_corte'] ?? null,
                        'encargado_control_calidad' => $reciboPrincipal['encargado_control_calidad'] ?? null,
                        'completado_area' => (bool) ($reciboPrincipal['completado_area'] ?? false),
                        'completado_corte' => (bool) ($reciboPrincipal['completado_corte'] ?? false),
                        'completado_costura' => (bool) ($reciboPrincipal['completado_costura'] ?? false),
                        'completado_control_calidad' => (bool) ($reciboPrincipal['completado_area'] ?? false),
                    ]],
                    'total_recibos' => 1,
                ];
            })
            ->sortBy(fn (array $item) => $item['fecha_creacion'] instanceof \DateTimeInterface
                ? $item['fecha_creacion']->getTimestamp()
                : ((is_numeric($item['fecha_creacion'])
                    ? (int) $item['fecha_creacion']
                    : (strtotime((string) $item['fecha_creacion']) ?: 0))))
            ->values();
    }

    public function filtrarPrendasControlCalidadPorBusqueda(Collection $prendas, string $busqueda): Collection
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return $prendas->values();
        }

        return $prendas->filter(function (array $prenda) use ($busqueda) {
            // Buscar por nombre del cliente
            $cliente = strtolower(trim((string) ($prenda['cliente'] ?? '')));
            if ($cliente !== '' && str_contains($cliente, $busqueda)) {
                return true;
            }

            // Buscar por número de recibo (consecutivo_actual o consecutivo_inicial)
            foreach (($prenda['recibos'] ?? []) as $recibo) {
                $consecutivoActual = strtolower(trim((string) ($recibo['consecutivo_actual'] ?? '')));
                $consecutivoInicial = strtolower(trim((string) ($recibo['consecutivo_inicial'] ?? '')));

                if ($consecutivoActual !== '' && str_contains($consecutivoActual, $busqueda)) {
                    return true;
                }

                if ($consecutivoInicial !== '' && str_contains($consecutivoInicial, $busqueda)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    public function buscarResultadosBusquedaVistaCosturaFueraDeArea(string $busqueda, ?string $filtroRecibo): Collection
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return collect();
        }

        $esNumerica = ctype_digit($busqueda);
        $comodin = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
        $areasVisibles = $filtroRecibo === 'reflectivo'
            ? ['costura']
            : (in_array($filtroRecibo, ['bodega'], true)
                ? ['costura', 'control calidad', 'control de calidad']
                : ['corte', 'costura', 'control calidad', 'control de calidad']);
        $tiposPermitidos = $this->tiposReciboBusquedaVistaCostura($filtroRecibo);

        return $this->operarioDashboardRepository
            ->buscarCoincidenciasVistaCosturaFueraDeArea($comodin, $esNumerica, $tiposPermitidos)
            ->filter(function ($row) use ($areasVisibles) {
                $area = strtolower(trim((string) ($row->area ?? '')));
                return !in_array($area, $areasVisibles, true);
            })
            ->map(function ($row) {
                $area = trim((string) ($row->area ?? ''));
                $estado = trim((string) ($row->estado ?? ''));
                $tipoRecibo = strtoupper(trim((string) ($row->tipo_recibo ?? '')));
                $consecutivoActual = (string) ($row->consecutivo_actual ?? '');
                $consecutivoInicial = (string) ($row->consecutivo_inicial ?? $consecutivoActual);
                $fechaCreacion = $row->created_at ?? null;
                $notas = (string) ($row->notas ?? '');
                $parcialId = null;

                if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                    $parcialId = (int) ($matches[1] ?? 0);
                    if ($parcialId <= 0) {
                        $parcialId = null;
                    }
                }

                return [
                    'recibo_id' => (int) ($row->id ?? 0),
                    'pedido_id' => (int) ($row->pedido_produccion_id ?? 0),
                    'prenda_id' => (int) ($row->prenda_id ?? 0),
                    'numero_pedido' => (string) ($row->numero_pedido ?? ''),
                    'cliente' => (string) ($row->cliente ?? ''),
                    'nombre_prenda' => (string) ($row->nombre_prenda ?? ''),
                    'descripcion' => (string) ($row->descripcion ?? ''),
                    'tipo_recibo' => $tipoRecibo,
                    'area' => $area,
                    'estado' => $estado,
                    'consecutivo_actual' => $consecutivoActual,
                    'consecutivo_inicial' => $consecutivoInicial,
                    'notas' => $notas,
                    'id_parcial' => $parcialId,
                    'fecha_creacion' => $fechaCreacion,
                    'area_label' => strtoupper($area !== '' ? $area : 'OTRA ÁREA'),
                    'estado_label' => strtoupper($estado !== '' ? $estado : 'SIN ESTADO'),
                ];
            })
            ->values();
    }

    public function resolverMensajeBusquedaVistaCostura(string $busqueda, ?string $filtroRecibo = null): ?string
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return null;
        }

        $esNumerica = ctype_digit($busqueda);
        $comodin = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
        $tiposPermitidos = $this->tiposReciboBusquedaVistaCostura($filtroRecibo);

        $coincidencias = $this->operarioDashboardRepository
            ->buscarCoincidenciasVistaCosturaParaMensaje($comodin, $esNumerica, $tiposPermitidos, 20);

        if ($coincidencias->isEmpty()) {
            return null;
        }

        $anuladas = $coincidencias->filter(function ($row) {
            $estado = strtolower(trim((string) ($row->estado ?? '')));
            $area = strtolower(trim((string) ($row->area ?? '')));

            return in_array($estado, ['anulada', 'anulado'], true)
                || in_array($area, ['anulada', 'anulado'], true);
        });

        if ($anuladas->isNotEmpty()) {
            $numero = $anuladas->first()?->consecutivo_actual ?? $anuladas->first()?->numero_pedido ?? $busqueda;
            return "Encontré coincidencias, pero el recibo #{$numero} está en área Anulado con estado Anulado.";
        }

        $entregaDespacho = $coincidencias->filter(function ($row) {
            $area = strtolower(trim((string) ($row->area ?? '')));
            return in_array($area, ['entrega', 'despacho'], true);
        });

        if ($entregaDespacho->isNotEmpty()) {
            $numero = $entregaDespacho->first()?->consecutivo_actual ?? $entregaDespacho->first()?->numero_pedido ?? $busqueda;
            $area = strtoupper(trim((string) ($entregaDespacho->first()?->area ?? '')));
            return "Encontré coincidencias, pero el recibo #{$numero} ya está en {$area}.";
        }

        if ($filtroRecibo === 'bodega') {
            return 'Encontré coincidencias, pero no están en Bodega.';
        }

        return 'Encontré coincidencias, pero no están en Costura.';
    }

    public function obtenerConteosControlCalidad(): array
    {
        $conteoControlCalidadCostura = ConsecutivoReciboPedido::query()
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('activo', 1)
            ->where('tipo_recibo', 'COSTURA')
            ->count();

        $conteoControlCalidadReflectivo = ConsecutivoReciboPedido::query()
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('activo', 1)
            ->where('tipo_recibo', 'REFLECTIVO')
            ->count();

        $conteoControlCalidadBodega = ConsecutivoReciboPedido::query()
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('activo', 1)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->count();

        $parcialesCCCounts = $this->operarioDashboardRepository->obtenerConteosParcialesControlCalidadPorTipoRecibo();
        foreach ($parcialesCCCounts as $fila) {
            if ($fila->tipo_recibo) {
                $tipoRecibo = strtoupper(trim((string) $fila->tipo_recibo));
                if ($tipoRecibo === 'COSTURA') {
                    $conteoControlCalidadCostura += $fila->total;
                } elseif ($tipoRecibo === 'REFLECTIVO') {
                    $conteoControlCalidadReflectivo += $fila->total;
                }
            }
        }

        return [
            'costura' => $conteoControlCalidadCostura,
            'reflectivo' => $conteoControlCalidadReflectivo,
            'bodega' => $conteoControlCalidadBodega,
        ];
    }

    public function obtenerNombresCosturaReflectivoNormalizados(): Collection
    {
        $rolId = $this->operarioDashboardRepository->obtenerRolIdPorNombre('costura-reflectivo');
        if (!$rolId) {
            return collect();
        }

        return $this->operarioDashboardRepository
            ->obtenerNombresUsuariosPorRolId($rolId)
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();
    }

    public function construirMapaParcialesBodegaDesdePrendas(Collection $prendas): array
    {
        $candidatos = $prendas->map(function ($prenda) {
            $reciboPrincipal = $prenda['recibos'][0] ?? [];
            $consecutivo = (string) ($reciboPrincipal['consecutivo_actual'] ?? $prenda['numero_pedido'] ?? '');

            return [
                'pedido_id' => (int) ($prenda['pedido_id'] ?? 0),
                'prenda_id' => (int) ($prenda['prenda_id'] ?? 0),
                'consecutivo' => is_numeric($consecutivo) ? (int) $consecutivo : 0,
            ];
        })->filter(fn (array $item) => $item['prenda_id'] > 0 && $item['consecutivo'] > 0)->values();

        if ($candidatos->isEmpty()) {
            return [];
        }

        $prendaIds = $candidatos->pluck('prenda_id')->unique()->values()->all();
        $consecutivos = $candidatos->pluck('consecutivo')->unique()->values()->all();

        $rows = $this->operarioDashboardRepository->obtenerParcialesBodegaPorPrendaYConsecutivo($prendaIds, $consecutivos);

        $map = [];
        foreach ($rows as $row) {
            $pedidoId = (int) ($row->pedido_produccion_id ?? 0);
            $prendaId = (int) ($row->prenda_pedido_id ?? 0);
            $consecutivo = (int) ($row->consecutivo_original ?? 0);

            if ($prendaId <= 0 || $consecutivo <= 0) {
                continue;
            }

            $map[$pedidoId . '|' . $prendaId . '|' . $consecutivo] = true;
            $map['0|' . $prendaId . '|' . $consecutivo] = true;
        }

        return $map;
    }

    public function prepararContextoVistaDashboard(
        Request $request,
        Collection $prendasConRecibos,
        Collection $prendasConRecibosControlCalidad,
        ?string $tab
    ): array {
        $usuario = $request->user();
        $esVistaCostura = $usuario?->hasRole('vista-costura') ?? false;

        $filtroReciboActual = strtolower(trim((string) $request->query('filtro', 'costura')));
        if (!in_array($filtroReciboActual, ['costura', 'reflectivo', 'bodega'], true)) {
            $filtroReciboActual = 'costura';
        }

        $filtroEncargadoActual = strtolower(trim((string) $request->query('encargado', 'todos')));
        if (!in_array($filtroEncargadoActual, ['todos', 'sin-encargado', 'control-calidad'], true)) {
            $filtroEncargadoActual = 'todos';
        }

        $busquedaActual = trim((string) $request->query('q', ''));
        $tabActualDashboard = (string) ($tab ?? $request->query('tab', 'pendientes'));
        $modoControlCalidadVistaCostura = $esVistaCostura && $filtroEncargadoActual === 'control-calidad';

        $coleccionBase = $modoControlCalidadVistaCostura
            ? $prendasConRecibosControlCalidad
            : $prendasConRecibos;

        if ($modoControlCalidadVistaCostura && $filtroReciboActual === 'bodega') {
            // En bodega/control-calidad necesitamos:
            // 1) Parciales en C.C. (fuente dedicada de control-calidad)
            // 2) Recibos bodega normales en C.C. (fuente general de bodega, ej. #17)
            $bodegaNormalesEnControlCalidad = $prendasConRecibos->filter(function (array $prenda) {
                $reciboPrincipal = collect($prenda['recibos'] ?? [])->first();
                if (!is_array($reciboPrincipal)) {
                    return false;
                }

                $tipo = strtoupper(trim((string) ($reciboPrincipal['tipo_recibo'] ?? '')));
                $area = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));

                return $tipo === 'CORTE-PARA-BODEGA'
                    && in_array($area, ['control calidad', 'control de calidad'], true);
            });

            $coleccionBase = $prendasConRecibosControlCalidad
                ->concat($bodegaNormalesEnControlCalidad)
                ->unique(function (array $prenda) {
                    $reciboPrincipal = collect($prenda['recibos'] ?? [])->first();
                    $reciboId = is_array($reciboPrincipal) ? ($reciboPrincipal['id'] ?? null) : null;
                    $parcialId = is_array($reciboPrincipal) ? ($reciboPrincipal['pedido_parcial_id'] ?? null) : null;
                    $consecutivo = is_array($reciboPrincipal) ? ($reciboPrincipal['consecutivo_actual'] ?? null) : null;

                    if ($parcialId) {
                        return 'parcial:' . (string) $parcialId;
                    }
                    if ($reciboId) {
                        return 'recibo:' . (string) $reciboId;
                    }

                    return 'consecutivo:' . (string) ($consecutivo ?? ($prenda['numero_pedido'] ?? ''));
                })
                ->values();
        }

        // En modo control-calidad, respetar estrictamente el filtro de tipo de recibo
        // para evitar mezclar tarjetas de costura/reflectivo en la pestaña de bodega.
        if ($modoControlCalidadVistaCostura) {
            $tiposPermitidos = $this->tiposReciboBusquedaVistaCostura($filtroReciboActual);
            $coleccionBase = $coleccionBase->filter(function ($prenda) use ($tiposPermitidos) {
                $recibos = (array) ($prenda['recibos'] ?? []);
                if (empty($recibos)) {
                    return false;
                }

                foreach ($recibos as $recibo) {
                    $tipoRecibo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                    if (in_array($tipoRecibo, $tiposPermitidos, true)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $callbackOrdenamiento = function ($prenda) use ($usuario, $filtroReciboActual, $tabActualDashboard) {
            $reciboPrincipal = collect($prenda['recibos'] ?? [])->first();

            $ordenarLiderReflectivoPorCreacion = ($usuario?->hasRole('lider-reflectivo') ?? false)
                && in_array($filtroReciboActual, ['costura', 'reflectivo'], true);
            $ordenarPorFechaAsignacionProceso = $usuario?->hasAnyRole(['costurero', 'lider-reflectivo', 'administrador-costura']) ?? false;
            $ordenarPorFechaCreacion = $usuario?->hasRole('vista-costura') ?? false;
            $ordenarPorFechaAsignacionCorte = $usuario?->hasRole('cortador') ?? false;

            if (in_array($tabActualDashboard, ['pendientes', 'completados', 'completado-bodega'], true)) {
                $fechaOrden = $reciboPrincipal['created_at']
                    ?? $reciboPrincipal['creado_en']
                    ?? ($prenda['fecha_creacion'] ?? null);
            } elseif ($ordenarLiderReflectivoPorCreacion || $ordenarPorFechaCreacion) {
                $fechaOrden = $reciboPrincipal['created_at']
                    ?? $reciboPrincipal['creado_en']
                    ?? ($prenda['fecha_creacion'] ?? null);
            } elseif ($ordenarPorFechaAsignacionCorte) {
                $fechaOrden = $reciboPrincipal['fecha_asignacion_corte']
                    ?? $reciboPrincipal['fecha_proceso_corte_created_at']
                    ?? ($prenda['fecha_creacion'] ?? null);
            } elseif ($ordenarPorFechaAsignacionProceso) {
                $fechaOrden = $reciboPrincipal['fecha_asignacion_costura']
                    ?? $reciboPrincipal['fecha_asignacion_corte']
                    ?? $reciboPrincipal['fecha_proceso_costura_created_at']
                    ?? ($prenda['fecha_creacion'] ?? null);
            } else {
                $fechaOrden = $reciboPrincipal['fecha_proceso_created_at']
                    ?? ($prenda['fecha_creacion'] ?? null);
            }

            if ($fechaOrden instanceof \DateTimeInterface) {
                return $fechaOrden->getTimestamp();
            }
            if (is_numeric($fechaOrden)) {
                return (int) $fechaOrden;
            }
            if (is_string($fechaOrden) && trim($fechaOrden) !== '') {
                return strtotime($fechaOrden) ?: 0;
            }

            return 0;
        };

        $ordenAscendente = true;
        if (($usuario?->hasRole('lider-reflectivo') ?? false) || ($usuario?->hasRole('administrador-costura') ?? false)) {
            if (!(($usuario?->hasRole('lider-reflectivo') ?? false) && in_array($filtroReciboActual, ['costura', 'reflectivo'], true))) {
                $ordenAscendente = false;
            }
        }

        $prendasOrdenadas = $ordenAscendente
            ? $coleccionBase->sortBy($callbackOrdenamiento)->values()
            : $coleccionBase->sortByDesc($callbackOrdenamiento)->values();

        $pageNameVistaCostura = 'page_vc_' . str_replace('-', '_', $filtroReciboActual . '_' . $filtroEncargadoActual);
        $paginaActualVistaCostura = max(1, (int) $request->query($pageNameVistaCostura, 1));

        $prendasPaginadasVistaCostura = null;
        $prendasRenderizadas = $prendasOrdenadas;
        $dashboardPaginacionVistaCostura = null;
        if ($esVistaCostura) {
            $perPageVistaCostura = 12;
            $total = $prendasOrdenadas->count();
            $items = $prendasOrdenadas->forPage($paginaActualVistaCostura, $perPageVistaCostura)->values();

            $prendasPaginadasVistaCostura = new LengthAwarePaginator(
                $items,
                $total,
                $perPageVistaCostura,
                $paginaActualVistaCostura,
                [
                    'path' => $request->url(),
                    'pageName' => $pageNameVistaCostura,
                    'query' => $request->query(),
                ]
            );

            $prendasRenderizadas = collect($prendasPaginadasVistaCostura->items());
            if ($prendasPaginadasVistaCostura->lastPage() > 1) {
                $desde = (($prendasPaginadasVistaCostura->currentPage() - 1) * $prendasPaginadasVistaCostura->perPage()) + 1;
                $hasta = min(
                    $prendasPaginadasVistaCostura->currentPage() * $prendasPaginadasVistaCostura->perPage(),
                    $prendasPaginadasVistaCostura->total()
                );
                $conteoPagina = max(0, $hasta - $desde + 1);
                $inicioBotones = max(1, $prendasPaginadasVistaCostura->currentPage() - 2);
                $finBotones = min($prendasPaginadasVistaCostura->lastPage(), $inicioBotones + 4);
                if (($finBotones - $inicioBotones) < 4) {
                    $inicioBotones = max(1, $finBotones - 4);
                }

                $dashboardPaginacionVistaCostura = [
                    'conteo_pagina' => $conteoPagina,
                    'inicio_botones' => $inicioBotones,
                    'fin_botones' => $finBotones,
                ];
            }
        }

        $rolDashboardActual = $usuario?->hasRole('administrador-costura') ? 'administrador-costura'
            : ($usuario?->hasRole('vista-costura') ? 'vista-costura'
                : ($usuario?->hasRole('costura-reflectivo') ? 'costura-reflectivo'
                    : ($usuario?->hasRole('lider-reflectivo') ? 'lider-reflectivo'
                        : ($usuario?->hasRole('confeccion-sobremedida') ? 'confeccion-sobremedida'
                            : ($usuario?->hasRole('costurero') ? 'costurero'
                                : (($usuario?->hasRole('cortador') || $usuario?->hasRole('visualizador_plooter')) ? 'cortador'
                                    : ($usuario?->hasRole('bodeguero') ? 'bodeguero' : '')))))));

        $dashboardPageTitleText = $filtroReciboActual === 'reflectivo'
            ? 'RECIBOS DE REFLECTIVO'
            : ($filtroReciboActual === 'bodega' ? 'RECIBOS DE BODEGA' : 'RECIBOS DE COSTURA');

        return [
            'filtroReciboTitle' => $filtroReciboActual,
            'dashboardPageTitleText' => $dashboardPageTitleText,
            'esVistaCostura' => $esVistaCostura,
            'filtroReciboActual' => $filtroReciboActual,
            'filtroEncargadoActual' => $filtroEncargadoActual,
            'busquedaActual' => $busquedaActual,
            'modoControlCalidadVistaCostura' => $modoControlCalidadVistaCostura,
            'tabActualDashboard' => $tabActualDashboard,
            'prendasPaginadasVistaCostura' => $prendasPaginadasVistaCostura,
            'dashboardPaginacionVistaCostura' => $dashboardPaginacionVistaCostura,
            'prendasRenderizadas' => $prendasRenderizadas,
            'rolDashboardActual' => $rolDashboardActual,
        ];
    }

    public function enriquecerPrendasBodegaParaVista(Collection $prendas, array $mapaParcialesBodega): Collection
    {
        return $prendas->map(function ($prenda) use ($mapaParcialesBodega) {
            $reciboPrincipalBodega = $prenda['recibos'][0] ?? [];
            $consecutivoBodega = (string) ($reciboPrincipalBodega['consecutivo_actual'] ?? $prenda['numero_pedido'] ?? '');
            $reciboIdBodega = $reciboPrincipalBodega['id'] ?? null;
            $tieneParcialesBodegaFlag = (bool) ($reciboPrincipalBodega['tiene_parciales'] ?? $prenda['tiene_parciales'] ?? false);
            $pedidoIdBodega = (int) ($prenda['pedido_id'] ?? 0);
            $prendaIdBodega = (int) ($prenda['prenda_id'] ?? 0);
            $consecutivoOriginalBodega = is_numeric($consecutivoBodega) ? (int) $consecutivoBodega : 0;

            $claveParcialesBodega = ($pedidoIdBodega > 0 ? $pedidoIdBodega : 0)
                . '|' . $prendaIdBodega . '|' . $consecutivoOriginalBodega;
            $tieneParcialesBodegaDb = isset($mapaParcialesBodega[$claveParcialesBodega]);
            $tieneParcialesBodega = $tieneParcialesBodegaFlag || $tieneParcialesBodegaDb;

            $encargadoCosturaBodega = trim((string) ($prenda['encargado_costura'] ?? ($reciboPrincipalBodega['encargado_costura'] ?? '')));
            $procesoIdCosturaBodega = $prenda['proceso_id_costura'] ?? ($reciboPrincipalBodega['proceso_id_costura'] ?? null);
            $mostrarComoDeshacerBodega = $encargadoCosturaBodega !== '' && !empty($procesoIdCosturaBodega);
            $textoEncargadoCosturaBodega = $encargadoCosturaBodega !== '' ? strtoupper($encargadoCosturaBodega) : 'SIN ASIGNAR';
            $descripcionPrendaBodega = trim((string) ($prenda['descripcion'] ?? ''));
            $textoPrendaBodega = $descripcionPrendaBodega !== '' ? $descripcionPrendaBodega : 'SIN DESCRIPCION';
            $pedidoIdAccion = $pedidoIdBodega ?: $prendaIdBodega;
            $numeroPedidoAccion = (string) ($prenda['numero_pedido'] ?? $consecutivoBodega);
            $searchText = strtolower(trim(($consecutivoBodega ?? '') . ' ' . $textoPrendaBodega . ' ' . ($prenda['cliente'] ?? '')));

            $prenda['bodega_view'] = [
                'consecutivo' => $consecutivoBodega,
                'recibo_id' => $reciboIdBodega,
                'tiene_parciales' => $tieneParcialesBodega,
                'encargado_costura' => $encargadoCosturaBodega,
                'proceso_id_costura' => $procesoIdCosturaBodega,
                'mostrar_como_deshacer' => $mostrarComoDeshacerBodega,
                'texto_encargado_costura' => $textoEncargadoCosturaBodega,
                'texto_prenda' => $textoPrendaBodega,
                'pedido_id_accion' => $pedidoIdAccion,
                'numero_pedido_accion' => $numeroPedidoAccion,
                'search_text' => $searchText,
            ];

            return $prenda;
        })->values();
    }

    /**
     * Obtener información de distribución de un recibo
     * Retorna: null (sin distribuir), 'modulos' (distribuido en módulos), 'talleres' (distribuido en talleres)
     * 
     * IMPORTANTE: Solo retorna 'talleres' si existen encargados asignados en procesos_prenda
     */
    private function obtenerTipoDistribucion(int $pedidoId, int $prendaId, int $numeroRecibo, string $tipoRecibo): ?string
    {
        // Buscar en recibo_por_partes (distribución en módulos)
        $tieneModulos = \Illuminate\Support\Facades\DB::table('recibo_por_partes')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->where('consecutivo_original', $numeroRecibo)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoRecibo))])
            ->exists();

        if ($tieneModulos) {
            return 'modulos';
        }

        // Buscar en pedidos_parciales (distribución en talleres)
        $tieneTalleres = \Illuminate\Support\Facades\DB::table('pedidos_parciales')
            ->where('pedido_produccion_id', $pedidoId)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoRecibo))])
            ->exists();

        if ($tieneTalleres) {
            // Validar que exista al menos un encargado asignado en procesos_prenda
            // Si no hay encargado, no mostrar "DISTRIBUIDO EN TALLERES"
            $tieneEncargadoAsignado = \Illuminate\Support\Facades\DB::table('procesos_prenda')
                ->where('numero_pedido', function ($query) use ($pedidoId) {
                    $query->select('numero_pedido')
                        ->from('pedidos_produccion')
                        ->where('id', $pedidoId);
                })
                ->where('prenda_pedido_id', $prendaId)
                ->where('numero_recibo', $numeroRecibo)
                ->where('proceso', 'Costura')
                ->whereNotNull('encargado')
                ->whereRaw('TRIM(encargado) != ?', [''])
                ->exists();

            if ($tieneEncargadoAsignado) {
                return 'talleres';
            }
        }

        return null;
    }

    public function enriquecerPrendasNormalesParaVista(
        Collection $prendas,
        $usuario,
        string $filtroReciboActual,
        string $busquedaActual,
        Collection $nombresCosturaReflectivo
    ): Collection {
        return $prendas->map(function ($prenda) use ($usuario, $filtroReciboActual, $busquedaActual, $nombresCosturaReflectivo) {
            $recibos = (array) ($prenda['recibos'] ?? []);
            $tiposRecibos = array_map(fn ($r) => strtoupper((string) ($r['tipo_recibo'] ?? '')), $recibos);
            $tieneReflectivo = in_array('REFLECTIVO', $tiposRecibos, true);
            $tieneCostura = in_array('COSTURA', $tiposRecibos, true);
            $tieneBodega = in_array('CORTE-PARA-BODEGA', $tiposRecibos, true) || in_array('BODEGA', $tiposRecibos, true);
            $reciboReflectivoParaFiltro = collect($recibos)->first(fn ($recibo) => strtoupper((string) ($recibo['tipo_recibo'] ?? '')) === 'REFLECTIVO');
            $reciboCosturaFiltroCard = collect($recibos)->first(fn ($recibo) => strtoupper((string) ($recibo['tipo_recibo'] ?? '')) === 'COSTURA');
            $reciboBodegaFiltroCard = collect($recibos)->first(function ($recibo) {
                $tipo = strtoupper((string) ($recibo['tipo_recibo'] ?? ''));
                return $tipo === 'CORTE-PARA-BODEGA' || $tipo === 'BODEGA';
            });
            $reciboReflectivoFiltroCard = $reciboReflectivoParaFiltro;

            if ($filtroReciboActual === 'bodega' && $reciboBodegaFiltroCard) {
                // Reusar el slot de "costura" del card normal para mostrar encargado/fechas de bodega
                // cuando lider-reflectivo o costura-reflectivo ven el tab bodega.
                $reciboCosturaFiltroCard = $reciboBodegaFiltroCard;
            }

            if ($filtroReciboActual === 'bodega' && is_array($reciboCosturaFiltroCard)) {
                $encargadoRecibo = trim((string) ($reciboCosturaFiltroCard['encargado_costura'] ?? ''));
                $encargadoPrenda = trim((string) ($prenda['encargado_costura'] ?? ''));

                if ($encargadoRecibo === '' && $encargadoPrenda !== '') {
                    $reciboCosturaFiltroCard['encargado_costura'] = $encargadoPrenda;
                }
            }

            $mostrarReflectivoEnFiltro = $tieneReflectivo;
            if ($usuario?->hasRole('vista-costura')) {
                if (!$tieneReflectivo || !$reciboReflectivoParaFiltro) {
                    $mostrarReflectivoEnFiltro = false;
                }
            }

            $busquedaActiva = $busquedaActual !== '';
            $reflectivoCompletadoEnCard = (bool) (($reciboReflectivoParaFiltro['completado_reflectivo'] ?? false) || ($reciboReflectivoParaFiltro['completado_costura'] ?? false));
            $reciboPrincipalFiltro = $recibos[0] ?? null;
            $areaReciboFiltro = strtolower(trim((string) ($reciboPrincipalFiltro['area'] ?? '')));

            if ($usuario?->hasRole('vista-costura')) {
                $esReflectivo = $filtroReciboActual === 'reflectivo' ? 'reflectivo' : ($filtroReciboActual === 'bodega' ? 'bodega' : 'costura');
            } elseif (($usuario?->hasRole('costura-reflectivo')) || ($usuario?->hasRole('lider-reflectivo'))) {
                $tiposParaFiltro = [];
                if ($tieneCostura) {
                    $tiposParaFiltro[] = 'costura';
                }
                if ($mostrarReflectivoEnFiltro) {
                    $tiposParaFiltro[] = 'reflectivo';
                }
                if ($tieneBodega) {
                    $tiposParaFiltro[] = 'bodega';
                }
                $esReflectivo = implode(',', $tiposParaFiltro);
            } else {
                $esReflectivo = $mostrarReflectivoEnFiltro ? 'reflectivo' : 'costura';
            }

            $displayInicial = '';
            if ($usuario?->hasRole('vista-costura')) {
                $displayInicial = '';
            } elseif (($usuario?->hasRole('costura-reflectivo')) || ($usuario?->hasRole('lider-reflectivo'))) {
                if ($filtroReciboActual === 'reflectivo') {
                    $displayInicial = ($mostrarReflectivoEnFiltro && ($busquedaActiva || !$reflectivoCompletadoEnCard)) ? '' : 'none';
                } elseif ($filtroReciboActual === 'bodega') {
                    $displayInicial = $tieneBodega ? '' : 'none';
                } else {
                    $displayInicial = $tieneCostura ? '' : 'none';
                }
            } elseif (($usuario?->hasAnyRole(['costurero', 'confeccion-sobremedida'])) || ($usuario?->hasRole('administrador-costura'))) {
                $displayInicial = $tieneCostura ? '' : 'none';
            } elseif ($usuario?->hasRole('cortador')) {
                $displayInicial = $areaReciboFiltro === 'corte' ? '' : 'none';
            } else {
                $displayInicial = $tieneReflectivo ? '' : 'none';
            }

            $reciboPrincipalCard = $recibos[0] ?? null;
            $tiposUnicos = collect($recibos)
                ->pluck('tipo_recibo')
                ->map(fn ($tipo) => strtoupper(trim((string) $tipo)))
                ->filter(fn ($tipo) => $tipo !== '')
                ->unique()
                ->values()
                ->all();
            $preferirParcial = $usuario?->hasAnyRole(['vista-costura', 'administrador-costura']) ?? false;
            $recibosPreferidosPorTipo = [];
            foreach ($tiposUnicos as $tipoReciboUnico) {
                $reciboTipo = collect($recibos)->first(function ($recibo) use ($tipoReciboUnico, $preferirParcial) {
                    $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                    if ($tipo !== $tipoReciboUnico) {
                        return false;
                    }

                    if (!$preferirParcial) {
                        return true;
                    }

                    return !empty($recibo['pedido_parcial_id']);
                });

                if (!$reciboTipo) {
                    $reciboTipo = collect($recibos)->first(fn ($recibo) => strtoupper(trim((string) ($recibo['tipo_recibo'] ?? ''))) === $tipoReciboUnico);
                }

                if ($reciboTipo) {
                    $recibosPreferidosPorTipo[$tipoReciboUnico] = $reciboTipo;
                }
            }
            $reciboReflectivo = $recibosPreferidosPorTipo['REFLECTIVO'] ?? null;
            $reciboCompletadoCostura = (bool) ($reciboPrincipalCard['completado_costura'] ?? false);
            $reciboCompletadoReflectivo = (bool) ($reciboReflectivoFiltroCard['completado_costura'] ?? false);
            $reciboParaBusqueda = collect($recibos)->first(fn ($recibo) => !empty($recibo['pedido_parcial_id'])) ?? $reciboPrincipalCard;
            $tipoReciboPreferido = $reciboParaBusqueda['tipo_recibo'] ?? '';
            $parcialIdPreferido = !empty($reciboParaBusqueda['pedido_parcial_id']) ? (int) $reciboParaBusqueda['pedido_parcial_id'] : 'null';
            $consecutivoPreferido = $reciboParaBusqueda['consecutivo_parcial'] ?? ($reciboParaBusqueda['consecutivo_actual'] ?? '');
            $numeroReciboBusqueda = $reciboParaBusqueda['consecutivo_parcial'] ?? $reciboParaBusqueda['consecutivo_actual'] ?? $prenda['numero_pedido'];
            $numerosRecibosBusqueda = collect($recibos)
                ->flatMap(fn ($recibo) => [$recibo['consecutivo_actual'] ?? null, $recibo['consecutivo_parcial'] ?? null])
                ->filter(fn ($valor) => $valor !== null && $valor !== '')
                ->map(fn ($valor) => (string) $valor)
                ->unique()
                ->values()
                ->implode(' ');

            $sinEncargadoCosturaCard = collect($recibos)->contains(function ($recibo) {
                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                if ($tipo !== 'COSTURA') {
                    return false;
                }
                $sinEncargado = empty(trim((string) ($recibo['encargado_costura'] ?? '')));
                $completadoCorte = (bool) ($recibo['completado_corte'] ?? false);
                return $sinEncargado && $completadoCorte;
            });
            $sinEncargadoReflectivoCard = $reciboReflectivoFiltroCard && empty(trim((string) ($reciboReflectivoFiltroCard['encargado_costura'] ?? '')));
            $sinEncargadoCosturaLider = $reciboCosturaFiltroCard && empty(trim((string) ($reciboCosturaFiltroCard['encargado_costura'] ?? '')));
            $recibosCorteAsignadosCortador = collect($recibos)->filter(fn ($recibo) => strtolower(trim((string) ($recibo['area'] ?? ''))) === 'corte')->count();

            $encargadoCosturaEsReflectivo = false;
            if (($usuario?->hasRole('lider-reflectivo')) && $filtroReciboActual === 'costura' && !$mostrarReflectivoEnFiltro && $tieneCostura) {
                $encargadoCosturaLider = strtolower(trim((string) ($reciboCosturaFiltroCard['encargado_costura'] ?? '')));
                $encargadoCosturaEsReflectivo = $encargadoCosturaLider !== '' && $nombresCosturaReflectivo->contains($encargadoCosturaLider);
            }
            $debeOmitirseEnLiderReflectivo = ($usuario?->hasRole('lider-reflectivo') && $filtroReciboActual === 'costura' && !$mostrarReflectivoEnFiltro && $sinEncargadoCosturaLider)
                || (($usuario?->hasRole('lider-reflectivo') && $filtroReciboActual === 'costura' && !$mostrarReflectivoEnFiltro && $tieneCostura) && !$encargadoCosturaEsReflectivo);

            // Obtener información de distribución del recibo original
            $pedidoId = (int) ($prenda['pedido_id'] ?? $prenda['pedido_id_accion'] ?? 0);
            $prendaId = (int) ($prenda['prenda_id'] ?? 0);
            $numeroReciboCostura = (int) ($reciboPrincipalCard['consecutivo_actual'] ?? 0);
            $tipoReciboCostura = strtoupper(trim((string) ($reciboPrincipalCard['tipo_recibo'] ?? 'COSTURA')));
            $tipoDistribucion = null;
            
            if ($pedidoId > 0 && $prendaId > 0 && $numeroReciboCostura > 0) {
                $tipoDistribucion = $this->obtenerTipoDistribucion($pedidoId, $prendaId, $numeroReciboCostura, $tipoReciboCostura);
            }

            $acciones = $this->construirAccionesParaCard(
                $prenda,
                $usuario,
                $filtroReciboActual,
                $nombresCosturaReflectivo
            );

            $prenda['normal_view'] = [
                'estado_class' => 'pendiente',
                'tiene_reflectivo' => $tieneReflectivo,
                'tiene_costura' => $tieneCostura,
                'mostrar_reflectivo_en_filtro' => $mostrarReflectivoEnFiltro,
                'es_reflectivo_filtro' => $esReflectivo,
                'display_inicial' => $displayInicial,
                'recibo_principal_card' => $reciboPrincipalCard,
                'tipos_unicos' => $tiposUnicos,
                'recibo_reflectivo' => $reciboReflectivo,
                'recibos_preferidos_por_tipo' => $recibosPreferidosPorTipo,
                'recibo_costura_filtro' => $reciboCosturaFiltroCard,
                'recibo_reflectivo_filtro' => $reciboReflectivoFiltroCard,
                'recibo_completado_costura' => $reciboCompletadoCostura,
                'recibo_completado_reflectivo' => $reciboCompletadoReflectivo,
                'recibo_para_busqueda' => $reciboParaBusqueda,
                'tipo_recibo_preferido' => $tipoReciboPreferido,
                'parcial_id_preferido' => $parcialIdPreferido,
                'consecutivo_preferido' => $consecutivoPreferido,
                'numero_recibo_busqueda' => $numeroReciboBusqueda,
                'numeros_recibos_busqueda' => $numerosRecibosBusqueda,
                'sin_encargado_costura' => $sinEncargadoCosturaCard,
                'sin_encargado_reflectivo' => $sinEncargadoReflectivoCard,
                'sin_encargado_costura_lider' => $sinEncargadoCosturaLider,
                'recibos_corte_asignados' => $recibosCorteAsignadosCortador,
                'debe_omitirse_lider_reflectivo' => $debeOmitirseEnLiderReflectivo,
                'tipo_distribucion' => $tipoDistribucion,
                'acciones' => $acciones,
            ];

            return $prenda;
        })->values();
    }

    public function construirAccionesParaCard(
        array $prenda,
        $usuario,
        string $filtroReciboActual,
        Collection $nombresCosturaReflectivo,
        ?string $tab = null
    ): array {
        $acciones = [
            'cortador' => [],
            'costurero' => [],
            'administrador_sobremedida' => [],
            'vista_costura' => [],
            'costura_reflectivo' => [],
            'lider_reflectivo' => [],
            'otros' => [],
        ];

        $recibos = (array) ($prenda['recibos'] ?? []);
        $reciboPrincipal = $recibos[0] ?? null;
        $pedidoId = $prenda['pedido_id'] ?? $prenda['pedido_id_accion'] ?? null;
        $prendaId = $prenda['prenda_id'] ?? null;
        $nombrePrenda = $prenda['nombre_prenda'] ?? '';
        $numeroPedido = $prenda['numero_pedido'] ?? '';
        $normalView = $prenda['normal_view'] ?? [];
        $recibosPreferidosPorTipo = $normalView['recibos_preferidos_por_tipo'] ?? [];
        $tiposUnicos = $normalView['tipos_unicos'] ?? [];
        $reciboReflectivo = $normalView['recibo_reflectivo'] ?? null;
        $reciboCosturaFiltro = $normalView['recibo_costura_filtro'] ?? null;
        $reciboReflectivoFiltro = $normalView['recibo_reflectivo_filtro'] ?? null;
        $nombresCosturaReflectivoNormalizados = $nombresCosturaReflectivo
            ->filter(fn ($nombre) => is_string($nombre) && trim($nombre) !== '')
            ->map(fn ($nombre) => strtolower(trim($nombre)))
            ->values()
            ->all();

        // CORTADOR
        if ($usuario?->hasRole('cortador') || $usuario?->hasRole('visualizador_plooter')) {
            $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
            $esCorteRecibo = $areaRecibo === 'corte';
            $esCosturaRecibo = $areaRecibo === 'costura';
            $reciboId = $reciboPrincipal['id'] ?? $reciboPrincipal['recibo_id'] ?? $reciboPrincipal['consecutivo_actual'] ?? null;

            if ($esCorteRecibo && $reciboId) {
                $acciones['cortador'][] = [
                    'tipo' => 'completar_corte',
                    'clase' => 'btn-completar-corte',
                    'icono' => 'check_circle',
                    'texto' => 'MARCAR COMPLETADO',
                    'datos' => [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'recibo_id' => $reciboId,
                        'nombre' => $nombrePrenda,
                    ],
                ];
            }

            if ($esCosturaRecibo && $reciboId) {
                $acciones['cortador'][] = [
                    'tipo' => 'deshacer_corte',
                    'clase' => 'btn-deshacer-corte',
                    'icono' => 'undo',
                    'texto' => 'DESHACER',
                    'datos' => [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'recibo_id' => $reciboId,
                        'nombre' => $nombrePrenda,
                    ],
                ];
            }
        }

        // COSTURERO
        if ($usuario?->hasAnyRole(['costurero', 'confeccion-sobremedida'])) {
            $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
            $esCosturaRecibo = $areaRecibo === 'costura';
            $reciboAccionId = $reciboPrincipal['id'] ?? null;
            $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);

            if ($esCosturaRecibo && $reciboAccionId && !$reciboCompletadoCostura) {
                $acciones['costurero'][] = [
                    'tipo' => 'completar_costura',
                    'clase' => 'btn-completar-costura',
                    'icono' => 'check_circle',
                    'texto' => 'COMPLETAR',
                    'datos' => [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'recibo_id' => $reciboAccionId,
                        'es_parcial' => '0',
                        'nombre' => $nombrePrenda,
                    ],
                ];
            }

            if ($esCosturaRecibo && $reciboAccionId && $reciboCompletadoCostura) {
                $acciones['costurero'][] = [
                    'tipo' => 'deshacer_costura',
                    'clase' => 'btn-deshacer-costura',
                    'icono' => 'undo',
                    'texto' => 'DESHACER',
                    'datos' => [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'recibo_id' => $reciboAccionId,
                        'es_parcial' => '0',
                        'nombre' => $nombrePrenda,
                    ],
                ];
            }
        }

        // ADMINISTRADOR-COSTURA en pestaña sobremedida
        if ($usuario?->hasRole('administrador-costura') && ($tab ?? 'costura') === 'sobremedida') {
            $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
            $esCorteRecibo = $areaRecibo === 'corte';
            $reciboId = $reciboPrincipal['id'] ?? $reciboPrincipal['recibo_id'] ?? $reciboPrincipal['consecutivo_actual'] ?? null;
            $reciboCompletadoCorte = (bool) ($reciboPrincipal['completado_corte'] ?? false);

            if ($esCorteRecibo && $reciboId && !$reciboCompletadoCorte) {
                $acciones['administrador_sobremedida'][] = [
                    'tipo' => 'completar_corte_sobremedida',
                    'clase' => 'btn-completar-corte',
                    'icono' => 'check_circle',
                    'texto' => 'COMPLETAR CORTE',
                    'datos' => [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'recibo_id' => $reciboId,
                        'nombre' => $nombrePrenda,
                    ],
                ];
            }

            if ($esCorteRecibo && $reciboId && $reciboCompletadoCorte) {
                $acciones['administrador_sobremedida'][] = [
                    'tipo' => 'deshacer_corte_sobremedida',
                    'clase' => 'btn-deshacer-corte',
                    'icono' => 'undo',
                    'texto' => 'DESHACER',
                    'datos' => [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'recibo_id' => $reciboId,
                        'nombre' => $nombrePrenda,
                    ],
                ];
            }
        }

        // VISTA-COSTURA
        if ($usuario?->hasRole('vista-costura')) {
            foreach ($recibos as $reciboItem) {
                if (strtoupper((string) ($reciboItem['tipo_recibo'] ?? '')) !== 'COSTURA') {
                    continue;
                }

                $reciboId = $reciboItem['id'] ?? null;
                $tieneParciales = $reciboItem['tiene_parciales'] ?? false;
                $areaActual = $reciboItem['area'] ?? null;
                $procesoId = $reciboItem['proceso_id_costura'] ?? null;
                $encargadoCostura = $reciboItem['encargado_costura'] ?? null;
                $consecutivoActual = $reciboItem['consecutivo_actual'] ?? $numeroPedido;

                $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                $tieneEncargadoCostura = !empty($encargadoCostura);
                $mostrarComoDeshacerCostura = ($esCosturaProceso && $tieneEncargadoCostura && !$tieneParciales);

                if (!$tieneParciales) {
                    $acciones['vista_costura'][] = [
                        'tipo' => 'pasar_costura',
                        'clase' => 'btn-pasar-costura ' . ($mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : ''),
                        'icono' => $mostrarComoDeshacerCostura ? 'undo' : 'checkroom',
                        'texto' => $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA',
                        'datos' => [
                            'pedido_id' => $pedidoId,
                            'numero_pedido' => $numeroPedido,
                            'prenda_id' => $prendaId,
                            'nombre' => $nombrePrenda,
                            'tipo_recibo' => 'COSTURA',
                            'recibo' => $consecutivoActual,
                            'area' => $areaActual ?? '',
                            'proceso_id' => $procesoId,
                            'encargado_costura' => is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? ''),
                            'parcial_id' => $reciboItem['pedido_parcial_id'] ?? '',
                        ],
                        'visible_filtro' => 'costura',
                    ];

                    $acciones['vista_costura'][] = [
                        'tipo' => 'pasar_cc',
                        'clase' => 'btn-pasar-cc',
                        'icono' => $esCC ? 'undo' : 'check_circle',
                        'texto' => $esCC ? 'DESHACER' : 'PASAR A C.C',
                        'datos' => [
                            'pedido_id' => $pedidoId,
                            'prenda_id' => $prendaId,
                            'nombre' => $nombrePrenda,
                            'tipo_recibo' => 'COSTURA',
                            'recibo' => $consecutivoActual,
                            'area' => $areaActual ?? 'COSTURA',
                            'proceso_id' => $procesoId,
                        ],
                        'visible_filtro' => 'costura',
                    ];
                }
            }
        }

        return $acciones;
    }

    private function tiposReciboBusquedaVistaCostura(?string $filtroRecibo): array
    {
        if ($filtroRecibo === 'bodega') {
            return ['CORTE-PARA-BODEGA'];
        }

        return $filtroRecibo === 'reflectivo'
            ? ['REFLECTIVO']
            : ['COSTURA'];
    }
}
