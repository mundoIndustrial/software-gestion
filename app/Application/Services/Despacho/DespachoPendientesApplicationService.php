<?php

namespace App\Application\Services\Despacho;

use App\Application\Bodega\Services\BodegaPedidoService;
use App\Models\DesparChoParcialesModel;
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

        return $pedidos->map(function ($pedido) {
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
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

        return $pedidos->map(function ($pedido) {
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
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

        return $pedidos->map(function ($pedido) {
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
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
                    ->whereIn('bdt.estado_bodega', ['Pendiente', 'Entregado'])
                    ->whereNull('pppd.id');
            })
            ->pluck('pedidos_produccion.id');

        $eppPedidoIds = PedidoProduccion::query()
            ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->whereNotNull('pedidos_produccion.numero_pedido')
            ->where('pedidos_produccion.numero_pedido', '!=', '')
            ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera'])
            ->where('bodega_detalles_talla.area', 'EPP')
            ->whereIn('bodega_detalles_talla.estado_bodega', ['Pendiente', 'Entregado'])
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
                        ->whereIn('bdt.estado_bodega', ['Pendiente', 'Entregado'])
                        ->whereNotNull('bdt.fecha_pendiente')
                        ->whereNull('pppd.id');
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('bodega_detalles_talla as bdt')
                        ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
                        ->whereNull('bdt.deleted_at')
                        ->where('bdt.area', 'EPP')
                        ->whereIn('bdt.estado_bodega', ['Pendiente', 'Entregado'])
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
        $reciboPrenda = ReciboPrenda::findOrFail($id);
        $numeroPedido = $reciboPrenda->numero_pedido;

        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        if (!$pedidoProduccion) {
            throw new \RuntimeException('Pedido de produccion no encontrado');
        }

        $pedidoData = [
            'id' => $reciboPrenda->id,
            'numero_pedido' => $numeroPedido,
            'estado' => $pedidoProduccion->estado ?? 'Pendiente',
            'cliente' => $reciboPrenda->cliente ?? 'No especificado',
            'asesor' => $reciboPrenda->asesor?->nombre ?? $reciboPrenda->asesor?->name ?? null,
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
                    // Mostrar solo pendientes
                    $esEppPendiente = ($tipo === 'epp') && ($area === 'EPP') && ($estadoPendiente || $tieneHistorial);
                    $esPrendaDeBodegaSinProcesos = ($tipo === 'prenda')
                        && $deBodega
                        && ($estadoBodega === 'Pendiente')
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
                'fecha_pendiente' => $item['fecha_pendiente'] ?? null,
                'fecha_entrega' => $fechaEntrega,
                'fecha_entrega_bodega' => $item['fecha_entrega_bodega'] ?? null,
                'created_at' => $item['created_at'] ?? null,
                'updated_at' => $item['updated_at'] ?? null,
                'observaciones' => $item['observaciones'] ?? '',
                'observaciones_bodega' => $item['observaciones_bodega'] ?? '',
                'pedido_produccion_id' => $item['pedido_produccion_id'] ?? $pedidoProduccion->id,
                'recibo_prenda_id' => $item['recibo_prenda_id'] ?? $reciboPrenda->id,
            ];

            $items[] = $itemNormalizado;
        }

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
        if (!in_array($pedidoData['estado'] ?? '', $estadosPermitidos, true)) {
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

