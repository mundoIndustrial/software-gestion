<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\EntradaCosturaHelper;
use App\Http\Controllers\Controller;
use App\Models\PrendaReciboCompletado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EntradaCosturaController extends Controller
{
    public function index(Request $request)
    {
        $filtroEntradaCostura = (string) ($request->input('filtro', 'all') ?? 'all');
        $searchEntradaCostura = trim((string) ($request->input('search', '') ?? ''));
        $fechaEntradaCostura = (string) ($request->input('fecha') ?? '');
        $mesEntradaCostura = (string) ($request->input('mes') ?? '');
        $anioEntradaCostura = (string) ($request->input('anio') ?? '');
        $desdeEntradaCostura = (string) ($request->input('desde') ?? '');
        $hastaEntradaCostura = (string) ($request->input('hasta') ?? '');

        [$entradaCostura, $resumenTallasEntradaCostura, $totalesEntradaCostura] = $this->obtenerEntradaCosturaDiariaDesdeCompletados(
            $filtroEntradaCostura,
            $fechaEntradaCostura,
            $mesEntradaCostura,
            $anioEntradaCostura,
            $desdeEntradaCostura,
            $hastaEntradaCostura,
            $searchEntradaCostura
        );

        return view('admin.entrada-costura.index', [
            'talleres' => collect(),
            'search' => null,
            'searchEntradaCostura' => $searchEntradaCostura,
            'filtroEntradaCostura' => $filtroEntradaCostura,
            'fechaEntradaCostura' => $fechaEntradaCostura,
            'mesEntradaCostura' => $mesEntradaCostura,
            'anioEntradaCostura' => $anioEntradaCostura,
            'desdeEntradaCostura' => $desdeEntradaCostura,
            'hastaEntradaCostura' => $hastaEntradaCostura,
            'entradaCostura' => $entradaCostura,
            'resumenTallasEntradaCostura' => $resumenTallasEntradaCostura,
            'totalesEntradaCostura' => $totalesEntradaCostura,
        ]);
    }

    public function registrarDestino(Request $request, int $registro)
    {
        $validated = $request->validate([
            'destino' => ['required', 'string', 'in:logo,empacar'],
        ]);

        $reciboCompletado = PrendaReciboCompletado::findOrFail($registro);
        $destino = $validated['destino'];

        $reciboCompletado->update([
            'destino_costura' => $destino,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Destino registrado como " . ($destino === 'logo' ? 'Logo' : 'Empacar') . '.',
            'destino' => $destino,
            'registro_id' => $reciboCompletado->id,
        ]);
    }

    private function obtenerEntradaCosturaDiariaDesdeCompletados(
        string $filtro = 'all',
        ?string $fechaSeleccionada = '',
        ?string $mesSeleccionado = '',
        ?string $anioSeleccionado = '',
        ?string $desdeSeleccionado = '',
        ?string $hastaSeleccionado = '',
        ?string $search = ''
    ): array {
        $fechaSeleccionada = (string) ($fechaSeleccionada ?? '');
        $mesSeleccionado = (string) ($mesSeleccionado ?? '');
        $anioSeleccionado = (string) ($anioSeleccionado ?? '');
        $desdeSeleccionado = (string) ($desdeSeleccionado ?? '');
        $hastaSeleccionado = (string) ($hastaSeleccionado ?? '');
        $tipoReciboResueltoExpr = "CASE WHEN rpp.id IS NOT NULL THEN COALESCE(rpp.tipo_recibo, ppar.tipo_recibo, crp.tipo_recibo, '') WHEN ppar.id IS NOT NULL THEN COALESCE(ppar.tipo_recibo, crp.tipo_recibo, '') ELSE COALESCE(crp.tipo_recibo, rpp.tipo_recibo, '') END";
        $tipoReciboResueltoUpperExpr = "UPPER(TRIM($tipoReciboResueltoExpr))";
        $nombrePrendaBusquedaExpr = "LOWER(COALESCE(COALESCE(pp_parcial_ppar.nombre_prenda, pp_parcial.nombre_prenda, pp.nombre_prenda, pb.nombre), ''))";
        $descripcionPrendaBusquedaExpr = "LOWER(COALESCE(COALESCE(pp_parcial_ppar.descripcion, pp_parcial.descripcion, pp.descripcion, pb.descripcion), ''))";

        $query = DB::table('prenda_recibo_completado as prc')
            ->leftJoin('consecutivos_recibos_pedidos as crp', 'crp.id', '=', 'prc.id_recibo')
            ->leftJoin('pedidos_parciales as ppar', 'ppar.id', '=', 'prc.id_parcial')
            ->leftJoin('recibo_por_partes as rpp', 'rpp.id', '=', 'prc.id_parcial')
            ->leftJoin('consecutivos_recibos_pedidos as crp_base', function ($join) {
                $join->on('crp_base.consecutivo_actual', '=', 'rpp.consecutivo_original')
                    ->whereRaw('UPPER(TRIM(crp_base.tipo_recibo)) IN (?, ?)', ['CORTE-PARA-BODEGA', 'COSTURA-BODEGA']);
            })
            ->leftJoin('pedidos_produccion as ped', 'ped.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('pedidos_produccion as ped_parcial_ppar', 'ped_parcial_ppar.id', '=', 'ppar.pedido_produccion_id')
            ->leftJoin('pedidos_produccion as ped_parcial', 'ped_parcial.id', '=', 'rpp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'crp.prenda_id')
            ->leftJoin('prendas_pedido as pp_parcial_ppar', 'pp_parcial_ppar.id', '=', 'ppar.prenda_pedido_id')
            ->leftJoin('prendas_pedido as pp_parcial', 'pp_parcial.id', '=', 'rpp.prenda_pedido_id')
            ->leftJoin('prenda_bodega as pb', function ($join) {
                $join->on('pb.id', '=', 'crp.prenda_bodega_id')
                    ->orOn('pb.id', '=', 'crp_base.prenda_bodega_id');
            })
            ->select(
                'prc.id',
                'prc.id_recibo',
                'prc.numero_recibo',
                'prc.area',
                'prc.destino_costura',
                'prc.nombre_operario',
                'prc.fecha_completado',
                'prc.tallas_control_calidad',
                'prc.id_parcial',
                DB::raw('COALESCE(rpp.pedido_produccion_id, ppar.pedido_produccion_id, crp.pedido_produccion_id) as pedido_produccion_id'),
                DB::raw('COALESCE(ped_parcial.numero_pedido, ped_parcial_ppar.numero_pedido, ped.numero_pedido) as numero_pedido_real'),
                DB::raw('COALESCE(crp.prenda_bodega_id, crp_base.prenda_bodega_id) as prenda_bodega_id'),
                DB::raw('COALESCE(rpp.prenda_pedido_id, ppar.prenda_pedido_id, crp.prenda_id) as prenda_id'),
                DB::raw("$tipoReciboResueltoExpr as tipo_recibo"),
                DB::raw("CASE WHEN $tipoReciboResueltoUpperExpr IN ('COSTURA-BODEGA', 'CORTE-PARA-BODEGA') THEN 'Bodega' ELSE COALESCE(ped_parcial.cliente, ped_parcial_ppar.cliente, ped.cliente) END as cliente"),
                DB::raw('COALESCE(rpp.consecutivo_parcial, ppar.consecutivo_actual, prc.numero_recibo, crp.consecutivo_actual) as numero_recibo_visible'),
                'pb.nombre as prenda_bodega_nombre',
                DB::raw("CASE WHEN $tipoReciboResueltoUpperExpr IN ('COSTURA-BODEGA', 'CORTE-PARA-BODEGA') THEN COALESCE(pb.nombre, pp_parcial.nombre_prenda, pp_parcial_ppar.nombre_prenda, pp.nombre_prenda) ELSE COALESCE(pp_parcial.nombre_prenda, pp_parcial_ppar.nombre_prenda, pp.nombre_prenda, pb.nombre) END as nombre_prenda"),
                DB::raw("CASE WHEN $tipoReciboResueltoUpperExpr IN ('COSTURA-BODEGA', 'CORTE-PARA-BODEGA') THEN COALESCE(pb.descripcion, pp_parcial.descripcion, pp_parcial_ppar.descripcion, pp.descripcion) ELSE COALESCE(pp_parcial.descripcion, pp_parcial_ppar.descripcion, pp.descripcion, pb.descripcion) END as descripcion_prenda")
            );

        $query->where(function ($subQuery) {
            $subQuery->whereNull('crp.tipo_recibo')
                ->orWhereRaw('UPPER(TRIM(crp.tipo_recibo)) <> ?', ['REFLECTIVO']);
        });

        $query->whereRaw(
            "$tipoReciboResueltoUpperExpr IN (?, ?, ?)",
            ['COSTURA', 'CORTE-PARA-BODEGA', 'COSTURA-BODEGA']
        );

        $query->whereExists(function ($subQuery) {
            $subQuery->select(DB::raw(1))
                ->from('prenda_recibo_completado_tallas as prct')
                ->whereColumn('prct.prenda_recibo_completado_id', 'prc.id')
                ->where('prct.cantidad', '>', 0);
        });

        if ($filtro === 'day' && !empty($fechaSeleccionada)) {
            $query->whereDate('prc.fecha_completado', $fechaSeleccionada);
        } elseif ($filtro === 'month' && !empty($mesSeleccionado)) {
            try {
                $fechaMes = Carbon::createFromFormat('Y-m', $mesSeleccionado);
                $query->whereYear('prc.fecha_completado', $fechaMes->year)
                    ->whereMonth('prc.fecha_completado', $fechaMes->month);
            } catch (\Throwable $e) {
                //
            }
        } elseif ($filtro === 'year' && !empty($anioSeleccionado)) {
            $query->whereYear('prc.fecha_completado', (int) $anioSeleccionado);
        } elseif ($filtro === 'range') {
            if (!empty($desdeSeleccionado)) {
                $query->whereDate('prc.fecha_completado', '>=', $desdeSeleccionado);
            }

            if (!empty($hastaSeleccionado)) {
                $query->whereDate('prc.fecha_completado', '<=', $hastaSeleccionado);
            }
        }

        $terminoBusqueda = trim((string) ($search ?? ''));
        if ($terminoBusqueda !== '') {
            $likeBusqueda = '%' . mb_strtolower($terminoBusqueda) . '%';

            $query->where(function ($searchQuery) use ($likeBusqueda) {
                $searchQuery
                    ->whereRaw('LOWER(COALESCE(CAST(prc.numero_recibo AS CHAR), "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw('LOWER(COALESCE(CAST(COALESCE(rpp.consecutivo_parcial, ppar.consecutivo_actual, prc.numero_recibo, crp.consecutivo_actual) AS CHAR), "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw('LOWER(COALESCE(CAST(COALESCE(rpp.pedido_produccion_id, ppar.pedido_produccion_id, crp.pedido_produccion_id) AS CHAR), "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw('LOWER(COALESCE(CAST(COALESCE(ped_parcial.numero_pedido, ped_parcial_ppar.numero_pedido, ped.numero_pedido) AS CHAR), "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw('LOWER(COALESCE(COALESCE(ped_parcial.cliente, ped_parcial_ppar.cliente, ped.cliente), "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw('LOWER(COALESCE(prc.area, "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw('LOWER(COALESCE(prc.nombre_operario, "")) LIKE ?', [$likeBusqueda])
                    ->orWhereRaw("LOWER(COALESCE($tipoReciboResueltoExpr, '')) LIKE ?", [$likeBusqueda])
                    ->orWhereRaw("$nombrePrendaBusquedaExpr LIKE ?", [$likeBusqueda])
                    ->orWhereRaw("$descripcionPrendaBusquedaExpr LIKE ?", [$likeBusqueda]);
            });
        }

        $registros = $query
            ->orderByDesc('prc.fecha_completado')
            ->orderByDesc('prc.id')
            ->paginate(10);

        if ($registros->isEmpty()) {
            return [collect(), collect(), [
                'ordenes' => 0,
                'unidades' => 0,
                'tallas_distintas' => 0,
            ]];
        }

        $tallasPorRecibo = DB::table('prenda_recibo_completado_tallas')
            ->select(
                'prenda_recibo_completado_id',
                'talla',
                'cantidad',
                'genero',
                'color_nombre'
            )
            ->whereIn('prenda_recibo_completado_id', $registros->getCollection()->pluck('id')->all())
            ->orderBy('id')
            ->get()
            ->groupBy('prenda_recibo_completado_id');

        $prendaBodegaIds = $registros->getCollection()
            ->pluck('prenda_bodega_id')
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $tallasPrendaBodega = !empty($prendaBodegaIds)
            ? DB::table('prenda_tallas_bodega')
                ->select('prenda_bodega_id', 'genero', 'color', 'talla', 'cantidad')
                ->whereIn('prenda_bodega_id', $prendaBodegaIds)
                ->orderBy('id')
                ->get()
                ->groupBy('prenda_bodega_id')
            : collect();

        $entradaCostura = $registros->getCollection()->map(function ($registro) use ($tallasPorRecibo, $tallasPrendaBodega) {
            $numeroReciboVisible = $this->normalizarNumeroReciboVisible($registro->numero_recibo_visible ?? null);
            $esReciboBodega = in_array(strtoupper(trim((string) ($registro->tipo_recibo ?? ''))), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'], true);
            $tallasBodega = $esReciboBodega && !empty($registro->prenda_bodega_id)
                ? collect($tallasPrendaBodega->get((int) $registro->prenda_bodega_id, []))
                : collect();

            $tallas = collect($tallasPorRecibo->get($registro->id, []))
                ->map(function ($talla) use ($tallasBodega, $esReciboBodega) {
                    $colorNombre = (string) ($talla->color_nombre ?? '');

                    if ($esReciboBodega && $colorNombre === '') {
                        $tallaBodegaCoincidente = $tallasBodega->first(function ($item) use ($talla) {
                            return strtoupper(trim((string) ($item->talla ?? ''))) === strtoupper(trim((string) ($talla->talla ?? '')))
                                && strtoupper(trim((string) ($item->genero ?? ''))) === strtoupper(trim((string) ($talla->genero ?? '')));
                        });

                        $colorNombre = (string) ($tallaBodegaCoincidente->color ?? '');
                    }

                    return [
                        'talla' => $talla->talla,
                        'cantidad' => (int) $talla->cantidad,
                        'genero' => $talla->genero,
                        'color_nombre' => $colorNombre,
                    ];
                })
                ->filter(function (array $talla) {
                    return !empty($talla['cantidad']) && (int) $talla['cantidad'] > 0;
                })
                ->sortBy(function (array $talla) {
                    return EntradaCosturaHelper::ordenarTalla($talla['genero'] ?? '', $talla['talla'] ?? '');
                })
                ->values();

            return [
                'id' => (int) $registro->id,
                'id_recibo' => (int) $registro->id_recibo,
                'numero_recibo' => $numeroReciboVisible,
                'numero_pedido' => $registro->pedido_produccion_id ? (int) $registro->pedido_produccion_id : null,
                'numero_pedido_real' => $registro->numero_pedido_real ? (int) $registro->numero_pedido_real : null,
                'prenda_id' => $registro->prenda_id ? (int) $registro->prenda_id : null,
                'prenda_bodega_id' => $registro->prenda_bodega_id ? (int) $registro->prenda_bodega_id : null,
                'tipo_recibo' => (string) ($registro->tipo_recibo ?? 'COSTURA'),
                'id_parcial' => $registro->id_parcial ? (int) $registro->id_parcial : null,
                'area' => $registro->area,
                'destino_costura' => $registro->destino_costura,
                'cliente' => $registro->cliente !== null && $registro->cliente !== '' ? (string) $registro->cliente : null,
                'nombre_operario' => $registro->nombre_operario,
                'encargado' => $this->resolverEncargadoCostura(
                    $registro->numero_pedido_real ? (int) $registro->numero_pedido_real : null,
                    in_array(strtoupper(trim((string) ($registro->tipo_recibo ?? ''))), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'], true)
                        ? null
                        : ($registro->prenda_id ? (int) $registro->prenda_id : null),
                    $numeroReciboVisible !== '' ? $numeroReciboVisible : null,
                    $registro->id_parcial ? (int) $registro->id_parcial : null,
                    $registro->prenda_bodega_id ? (int) $registro->prenda_bodega_id : null
                ),
                'nombre_prenda' => in_array(strtoupper(trim((string) ($registro->tipo_recibo ?? ''))), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'], true)
                    ? ($registro->prenda_bodega_nombre !== null && $registro->prenda_bodega_nombre !== '' ? (string) $registro->prenda_bodega_nombre : null)
                    : ($registro->nombre_prenda !== null && $registro->nombre_prenda !== '' ? (string) $registro->nombre_prenda : null),
                'prenda_bodega_nombre' => $registro->prenda_bodega_nombre !== null && $registro->prenda_bodega_nombre !== '' ? (string) $registro->prenda_bodega_nombre : null,
                'descripcion_prenda' => $registro->descripcion_prenda !== null && $registro->descripcion_prenda !== '' ? (string) $registro->descripcion_prenda : null,
                'fecha' => Carbon::parse($registro->fecha_completado)->format('d/m/Y h:i A'),
                'tallas' => $tallas->all(),
                'total_unidades' => (int) $tallas->sum('cantidad'),
            ];
        })->filter(function (array $registro) {
            return !empty($registro['tallas']) && collect($registro['tallas'])->sum('cantidad') > 0;
        })->values();

        $registros->setCollection($entradaCostura);

        $resumenTallas = $entradaCostura->flatMap(function (array $registro) {
            return $registro['tallas'];
        })->groupBy(function (array $talla) {
            return trim(($talla['genero'] ?? '') . ' - ' . ($talla['talla'] ?? ''));
        })->map(function ($items, string $etiqueta) {
            return [
                'etiqueta' => $etiqueta,
                'cantidad' => collect($items)->sum('cantidad'),
            ];
        })->values();

        $totales = [
            'ordenes' => $entradaCostura->count(),
            'unidades' => (int) $entradaCostura->sum('total_unidades'),
            'tallas_distintas' => $resumenTallas->count(),
        ];

        return [$registros, $resumenTallas, $totales];
    }

    private function resolverEncargadoCostura(?int $numeroPedido, ?int $prendaId, ?string $numeroRecibo, ?int $idParcial = null, ?int $prendaBodegaId = null): ?string
    {
        $tieneContextoPedido = $numeroPedido !== null && $numeroPedido > 0;
        $tieneContextoPrenda = ($prendaBodegaId !== null && $prendaBodegaId > 0)
            || ($prendaId !== null && $prendaId > 0);

        if (!$numeroRecibo || (!$tieneContextoPedido && !$tieneContextoPrenda)) {
            return null;
        }

        $numeroReciboLimpio = trim($numeroRecibo);

        $queryBase = DB::table('procesos_prenda')
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->whereNull('deleted_at')
            ->orderByDesc('fecha_de_asignacion_encargado')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($tieneContextoPedido) {
            $queryBase->where('numero_pedido', $numeroPedido);
        }

        $aplicarFiltroNumeroRecibo = function ($query) use ($idParcial, $numeroReciboLimpio) {
            if (!Schema::hasColumn('procesos_prenda', 'numero_recibo_parcial')) {
                return $query->where('numero_recibo', $numeroReciboLimpio);
            }

            if ($idParcial !== null) {
                return $query->where(function ($subQuery) use ($numeroReciboLimpio) {
                    $subQuery->where('numero_recibo', $numeroReciboLimpio)
                        ->orWhere('numero_recibo_parcial', $numeroReciboLimpio);
                });
            }

            return $query->where('numero_recibo', $numeroReciboLimpio)
                ->where(function ($subQuery) {
                    $subQuery->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', '')
                        ->orWhere('numero_recibo_parcial', 0);
                });
        };

        $buscarEncargado = function ($aplicarFiltroPrenda) use ($queryBase, $prendaBodegaId, $prendaId, $aplicarFiltroNumeroRecibo) {
            $query = clone $queryBase;

            if ($aplicarFiltroPrenda) {
                if ($prendaBodegaId !== null && $prendaBodegaId > 0 && Schema::hasColumn('procesos_prenda', 'prenda_bodega_id')) {
                    $query->where('prenda_bodega_id', $prendaBodegaId);
                } elseif ($prendaId) {
                    $query->where('prenda_pedido_id', $prendaId);
                } else {
                    return null;
                }
            }

            $aplicarFiltroNumeroRecibo($query);

            return $query->whereRaw('COALESCE(TRIM(encargado), "") <> ""')->value('encargado');
        };

        $encargado = $buscarEncargado(true);

        if (!$encargado) {
            $encargado = $buscarEncargado(false);
        }

        return $encargado ? (string) $encargado : null;
    }

    private function normalizarNumeroReciboVisible($numeroRecibo): string
    {
        $valor = trim((string) ($numeroRecibo ?? ''));

        if ($valor === '') {
            return '';
        }

        $valor = rtrim($valor, '0');
        $valor = rtrim($valor, '.');

        return $valor;
    }
}
