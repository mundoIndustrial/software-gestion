<?php

namespace App\Application\Services\Despacho;

use App\Application\Bodega\Services\BodegaPedidoService;
use App\Models\DesparChoParcialesModel;
use App\Models\PedidoEpp;
use App\Models\PedidoProduccion;
use App\Models\ReciboPrenda;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DespachoPendientesApplicationService
{
    public function __construct(
        private readonly BodegaPedidoService $bodegaPedidoService,
    ) {
    }

    /**
     * @param array<int,int|string> $pedidoIds
     * @return array<int,array{fecha_pendiente_min:?string,fechas_pendiente_distintas:array<int,string>,fechas_pendiente_distintas_count:int}>
     */
    private function obtenerResumenFechasPendientePorPedido(array $pedidoIds): array
    {
        $ids = collect($pedidoIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = DB::table('bodega_detalles_talla')
            ->select('pedido_produccion_id', 'fecha_pendiente')
            ->whereIn('pedido_produccion_id', $ids->all())
            ->where('estado_bodega', 'Pendiente')
            ->whereNotNull('fecha_pendiente')
            ->get();

        $resumen = [];
        foreach ($rows as $row) {
            $pedidoId = (int) $row->pedido_produccion_id;
            $fechaRaw = (string) $row->fecha_pendiente;
            $fechaSolo = substr($fechaRaw, 0, 10);

            if (!isset($resumen[$pedidoId])) {
                $resumen[$pedidoId] = [
                    'min_raw' => $fechaRaw,
                    'fechas_raw' => [],
                ];
            }

            if ($fechaRaw < $resumen[$pedidoId]['min_raw']) {
                $resumen[$pedidoId]['min_raw'] = $fechaRaw;
            }

            $resumen[$pedidoId]['fechas_raw'][$fechaSolo] = true;
        }

        $output = [];
        foreach ($resumen as $pedidoId => $data) {
            $fechas = array_keys($data['fechas_raw']);
            sort($fechas);

            $output[$pedidoId] = [
                'fecha_pendiente_min' => \Carbon\Carbon::parse($data['min_raw'])->format('d/m/Y'),
                'fechas_pendiente_distintas' => array_map(
                    fn ($fecha) => \Carbon\Carbon::parse($fecha)->format('d/m/Y'),
                    $fechas
                ),
                'fechas_pendiente_distintas_count' => count($fechas),
            ];
        }

        return $output;
    }

    public function obtenerPendientesBodegaSinProcesosData(string $search = ''): array
    {
        $query = PedidoProduccion::query()
            ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'])
            ->where('prendas_pedido.de_bodega', 1)
            ->whereNull('prendas_pedido.deleted_at')
            ->whereNull('pedidos_procesos_prenda_detalles.id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('bodega_detalles_talla as bdt')
                    ->join('prendas_pedido as pp', function ($join) {
                        $join->on('pp.id', '=', 'bdt.prenda_id')
                            ->where('pp.de_bodega', '=', 1)
                            ->whereNull('pp.deleted_at');
                    })
                    ->leftJoin('pedidos_procesos_prenda_detalles as pppd', 'pppd.prenda_pedido_id', '=', 'pp.id')
                    ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
                    ->where('bdt.estado_bodega', 'Pendiente')
                    ->whereNotNull('bdt.fecha_pendiente')
                    ->whereNull('pppd.id');
            })
            ->select('pedidos_produccion.*')
            ->distinct();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pedidos_produccion.numero_pedido', 'like', "%{$search}%")
                    ->orWhere('pedidos_produccion.cliente', 'like', "%{$search}%");
            });
        }

        $pedidos = $query->orderBy('created_at', 'desc')->get();
        $resumenFechas = $this->obtenerResumenFechasPendientePorPedido($pedidos->pluck('id')->all());

        return $pedidos->map(function ($pedido) use ($resumenFechas) {
            $fechasPedido = $resumenFechas[(int) $pedido->id] ?? null;
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
                'fecha_pendiente_min' => $fechasPedido['fecha_pendiente_min'] ?? null,
                'fechas_pendiente_distintas' => $fechasPedido['fechas_pendiente_distintas'] ?? [],
                'fechas_pendiente_distintas_count' => $fechasPedido['fechas_pendiente_distintas_count'] ?? 0,
                'tipo' => 'costura',
            ];
        })->toArray();
    }

    public function obtenerPendientesCosturaData(string $search = ''): array
    {
        $query = PedidoProduccion::query()
            ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->join('prendas_pedido', function ($join) {
                $join->on('prendas_pedido.id', '=', 'bodega_detalles_talla.prenda_id')
                    ->where('prendas_pedido.de_bodega', '=', 1)
                    ->whereNull('prendas_pedido.deleted_at');
            })
            ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'])
            ->where('bodega_detalles_talla.area', 'Costura')
            ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
            ->whereNull('pedidos_procesos_prenda_detalles.id')
            ->select('pedidos_produccion.*')
            ->distinct();

        \Log::info('[DEBUG] Costura SQL Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $bodegaCount = DB::table('bodega_detalles_talla')
            ->where('area', 'Costura')
            ->where('estado_bodega', 'Pendiente')
            ->count();

        \Log::info('[DEBUG] Bodega detalles costura count:', [
            'count' => $bodegaCount,
        ]);

        $areas = DB::table('bodega_detalles_talla')->distinct()->pluck('area');
        $estados = DB::table('bodega_detalles_talla')->distinct()->pluck('estado_bodega');
        $totalRegistros = DB::table('bodega_detalles_talla')->count();

        \Log::info('[DEBUG] Bodega detalles analisis:', [
            'total_registros' => $totalRegistros,
            'areas_disponibles' => $areas->toArray(),
            'estados_disponibles' => $estados->toArray(),
        ]);

        $bodegaPedidosIds = DB::table('bodega_detalles_talla')
            ->where('area', 'Costura')
            ->where('estado_bodega', 'Pendiente')
            ->pluck('pedido_produccion_id');

        \Log::info('[DEBUG] Pedidos IDs de bodega (Costura):', [
            'pedido_ids' => $bodegaPedidosIds->toArray(),
        ]);

        $pedidosRelacionados = DB::table('pedidos_produccion')
            ->whereIn('id', $bodegaPedidosIds)
            ->get(['id', 'numero_pedido', 'estado', 'deleted_at']);

        \Log::info('[DEBUG] Pedidos relacionados en pedidos_produccion:', [
            'pedidos' => $pedidosRelacionados->toArray(),
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pedidos_produccion.numero_pedido', 'like', "%{$search}%")
                    ->orWhere('pedidos_produccion.cliente', 'like', "%{$search}%");
            });
        }

        $pedidos = $query->orderBy('created_at', 'desc')->get();
        $resumenFechas = $this->obtenerResumenFechasPendientePorPedido($pedidos->pluck('id')->all());

        return $pedidos->map(function ($pedido) use ($resumenFechas) {
            $fechasPedido = $resumenFechas[(int) $pedido->id] ?? null;
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
                'fecha_pendiente_min' => $fechasPedido['fecha_pendiente_min'] ?? null,
                'fechas_pendiente_distintas' => $fechasPedido['fechas_pendiente_distintas'] ?? [],
                'fechas_pendiente_distintas_count' => $fechasPedido['fechas_pendiente_distintas_count'] ?? 0,
                'tipo' => 'costura',
            ];
        })->all();
    }

    public function obtenerPendientesEppData(string $search = ''): array
    {
        $query = PedidoProduccion::query()
            ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'])
            ->where('bodega_detalles_talla.area', 'EPP')
            ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
            ->whereNotNull('bodega_detalles_talla.fecha_pendiente')
            ->select('pedidos_produccion.*')
            ->distinct();

        \Log::info('[DEBUG] EPP SQL Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $bodegaCount = DB::table('bodega_detalles_talla')
            ->where('area', 'EPP')
            ->where('estado_bodega', 'Pendiente')
            ->count();

        \Log::info('[DEBUG] Bodega detalles EPP count:', [
            'count' => $bodegaCount,
        ]);

        $bodegaPedidosIds = DB::table('bodega_detalles_talla')
            ->where('area', 'EPP')
            ->where('estado_bodega', 'Pendiente')
            ->pluck('pedido_produccion_id');

        \Log::info('[DEBUG] Pedidos IDs de bodega (EPP):', [
            'pedido_ids' => $bodegaPedidosIds->toArray(),
        ]);

        $pedidosRelacionados = DB::table('pedidos_produccion')
            ->whereIn('id', $bodegaPedidosIds)
            ->get(['id', 'numero_pedido', 'estado', 'deleted_at']);

        \Log::info('[DEBUG] Pedidos relacionados en pedidos_produccion (EPP):', [
            'pedidos' => $pedidosRelacionados->toArray(),
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pedidos_produccion.numero_pedido', 'like', "%{$search}%")
                    ->orWhere('pedidos_produccion.cliente', 'like', "%{$search}%");
            });
        }

        $pedidos = $query->orderBy('created_at', 'desc')->get();
        $resumenFechas = $this->obtenerResumenFechasPendientePorPedido($pedidos->pluck('id')->all());

        return $pedidos->map(function ($pedido) use ($resumenFechas) {
            $fechasPedido = $resumenFechas[(int) $pedido->id] ?? null;
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
                'fecha_pendiente_min' => $fechasPedido['fecha_pendiente_min'] ?? null,
                'fechas_pendiente_distintas' => $fechasPedido['fechas_pendiente_distintas'] ?? [],
                'fechas_pendiente_distintas_count' => $fechasPedido['fechas_pendiente_distintas_count'] ?? 0,
                'tipo' => 'epp',
            ];
        })->toArray();
    }

    public function obtenerPendientesUnificadosData(
        string $search = '',
        string $tipo = 'todos',
        string $filter = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        \Log::info('[DEBUG] obtenerPendientesUnificados llamado - INICIO ABSOLUTO');

        \Log::info('[DEBUG] obtenerPendientesUnificados iniciado', [
            'search' => $search,
            'tipo' => $tipo,
            'filter' => $filter,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $pendientes = collect();
        $pedidosProcesados = [];

        if ($tipo === 'todos' || $tipo === 'costura') {
            \Log::info('[DEBUG] Obteniendo prendas de bodega sin procesos');
            try {
                $bodegaData = $this->obtenerPendientesBodegaSinProcesosData($search);
                \Log::info('[DEBUG] Respuesta bodega sin procesos:', [
                    'success' => true,
                    'data_count' => count($bodegaData),
                ]);

                $bodegaPedidos = collect($bodegaData);

                if ($filter !== '') {
                    $bodegaPedidos = $this->aplicarFiltros($bodegaPedidos, $filter);
                    \Log::info('[DEBUG] Bodega sin procesos despues de filtros:', [
                        'total_antes' => count($bodegaData),
                        'total_despues' => $bodegaPedidos->count(),
                    ]);
                }

                foreach ($bodegaPedidos as $pedido) {
                    $pedidoId = $pedido['id'] ?? $pedido->id;
                    if (!isset($pedidosProcesados[$pedidoId])) {
                        $pendientes->push($pedido);
                        $pedidosProcesados[$pedidoId] = true;
                    }
                }
                \Log::info('[DEBUG] Pedidos bodega sin procesos agregados, total: ' . $pendientes->count());
            } catch (\Exception $e) {
                \Log::error('[ERROR] Error obteniendo pedidos bodega sin procesos:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        if ($tipo === 'todos' || $tipo === 'epp') {
            \Log::info('[DEBUG] Obteniendo pendientes de EPP');
            try {
                $eppData = $this->obtenerPendientesEppData($search);
                \Log::info('[DEBUG] Respuesta EPP:', [
                    'success' => true,
                    'data_count' => count($eppData),
                ]);

                $eppPedidos = collect($eppData);

                if ($filter !== '') {
                    $eppPedidos = $this->aplicarFiltros($eppPedidos, $filter);
                    \Log::info('[DEBUG] EPP despues de filtros:', [
                        'total_antes' => count($eppData),
                        'total_despues' => $eppPedidos->count(),
                    ]);
                }

                foreach ($eppPedidos as $pedido) {
                    $pedidoId = $pedido['id'] ?? $pedido->id;
                    if (!isset($pedidosProcesados[$pedidoId])) {
                        $pendientes->push($pedido);
                        $pedidosProcesados[$pedidoId] = true;
                    }
                }
                \Log::info('[DEBUG] Pendientes EPP agregados, total: ' . $pendientes->count());
            } catch (\Exception $e) {
                \Log::error('[ERROR] Error obteniendo pendientes de EPP:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        if ($filter !== '' && $pendientes->count() > 0) {
            $pendientes = $this->aplicarFiltros($pendientes, $filter);
            \Log::info('[DEBUG] Pendientes finales despues de filtros globales:', [
                'total_antes' => $pendientes->count(),
                'total_despues' => $pendientes->count(),
            ]);
        }

        $pendientes = $pendientes->sortByDesc(function ($pedido) {
            return is_array($pedido) ? ($pedido['numero_pedido'] ?? '') : $pedido->numero_pedido;
        })->values();

        \Log::info('[DEBUG] Pendientes antes de paginacion - Numeros de pedido:', [
            'total_pendientes' => $pendientes->count(),
            'numeros_pedido' => $pendientes->map(function ($p) {
                return is_array($p) ? ($p['numero_pedido'] ?? null) : $p->numero_pedido;
            })->filter()->toArray(),
        ]);

        $total = $pendientes->count();
        $offset = ($page - 1) * $perPage;
        $paginated = $pendientes->slice($offset, $perPage)->values();

        $costuraCount = $paginated->filter(fn ($p) => (is_array($p) ? ($p['tipo'] ?? null) : $p->tipo) === 'costura')->count();
        $eppCount = $paginated->filter(fn ($p) => (is_array($p) ? ($p['tipo'] ?? null) : $p->tipo) === 'epp')->count();

        \Log::info('[DEBUG] Pendientes finales:', [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset,
            'paginated_count' => $paginated->count(),
            'costura_count' => $costuraCount,
            'epp_count' => $eppCount,
        ]);

        return [
            'success' => true,
            'data' => $paginated,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => min($offset + $perPage, $total),
                'has_more' => $perPage > 0 ? ($page < ceil($total / $perPage)) : false,
            ],
            'costura_count' => $costuraCount,
            'epp_count' => $eppCount,
        ];
    }

    public function obtenerEntregadosData(
        string $search = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        $query = PedidoProduccion::query()
            ->where('estado', 'Entregado')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->orderByDesc('numero_pedido');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                    ->orWhere('cliente', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $pedidos = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $pedidos->transform(function ($pedido) {
            $fechaEntrega = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('entregado', true)
                ->whereNotNull('fecha_entrega')
                ->orderBy('fecha_entrega', 'desc')
                ->first();

            $pedido->fecha_entrega = $fechaEntrega ? $fechaEntrega->fecha_entrega->format('d/m/Y h:i A') : '';
            $pedido->fecha_creacion = $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '';
            return $pedido;
        });

        return [
            'success' => true,
            'data' => $pedidos->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'fecha_entrega' => $pedido->fecha_entrega,
                    'fecha_creacion' => $pedido->fecha_creacion,
                ];
            }),
            'total' => $total,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
                'from' => $total > 0 ? ($page - 1) * $perPage + 1 : null,
                'to' => min($page * $perPage, $total),
                'has_more' => $page < ceil($total / $perPage),
            ],
        ];
    }

    public function obtenerAnuladosData(
        string $search = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        $query = PedidoProduccion::query()
            ->where('estado', 'Anulada')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->orderByDesc('numero_pedido');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                    ->orWhere('cliente', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $pedidos = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $pedidos->transform(function ($pedido) {
            $pedido->fecha_creacion = $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '';
            $pedido->fecha_anulacion = $pedido->updated_at ? $pedido->updated_at->format('d/m/Y h:i A') : '';
            return $pedido;
        });

        return [
            'success' => true,
            'data' => $pedidos->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'fecha_anulacion' => $pedido->fecha_anulacion,
                    'fecha_creacion' => $pedido->fecha_creacion,
                ];
            }),
            'total' => $total,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
                'from' => $total > 0 ? ($page - 1) * $perPage + 1 : null,
                'to' => min($page * $perPage, $total),
                'has_more' => $page < ceil($total / $perPage),
            ],
        ];
    }

    public function obtenerTodosLosPedidosData(string $search = ''): array
    {
        // TODO: completar caso de uso; se mantiene comportamiento legado
        return [
            'success' => true,
            'data' => [],
            'total' => 0,
            'search' => $search,
        ];
    }

    public function obtenerHistorialPendientesData(
        string $search = '',
        string $tipo = 'todos',
        int $page = 1,
        int $perPage = 10
    ): array {
        $coturaPedidoIds = PedidoProduccion::query()
            ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'])
            ->where('prendas_pedido.de_bodega', 1)
            ->whereNull('prendas_pedido.deleted_at')
            ->whereNull('pedidos_procesos_prenda_detalles.id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('bodega_detalles_talla as bdt')
                    ->join('prendas_pedido as pp', function ($join) {
                        $join->on('pp.id', '=', 'bdt.prenda_id')
                            ->where('pp.de_bodega', '=', 1)
                            ->whereNull('pp.deleted_at');
                    })
                    ->leftJoin('pedidos_procesos_prenda_detalles as pppd', 'pppd.prenda_pedido_id', '=', 'pp.id')
                    ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
                    ->whereIn('bdt.estado_bodega', ['Entregado', 'Pendiente'])
                    ->whereNotNull('bdt.fecha_pendiente')
                    ->whereNull('pppd.id');
            })
            ->pluck('pedidos_produccion.id');

        $eppPedidoIds = PedidoProduccion::query()
            ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'])
            ->where('bodega_detalles_talla.area', 'EPP')
            ->whereIn('bodega_detalles_talla.estado_bodega', ['Entregado', 'Pendiente'])
            ->whereNotNull('bodega_detalles_talla.fecha_pendiente')
            ->pluck('pedidos_produccion.id');

        $pedidoIds = $coturaPedidoIds->merge($eppPedidoIds)->unique();

        $query = PedidoProduccion::query()
            ->whereIn('id', $pedidoIds)
            // En historial solo deben listarse pedidos con al menos un item mostrable
            // (prenda de bodega sin procesos o item EPP) que tenga fecha_pendiente.
            ->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('bodega_detalles_talla as bdt')
                        ->join('prendas_pedido as pp', function ($join) {
                            $join->on('pp.id', '=', 'bdt.prenda_id')
                                ->where('pp.de_bodega', '=', 1)
                                ->whereNull('pp.deleted_at');
                        })
                        ->leftJoin('pedidos_procesos_prenda_detalles as pppd', 'pppd.prenda_pedido_id', '=', 'pp.id')
                        ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
                        ->whereNull('bdt.deleted_at')
                        ->whereIn('bdt.estado_bodega', ['Entregado', 'Pendiente'])
                        ->whereNotNull('bdt.fecha_pendiente')
                        ->whereNull('pppd.id');
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('bodega_detalles_talla as bdt')
                        ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
                        ->whereNull('bdt.deleted_at')
                        ->where('bdt.area', 'EPP')
                        ->whereIn('bdt.estado_bodega', ['Entregado', 'Pendiente'])
                        ->whereNotNull('bdt.fecha_pendiente');
                });
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                    ->orWhere('cliente', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $pedidos = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $pedidos->transform(function ($pedido) {
            // Obtener información de bodega para TODO el pedido (no solo un item)
            $bodegaStats = DB::table('bodega_detalles_talla')
                ->where('pedido_produccion_id', $pedido->id)
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('MIN(created_at) as fecha_primer_item'),
                    DB::raw('MAX(CASE WHEN estado_bodega = "Entregado" THEN COALESCE(fecha_entrega_bodega, updated_at) END) as fecha_entrega_ultimo_item')
                )
                ->first();

            // Verificar si todos los items están entregados
            $hayItemsPendientes = DB::table('bodega_detalles_talla')
                ->where('pedido_produccion_id', $pedido->id)
                ->whereNull('deleted_at')
                ->where('estado_bodega', '!=', 'Entregado')
                ->exists();

            $fechaCreacionPedido = $pedido->created_at ? $pedido->created_at->format('d/m/Y h:i A') : '';
            $fechaCreacionPendiente = '';
            $fechaEntrega = 'No entregado';
            $estadoEntrega = 'Pendiente';

            if ($bodegaStats) {
                // Fecha pendiente: cuando llegó el primer item a bodega
                if ($bodegaStats->fecha_primer_item) {
                    $fechaCreacionPendiente = \Carbon\Carbon::parse($bodegaStats->fecha_primer_item)->format('d/m/Y h:i A');
                }

                // Si NO hay items pendientes, mostrar fecha de entrega del último
                if (!$hayItemsPendientes && $bodegaStats->fecha_entrega_ultimo_item) {
                    $fechaEntrega = \Carbon\Carbon::parse($bodegaStats->fecha_entrega_ultimo_item)->format('d/m/Y h:i A');
                    $estadoEntrega = 'Entregado';
                }
            }

            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion_pedido' => $fechaCreacionPedido,
                'fecha_creacion_pendiente' => $fechaCreacionPendiente,
                'fecha_entrega' => $fechaEntrega,
                'estado_entrega' => $estadoEntrega,
            ];
        });

        return [
            'success' => true,
            'data' => $pedidos,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : null,
                'to' => min($page * $perPage, $total),
                'has_more' => $page < ceil($total / $perPage),
            ],
        ];
    }

    public function construirDetallePendienteUnificado(int $id, bool $mostrarEntregados = false): array
    {
        \Log::info('[DESPACHO][DETALLE] construirDetallePendienteUnificado INICIO', [
            'id_entrada' => $id,
            'mostrar_entregados' => $mostrarEntregados,
        ]);

        $reciboPrenda = null;
        $pedidoProduccion = null;

        // Priorizar numero_pedido para que la URL del historial abra exactamente
        // el pedido que el usuario ve en la tabla.
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $id)->first();
        $fuenteResolucion = $pedidoProduccion ? 'numero_pedido' : null;

        if (!$pedidoProduccion) {
            // Compatibilidad con enlaces antiguos que enviaban id interno.
            $reciboPrenda = ReciboPrenda::find($id);
            if ($reciboPrenda) {
                $pedidoProduccion = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)->first();
                if ($pedidoProduccion) {
                    $fuenteResolucion = 'recibo_prenda_id';
                }
            }
        }

        if (!$pedidoProduccion) {
            // Fallback: algunos listados pueden enviar id de pedidos_produccion.
            $pedidoProduccion = PedidoProduccion::find($id);
            if ($pedidoProduccion) {
                $fuenteResolucion = 'pedido_produccion_id';
            }
        }

        if (!$pedidoProduccion) {
            throw new \RuntimeException('Pedido de produccion no encontrado');
        }

        $numeroPedido = $pedidoProduccion->numero_pedido;

        \Log::info('[DESPACHO][DETALLE] Pedido resuelto', [
            'id_entrada' => $id,
            'fuente_resolucion' => $fuenteResolucion,
            'pedido_produccion_id' => $pedidoProduccion->id ?? null,
            'numero_pedido' => $numeroPedido,
            'estado' => $pedidoProduccion->estado ?? null,
        ]);

        if (!$reciboPrenda) {
            $reciboPrenda = ReciboPrenda::where('numero_pedido', $numeroPedido)
                ->orderByDesc('id')
                ->first();
        }

        \Log::info('[DESPACHO][DETALLE] Recibo resuelto', [
            'numero_pedido' => $numeroPedido,
            'recibo_prenda_id' => $reciboPrenda?->id,
        ]);

        $pedidoData = [
            'id' => $reciboPrenda?->id ?? $pedidoProduccion->id,
            'numero_pedido' => $numeroPedido,
            'estado' => $pedidoProduccion->estado ?? 'Pendiente',
            'cliente' => $reciboPrenda->cliente ?? $pedidoProduccion->cliente ?? 'No especificado',
            'asesor' => $reciboPrenda?->asesor?->nombre ?? $reciboPrenda?->asesor?->name ?? null,
            'created_at' => $pedidoProduccion->created_at,
        ];

        $datosCompletos = $this->bodegaPedidoService->obtenerDetallePedido($pedidoProduccion->id);

        $itemsPendientes = [];
        if (isset($datosCompletos['items']) && is_array($datosCompletos['items'])) {
            foreach ($datosCompletos['items'] as $item) {
                $tipo = strtolower($item['tipo'] ?? '');
                $area = $item['area'] ?? null;
                $estadoBodega = $item['estado_bodega'] ?? null;
                $deBodega = (bool) ($item['de_bodega'] ?? ($item['descripcion']['de_bodega'] ?? ($item['objetoPrenda']['de_bodega'] ?? false)));
                $procesos = $item['descripcion']['procesos'] ?? [];
                $tieneHistorial = $item['tiene_historial'] ?? false;

                $estadoPendiente = ($tipo === 'epp')
                    ? (($item['epp_estado'] ?? null) === 'Pendiente' || (!($item['epp_estado']) && $estadoBodega === 'Pendiente'))
                    : ($estadoBodega === 'Pendiente');

                if ($mostrarEntregados) {
                    // Mostrar todos los items (pendiente + entregado)
                    $incluir = ($tipo === 'epp' && $area === 'EPP') || ($tipo === 'prenda' && $deBodega && (empty($procesos) || (is_array($procesos) && count($procesos) === 0)));
                } else {
                    // Mostrar solo pendientes - EXCLUIR ENTREGADOS
                    $esEppPendiente = ($tipo === 'epp') && ($area === 'EPP') && ($estadoBodega !== 'Entregado');
                    $esPrendaDeBodegaSinProcesos = ($tipo === 'prenda')
                        && $deBodega
                        && ($estadoBodega !== 'Entregado')
                        && (empty($procesos) || (is_array($procesos) && count($procesos) === 0));
                    $incluir = $esEppPendiente || $esPrendaDeBodegaSinProcesos;
                }

                if ($incluir) {
                    $itemsPendientes[] = $item;
                }
            }
        }

        $items = [];

        foreach ($itemsPendientes as $item) {
            $pedidoEppId = isset($item['pedido_epp_id']) ? (int) $item['pedido_epp_id'] : null;
            $esItemEpp = strtolower((string) ($item['tipo'] ?? '')) === 'epp' || strtoupper((string) ($item['area'] ?? '')) === 'EPP';

            // No mostrar EPPs eliminados (soft-delete), ya que suelen ser versiones
            // reemplazadas por homologación.
            if ($esItemEpp && !empty($pedidoEppId)) {
                $pedidoEpp = PedidoEpp::withTrashed()->select(['id', 'deleted_at'])->find($pedidoEppId);
                if ($pedidoEpp && $pedidoEpp->deleted_at !== null) {
                    \Log::warning('[DESPACHO][DETALLE] Item EPP omitido por soft-delete', [
                        'numero_pedido' => $numeroPedido,
                        'pedido_produccion_id' => $pedidoProduccion->id ?? null,
                        'pedido_epp_id' => $pedidoEppId,
                    ]);
                    continue;
                }
            }

            $prendaNombre = $item['descripcion']['nombre_prenda'] ?? $item['prenda_nombre'] ?? '';
            $colorKey = $item['descripcion']['color'] ?? ($item['color'] ?? '');
            if (empty($colorKey)) {
                $tallaColorId = $item['talla_color_id'] ?? null;
                $variantes = $item['descripcion']['variantes'] ?? [];
                if ($tallaColorId !== null && is_array($variantes) && !empty($variantes)) {
                    foreach ($variantes as $var) {
                        $coloresDetalle = $var['colores_detalle'] ?? null;
                        if (!is_array($coloresDetalle) || empty($coloresDetalle)) {
                            continue;
                        }
                        foreach ($coloresDetalle as $cd) {
                            $tcId = $cd['talla_color_id'] ?? ($cd['tallaColorId'] ?? null);
                            if ($tcId !== null && (string) $tcId === (string) $tallaColorId) {
                                $colorKey = $cd['color'] ?? ($cd['color_nombre'] ?? '');
                                break 2;
                            }
                        }
                    }
                }
            }
            if (!empty($colorKey) && (!isset($item['descripcion']['color']) || empty($item['descripcion']['color']))) {
                $item['descripcion']['color'] = $colorKey;
            }

            $esEpp = strpos(strtoupper($prendaNombre), 'EPP') !== false;

            $fechaPendiente = $item['fecha_pendiente'] ?? null;
            if ((is_null($fechaPendiente) || (is_string($fechaPendiente) && trim($fechaPendiente) === '')) && !empty($pedidoProduccion->id)) {
                $fallbackQuery = DB::table('bodega_detalles_talla')
                    ->where('pedido_produccion_id', $pedidoProduccion->id)
                    ->whereNull('deleted_at');

                if (!empty($pedidoEppId)) {
                    $fallbackQuery->where('pedido_epp_id', $pedidoEppId);
                } else {
                    $fallbackQuery
                        ->when(!empty($prendaNombre), fn ($q) => $q->where('prenda_nombre', $prendaNombre))
                        ->when(!empty($item['talla_color_id']), fn ($q) => $q->where('talla_color_id', $item['talla_color_id']))
                        ->when(empty($item['talla_color_id']) && !empty($item['talla']), fn ($q) => $q->where('talla', $item['talla']));
                }

                $fallbackFechaPendiente = $fallbackQuery
                    ->orderByDesc('id')
                    ->value('fecha_pendiente');

                if (!empty($fallbackFechaPendiente)) {
                    $fechaPendiente = (string) $fallbackFechaPendiente;
                    \Log::info('[DESPACHO][DETALLE] fecha_pendiente recuperada por fallback', [
                        'numero_pedido' => $numeroPedido,
                        'pedido_produccion_id' => $pedidoProduccion->id,
                        'prenda_nombre' => $prendaNombre,
                        'talla' => $item['talla'] ?? null,
                        'talla_color_id' => $item['talla_color_id'] ?? null,
                        'fecha_pendiente' => $fechaPendiente,
                    ]);
                }
            }

            // Calcular fecha de entrega: usa fecha_entrega_bodega si existe, sino usa updated_at si estado es Entregado
            $fechaEntrega = '';
            if ($item['estado_bodega'] === 'Entregado') {
                if (!empty($item['fecha_entrega_bodega'])) {
                    $fechaEntrega = $item['fecha_entrega_bodega'];
                } elseif (!empty($item['updated_at'])) {
                    $fechaEntrega = $item['updated_at'];
                }
            }

            \Log::info('[DEBUG] Calculando fecha de entrega', [
                'numero_pedido' => $numeroPedido,
                'prenda_nombre' => $prendaNombre,
                'estado_bodega' => $item['estado_bodega'] ?? null,
                'fecha_entrega_bodega' => $item['fecha_entrega_bodega'] ?? null,
                'updated_at' => $item['updated_at'] ?? null,
                'fecha_entrega_final' => $fechaEntrega,
            ]);

            $itemNormalizado = [
                'numero_pedido' => $numeroPedido,
                'asesor' => $item['asesor'] ?? $pedidoData['asesor'],
                'empresa' => $item['empresa'] ?? $pedidoData['cliente'],
                'prenda_nombre' => $prendaNombre,
                'es_epp' => $esEpp,
                'tipo' => $item['tipo'] ?? 'prenda',
                'area' => $item['area'] ?? '',
                'tiene_historial' => $item['tiene_historial'] ?? false,
                'historial_homologaciones' => $item['historial_homologaciones'] ?? [],
                'descripcion' => $item['descripcion'] ?? [
                    'nombre_prenda' => $prendaNombre,
                    'nombre' => $prendaNombre,
                    'tela' => null,
                    'color' => null,
                    'procesos' => [],
                    'variantes' => [],
                ],
                'genero' => $item['genero'] ?? null,
                'talla' => $item['talla'] ?? '',
                'talla_color_id' => $item['talla_color_id'] ?? null,
                'cantidad' => $item['cantidad'] ?? 0,
                'cantidad_total' => $item['cantidad'] ?? 0,
                'pendientes' => $item['pendientes'] ?? '',
                'estado_bodega' => $item['estado_bodega'] ?? 'Pendiente',
                'fecha_pedido' => $item['fecha_pedido'] ?? null,
                'fecha_pendiente' => $fechaPendiente,
                'fecha_entrega' => $fechaEntrega,
                'fecha_entrega_bodega' => $item['fecha_entrega_bodega'] ?? null,
                'created_at' => $item['created_at'] ?? null,
                'updated_at' => $item['updated_at'] ?? null,
                'observaciones' => $item['observaciones'] ?? '',
                'observaciones_bodega' => $item['observaciones_bodega'] ?? '',
                'pedido_produccion_id' => $item['pedido_produccion_id'] ?? $pedidoProduccion->id,
                'recibo_prenda_id' => $item['recibo_prenda_id'] ?? $reciboPrenda?->id,
                'pedido_epp_id' => $pedidoEppId,
            ];

            $items[] = $itemNormalizado;
        }

        \Log::info('[DESPACHO][DETALLE] Items normalizados', [
            'numero_pedido' => $numeroPedido,
            'mostrar_entregados' => $mostrarEntregados,
            'items_count' => count($items),
            'items_resumen' => collect($items)->map(function ($it) {
                return [
                    'tipo' => $it['tipo'] ?? null,
                    'area' => $it['area'] ?? null,
                    'estado_bodega' => $it['estado_bodega'] ?? null,
                    'fecha_pendiente' => $it['fecha_pendiente'] ?? null,
                    'prenda_nombre' => $it['prenda_nombre'] ?? null,
                ];
            })->take(20)->values()->all(),
        ]);

        $estadosPermitidos = ['Pendiente', 'En Ejecución', 'No iniciado', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'];
        if (!empty($items)) {
            $tallas = collect($items)
                ->map(fn ($it) => trim((string) ($it['talla'] ?? '')))
                ->filter(fn ($t) => $t !== '')
                ->values()
                ->all();

            if (!empty($tallas)) {
                $notas = DB::table('bodega_notas')
                    ->where('numero_pedido', (string) $numeroPedido)
                    ->whereIn('talla', $tallas)
                    ->orderByDesc('created_at')
                    ->get();

                $notasPorClave = [];
                foreach ($notas as $nota) {
                    $clave = trim((string) ($nota->talla ?? '')) . '|' . (string) ($nota->talla_color_id ?? '');
                    if (!isset($notasPorClave[$clave])) {
                        $notasPorClave[$clave] = $nota;
                    }
                }

                foreach ($items as &$itemRef) {
                    $claveExacta = trim((string) ($itemRef['talla'] ?? '')) . '|' . (string) ($itemRef['talla_color_id'] ?? '');
                    $claveSinColor = trim((string) ($itemRef['talla'] ?? '')) . '|';
                    $notaItem = $notasPorClave[$claveExacta] ?? $notasPorClave[$claveSinColor] ?? null;

                    if ($notaItem && !empty($notaItem->contenido)) {
                        $itemRef['nota_bodega'] = (string) $notaItem->contenido;
                        if (empty($itemRef['observaciones_bodega'] ?? null)) {
                            $itemRef['observaciones_bodega'] = (string) $notaItem->contenido;
                        }
                    } else {
                        $itemRef['nota_bodega'] = null;
                    }
                }
                unset($itemRef);
            }
        }
        if (!$mostrarEntregados && !in_array($pedidoData['estado'] ?? '', $estadosPermitidos, true)) {
            throw new \RuntimeException('Este pedido no tiene un estado válido para despacho');
        }

        return [
            'pedido' => $pedidoData,
            'items' => $items,
        ];
    }

    /**
     * @param Collection<int, mixed> $pedidos
     */
    private function aplicarFiltros(Collection $pedidos, string $filterString): Collection
    {
        try {
            \Log::info('[DEBUG] Aplicando filtros:', [
                'filter_string' => $filterString,
                'pedidos_count' => $pedidos->count(),
            ]);

            if ($filterString === '') {
                return $pedidos;
            }

            $filtros = explode(',', $filterString);
            $pedidosFiltrados = $pedidos;

            foreach ($filtros as $filtroItem) {
                $filtroItem = trim($filtroItem);

                \Log::info('[DEBUG] Procesando filtro:', [
                    'filtro_item' => $filtroItem,
                    'es_numerico' => is_numeric($filtroItem),
                ]);

                if (is_numeric($filtroItem)) {
                    $pedidosFiltrados = $pedidosFiltrados->filter(function ($pedido) use ($filtroItem) {
                        $numero = is_array($pedido) ? ($pedido['numero_pedido'] ?? null) : $pedido->numero_pedido;
                        return (string) $numero === (string) $filtroItem;
                    });
                } else {
                    $pedidosFiltrados = $pedidosFiltrados->filter(function ($pedido) use ($filtroItem) {
                        $cliente = is_array($pedido) ? ($pedido['cliente'] ?? '') : $pedido->cliente;
                        return stripos((string) $cliente, $filtroItem) !== false;
                    });
                }

                $estadosMap = [
                    'Pendiente' => 'Pendiente',
                    'PENDIENTE_INSUMOS' => 'PENDIENTE_INSUMOS',
                    'No iniciado' => 'No iniciado',
                    'En Ejecución' => 'En Ejecución',
                    'Anulada' => 'Anulada',
                    'PENDIENTE_SUPERVISOR' => 'PENDIENTE_SUPERVISOR',
                    'DEVUELTO_A_ASESORA' => 'DEVUELTO_A_ASESORA',
                ];

                if (isset($estadosMap[$filtroItem])) {
                    $estadoBusqueda = $estadosMap[$filtroItem];
                    $pedidosFiltrados = $pedidosFiltrados->filter(function ($pedido) use ($estadoBusqueda) {
                        $estado = is_array($pedido) ? ($pedido['estado'] ?? null) : $pedido->estado;
                        return $estado === $estadoBusqueda;
                    });
                }
            }

            \Log::info('[DEBUG] Resultado de filtros:', [
                'total_original' => $pedidos->count(),
                'total_filtrados' => $pedidosFiltrados->count(),
            ]);

            return $pedidosFiltrados;
        } catch (\Exception $e) {
            \Log::error('[ERROR] Error aplicando filtros:', [
                'error' => $e->getMessage(),
                'filter_string' => $filterString,
                'trace' => $e->getTraceAsString(),
            ]);

            return $pedidos;
        }
    }
}
