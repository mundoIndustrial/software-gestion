<?php

namespace App\Application\Operario\Services;

use App\Infrastructure\Repositories\Operario\OperarioDashboardRepository;
use App\Models\ConsecutivoReciboPedido;
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
            $camposPrenda = [
                strtolower(trim((string) ($prenda['numero_pedido'] ?? ''))),
                strtolower(trim((string) ($prenda['cliente'] ?? ''))),
                strtolower(trim((string) ($prenda['nombre_prenda'] ?? ''))),
                strtolower(trim((string) ($prenda['descripcion'] ?? ''))),
            ];

            foreach ($camposPrenda as $campo) {
                if ($campo !== '' && str_contains($campo, $busqueda)) {
                    return true;
                }
            }

            foreach (($prenda['recibos'] ?? []) as $recibo) {
                $camposRecibo = [
                    strtolower(trim((string) ($recibo['consecutivo_actual'] ?? ''))),
                    strtolower(trim((string) ($recibo['consecutivo_inicial'] ?? ''))),
                    strtolower(trim((string) ($recibo['tipo_recibo'] ?? ''))),
                    strtolower(trim((string) ($recibo['area'] ?? ''))),
                    strtolower(trim((string) ($recibo['notas'] ?? ''))),
                ];

                foreach ($camposRecibo as $campo) {
                    if ($campo !== '' && str_contains($campo, $busqueda)) {
                        return true;
                    }
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
                    'notas' => (string) ($row->notas ?? ''),
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

