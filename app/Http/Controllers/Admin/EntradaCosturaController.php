<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\EntradaCosturaHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            $hastaEntradaCostura
        );

        if ($searchEntradaCostura !== '') {
            $termino = mb_strtolower($searchEntradaCostura);
            $entradaCostura = $entradaCostura->filter(function (array $registro) use ($termino) {
                $textoBusqueda = mb_strtolower(implode(' ', [
                    (string) ($registro['numero_recibo'] ?? ''),
                    (string) ($registro['numero_pedido'] ?? ''),
                    (string) ($registro['cliente'] ?? ''),
                    (string) ($registro['nombre_prenda'] ?? ''),
                    (string) ($registro['nombre_operario'] ?? ''),
                    (string) ($registro['area'] ?? ''),
                    (string) ($registro['tipo_recibo'] ?? ''),
                ]));

                return str_contains($textoBusqueda, $termino);
            })->values();
        }

        $entradaCostura = $this->paginarColeccionEntradaCostura($entradaCostura, $request);

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

    private function obtenerEntradaCosturaDiariaDesdeCompletados(
        string $filtro = 'all',
        ?string $fechaSeleccionada = '',
        ?string $mesSeleccionado = '',
        ?string $anioSeleccionado = '',
        ?string $desdeSeleccionado = '',
        ?string $hastaSeleccionado = ''
    ): array {
        $fechaSeleccionada = (string) ($fechaSeleccionada ?? '');
        $mesSeleccionado = (string) ($mesSeleccionado ?? '');
        $anioSeleccionado = (string) ($anioSeleccionado ?? '');
        $desdeSeleccionado = (string) ($desdeSeleccionado ?? '');
        $hastaSeleccionado = (string) ($hastaSeleccionado ?? '');

        $query = DB::table('prenda_recibo_completado as prc')
            ->leftJoin('consecutivos_recibos_pedidos as crp', 'crp.id', '=', 'prc.id_recibo')
            ->leftJoin('recibo_por_partes as rpp', 'rpp.id', '=', 'prc.id_parcial')
            ->leftJoin('pedidos_produccion as ped', 'ped.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('pedidos_produccion as ped_parcial', 'ped_parcial.id', '=', 'rpp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'crp.prenda_id')
            ->leftJoin('prendas_pedido as pp_parcial', 'pp_parcial.id', '=', 'rpp.prenda_pedido_id')
            ->leftJoin('prenda_bodega as pb', 'pb.id', '=', 'crp.prenda_bodega_id')
            ->select(
                'prc.id',
                'prc.id_recibo',
                'prc.numero_recibo',
                'prc.area',
                'prc.nombre_operario',
                'prc.fecha_completado',
                'prc.tallas_control_calidad',
                'prc.id_parcial',
                DB::raw('COALESCE(rpp.pedido_produccion_id, crp.pedido_produccion_id) as pedido_produccion_id'),
                DB::raw('COALESCE(ped_parcial.numero_pedido, ped.numero_pedido) as numero_pedido_real'),
                DB::raw('COALESCE(rpp.prenda_pedido_id, crp.prenda_id) as prenda_id'),
                'crp.tipo_recibo',
                DB::raw('COALESCE(ped_parcial.cliente, ped.cliente) as cliente'),
                DB::raw('COALESCE(rpp.consecutivo_parcial, prc.numero_recibo, crp.consecutivo_actual) as numero_recibo_visible'),
                DB::raw('COALESCE(pp_parcial.nombre_prenda, pp.nombre_prenda, pb.descripcion) as nombre_prenda'),
                DB::raw('COALESCE(pp_parcial.descripcion, pp.descripcion, pb.descripcion) as descripcion_prenda')
            );

        $query->where(function ($subQuery) {
            $subQuery->whereNull('crp.tipo_recibo')
                ->orWhereRaw('UPPER(TRIM(crp.tipo_recibo)) <> ?', ['REFLECTIVO']);
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

        $registros = $query
            ->orderByDesc('prc.fecha_completado')
            ->orderByDesc('prc.id')
            ->get();

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
            ->whereIn('prenda_recibo_completado_id', $registros->pluck('id')->all())
            ->orderBy('id')
            ->get()
            ->groupBy('prenda_recibo_completado_id');

        $entradaCostura = $registros->map(function ($registro) use ($tallasPorRecibo) {
            $numeroReciboVisible = $this->normalizarNumeroReciboVisible($registro->numero_recibo_visible ?? null);

            $tallas = collect($tallasPorRecibo->get($registro->id, []))
                ->map(function ($talla) {
                    return [
                        'talla' => $talla->talla,
                        'cantidad' => (int) $talla->cantidad,
                        'genero' => $talla->genero,
                        'color_nombre' => $talla->color_nombre,
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
                'tipo_recibo' => (string) ($registro->tipo_recibo ?? 'COSTURA'),
                'id_parcial' => $registro->id_parcial ? (int) $registro->id_parcial : null,
                'area' => $registro->area,
                'cliente' => $registro->cliente !== null && $registro->cliente !== '' ? (string) $registro->cliente : null,
                'nombre_operario' => $registro->nombre_operario,
                'encargado' => (string) $this->resolverEncargadoCostura(
                    $registro->numero_pedido_real ? (int) $registro->numero_pedido_real : null,
                    $registro->prenda_id ? (int) $registro->prenda_id : null,
                    $numeroReciboVisible !== '' ? $numeroReciboVisible : null,
                    $registro->id_parcial ? (int) $registro->id_parcial : null
                ),
                'nombre_prenda' => $registro->nombre_prenda !== null && $registro->nombre_prenda !== '' ? (string) $registro->nombre_prenda : null,
                'descripcion_prenda' => $registro->descripcion_prenda !== null && $registro->descripcion_prenda !== '' ? (string) $registro->descripcion_prenda : null,
                'fecha' => Carbon::parse($registro->fecha_completado)->format('d/m/Y h:i A'),
                'tallas' => $tallas->all(),
                'total_unidades' => (int) $tallas->sum('cantidad'),
            ];
        })->filter(function (array $registro) {
            return !empty($registro['tallas']) && collect($registro['tallas'])->sum('cantidad') > 0;
        })->values();

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

        return [$entradaCostura, $resumenTallas, $totales];
    }

    private function paginarColeccionEntradaCostura($items, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $items = $items instanceof \Illuminate\Support\Collection ? $items : collect($items);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolverEncargadoCostura(?int $numeroPedido, ?int $prendaId, ?string $numeroRecibo, ?int $idParcial = null): ?string
    {
        if (!$numeroPedido || !$prendaId || !$numeroRecibo) {
            return null;
        }

        $query = DB::table('procesos_prenda')
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $prendaId)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->whereNull('deleted_at')
            ->orderByDesc('fecha_de_asignacion_encargado')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (Schema::hasColumn('procesos_prenda', 'numero_recibo_parcial')) {
            if ($idParcial !== null) {
                $query->where('numero_recibo_parcial', $numeroRecibo);
            } else {
                $query->where('numero_recibo', $numeroRecibo)
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('numero_recibo_parcial')
                            ->orWhere('numero_recibo_parcial', '')
                            ->orWhere('numero_recibo_parcial', 0);
                    });
            }
        } else {
            $query->where('numero_recibo', $numeroRecibo);
        }

        $encargado = $query->value('encargado');

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
