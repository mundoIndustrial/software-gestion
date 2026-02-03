<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\Role;
use App\Helpers\EstadoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupervisorAsesoresController extends Controller
{
    /**
     * Obtener todos los usuarios con rol 'asesor'
     */
    private function getAsesores()
    {
        // Obtener el rol 'asesor'
        $roleAsesor = Role::where('name', 'asesor')->first();
        
        \Log::info('SupervisorAsesoresController::getAsesores', [
            'role_asesor_id' => $roleAsesor?->id,
            'role_asesor_name' => $roleAsesor?->name,
        ]);

        if (!$roleAsesor) {
            \Log::warning('No se encontró el rol "asesor"');
            return collect([]);
        }

        // Buscar usuarios que tengan este rol en su array roles_ids
        $asesores = User::whereJsonContains('roles_ids', $roleAsesor->id)->get();
        
        \Log::info('Asesores encontrados', [
            'total' => $asesores->count(),
            'ids' => $asesores->pluck('id')->toArray(),
        ]);
        
        return $asesores;
    }

    /**
     * Paginar una colección manualmente
     */
    private function paginate($collection, $perPage = 15, $pageName = 'page')
    {
        $page = request()->get($pageName, 1);
        $items = $collection->forPage($page, $perPage);
        
        return new \Illuminate\Pagination\Paginator(
            $items,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Dashboard principal del supervisor de asesores
     */
    public function dashboard()
    {
        $asesores = $this->getAsesores();
        return view('supervisor-asesores.dashboard', compact('asesores'));
    }

    /**
     * Obtener estadísticas del dashboard en JSON
     */
    public function dashboardStats(Request $request)
    {
        $asesores = $this->getAsesores();
        $asesoresIds = $asesores->pluck('id')->toArray();
        
        // ============================================
        // Estadísticas Generales
        // ============================================
        $cotizacionesMes = Cotizacion::whereIn('asesor_id', $asesoresIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        // Total de pedidos de este mes (SIN filtro de asesores - para contexto)
        $totalPedidosMes = PedidoProduccion::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            // Tarjetas de estadísticas
            'cotizaciones_mes' => $cotizacionesMes,
            'total_pedidos_mes' => $totalPedidosMes
        ]);
    }

    /**
     * Mostrar lista de cotizaciones
     */
    public function cotizacionesIndex(Request $request)
    {
        $asesoresIds = $this->getAsesores()->pluck('id')->toArray();
        $asesoresMap = User::whereIn('id', $asesoresIds)->pluck('name', 'id');
        
        // Obtener TODAS las cotizaciones de todos los asesores
        $query = Cotizacion::whereIn('asesor_id', $asesoresIds)
            ->leftJoin('clientes', 'cotizaciones.cliente_id', '=', 'clientes.id')
            ->select(
                'cotizaciones.id',
                'cotizaciones.numero_cotizacion',
                'cotizaciones.tipo_cotizacion_id',
                'cotizaciones.estado',
                'cotizaciones.es_borrador',
                'cotizaciones.asesor_id',
                'cotizaciones.created_at',
                'cotizaciones.fecha_inicio',
                'cotizaciones.fecha_envio',
                'clientes.nombre as cliente_nombre'
            )
            ->with([
                'prendas.fotos',
                'prendas.tallas',
                'prendas.variantes',
                'prendas.telas',
                'logoCotizacion.fotos',
                'reflectivo.fotos',
                'tipoCotizacion'
            ]);

        $cotizacionesModelo = $query->orderBy('cotizaciones.created_at', 'desc')->get();

        // Convertir modelos a objetos para la vista
        $cotizaciones = $cotizacionesModelo->map(function($cot) use ($asesoresMap) {
            $obj = (object)[
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'tipo_cotizacion_id' => $cot->tipo_cotizacion_id,
                'tipo' => $cot->tipo_cotizacion_id ? ($cot->tipoCotizacion->codigo ?? 'P') : 'P',
                'estado' => $cot->estado,
                'es_borrador' => $cot->es_borrador,
                'cliente' => $cot->cliente_nombre ?? 'Sin cliente',
                'asesor_nombre' => $asesoresMap[$cot->asesor_id] ?? 'Desconocido',
                'created_at' => $cot->created_at,
                'fecha_inicio' => $cot->fecha_inicio,
                'fecha_envio' => $cot->fecha_envio,
                'prendas' => $cot->prendas,
                'logoCotizacion' => $cot->logoCotizacion,
            ];
            return $obj;
        });

        // Filtrar por búsqueda si se proporciona
        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $cotizaciones = $cotizaciones->filter(function($cot) use ($search) {
                return stripos($cot->cliente, $search) !== false;
            });
        }

        // Mostrar todas las cotizaciones en el tab de Cotizaciones
        // y solo las que tienen estado BORRADOR en el tab de Borradores
        $cotizacionesTodas = $this->paginate($cotizaciones, 15, 'page_cot_todas');
        $cotizacionesPrenda = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'PL'), 15, 'page_cot_prenda');
        $cotizacionesLogo = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'L'), 15, 'page_cot_logo');
        $cotizacionesPrendaBordado = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'PL'), 15, 'page_cot_pb');
        $cotizacionesReflectivo = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'RF'), 15, 'page_cot_rf');

        // Separar borradores por tipo (solo las que tienen es_borrador = 1)
        $borradoresCollection = $cotizaciones->filter(fn($c) => $c->es_borrador === true || $c->es_borrador === 1);
        $borradoresTodas = $this->paginate($borradoresCollection, 15, 'page_bor_todas');
        $borradorespPrenda = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'PL'), 15, 'page_bor_prenda');
        $borradoresLogo = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'L'), 15, 'page_bor_logo');
        $borradorespPrendaBordado = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'PL'), 15, 'page_bor_pb');
        $borradoresReflectivo = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'RF'), 15, 'page_bor_rf');

        return view('supervisor-asesores.cotizaciones.index', compact(
            'cotizacionesTodas',
            'cotizacionesPrenda',
            'cotizacionesLogo',
            'cotizacionesPrendaBordado',
            'cotizacionesReflectivo',
            'borradoresTodas',
            'borradorespPrenda',
            'borradoresLogo',
            'borradorespPrendaBordado',
            'borradoresReflectivo'
        ) + [
            'pageNameCotTodas' => 'page_cot_todas',
            'pageNameCotPrenda' => 'page_cot_prenda',
            'pageNameCotLogo' => 'page_cot_logo',
            'pageNameCotPB' => 'page_cot_pb',
            'pageNameCotRF' => 'page_cot_rf',
            'pageNameBorTodas' => 'page_bor_todas',
            'pageNameBorPrenda' => 'page_bor_prenda',
            'pageNameBorLogo' => 'page_bor_logo',
            'pageNameBorPB' => 'page_bor_pb',
            'pageNameBorRF' => 'page_bor_rf',
        ]);
    }

    /**
     * Obtener datos de cotizaciones en JSON
     */
    public function cotizacionesData(Request $request)
    {
        $asesoresIds = $this->getAsesores()->pluck('id')->toArray();
        
        $query = Cotizacion::whereIn('user_id', $asesoresIds)
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'email');
            }]);

        // Filtro por asesor
        if ($request->has('asesor_id') && $request->asesor_id) {
            $query->where('user_id', $request->asesor_id);
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        $cotizaciones = $query->orderBy('created_at', 'desc')
            ->select('id', 'numero', 'cliente', 'user_id', 'estado', 'created_at')
            ->get();

        return response()->json($cotizaciones);
    }

    /**
     * Mostrar lista de pedidos
     */
    public function pedidosIndex(Request $request)
    {
        \Log::info('=== SUPERVISOR ASESORES - PEDIDOS INDEX ===');
        \Log::info('Usuario: ' . Auth::user()?->email);
        
        $asesores = $this->getAsesores();
        \Log::info('Asesores encontrados: ' . count($asesores));
        
        // Obtener TODOS los pedidos de la tabla sin filtros de asesor
        $query = PedidoProduccion::with(['asesora', 'prendas.procesos', 'cotizacion']);
        
        // Búsqueda general (cliente o número de pedido)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            \Log::info('Búsqueda: ' . $search);
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'like', '%' . $search . '%')
                  ->orWhere('numero_pedido', 'like', '%' . $search . '%');
            });
        }
        
        // Filtro por estado (desde quick filter o URL)
        if ($request->has('estado') && $request->estado) {
            \Log::info('Filtro estado: ' . $request->estado);
            $query->where('estado', $request->estado);
        }
        
        // Filtro por asesor
        if ($request->has('asesor_id') && $request->asesor_id) {
            \Log::info('Filtro asesor_id: ' . $request->asesor_id);
            $query->where('asesor_id', $request->asesor_id);
        }
        
        // Filtro por cliente
        if ($request->has('cliente') && $request->cliente) {
            \Log::info('Filtro cliente: ' . $request->cliente);
            $query->where('cliente', 'like', '%' . $request->cliente . '%');
        }
        
        // Procesar filtros desde array filter[columna][]=valor
        if ($request->has('filter')) {
            $filters = $request->get('filter', []);
            \Log::info('Filtros desde URL:', $filters);
            
            // Estado
            if (isset($filters['Estado']) && !empty($filters['Estado'])) {
                $estados = is_array($filters['Estado']) ? $filters['Estado'] : [$filters['Estado']];
                $query->whereIn('estado', $estados);
                \Log::info('Filtro Estado aplicado:', $estados);
            }
            
            // Área
            if (isset($filters['Área']) && !empty($filters['Área'])) {
                $areas = is_array($filters['Área']) ? $filters['Área'] : [$filters['Área']];
                $query->whereIn('area', $areas);
                \Log::info('Filtro Área aplicado:', $areas);
            }
            
            // Número de Pedido
            if (isset($filters['Pedido']) && !empty($filters['Pedido'])) {
                $pedidos_nums = is_array($filters['Pedido']) ? $filters['Pedido'] : [$filters['Pedido']];
                $query->whereIn('numero_pedido', $pedidos_nums);
                \Log::info('Filtro Pedido aplicado:', $pedidos_nums);
            }
            
            // Cliente (búsqueda parcial)
            if (isset($filters['Cliente']) && !empty($filters['Cliente'])) {
                $clientes = is_array($filters['Cliente']) ? $filters['Cliente'] : [$filters['Cliente']];
                $query->where(function($q) use ($clientes) {
                    foreach ($clientes as $cliente) {
                        $q->orWhere('cliente', 'like', '%' . $cliente . '%');
                    }
                });
                \Log::info('Filtro Cliente aplicado:', $clientes);
            }
            
            // Descripción (búsqueda parcial)
            if (isset($filters['Descripción']) && !empty($filters['Descripción'])) {
                $descripciones = is_array($filters['Descripción']) ? $filters['Descripción'] : [$filters['Descripción']];
                $query->where(function($q) use ($descripciones) {
                    foreach ($descripciones as $desc) {
                        $q->orWhere('descripcion_prendas', 'like', '%' . $desc . '%');
                    }
                });
                \Log::info('Filtro Descripción aplicado:', $descripciones);
            }
            
            // Cantidad
            if (isset($filters['Cantidad']) && !empty($filters['Cantidad'])) {
                $cantidades = is_array($filters['Cantidad']) ? $filters['Cantidad'] : [$filters['Cantidad']];
                $query->whereIn('cantidad_prendas', $cantidades);
                \Log::info('Filtro Cantidad aplicado:', $cantidades);
            }
            
            // Forma de Pago
            if (isset($filters['Forma Pago']) && !empty($filters['Forma Pago'])) {
                $formas = is_array($filters['Forma Pago']) ? $filters['Forma Pago'] : [$filters['Forma Pago']];
                $query->whereIn('forma_de_pago', $formas);
                \Log::info('Filtro Forma Pago aplicado:', $formas);
            }
            
            // Fecha Creación
            if (isset($filters['Fecha Creación']) && !empty($filters['Fecha Creación'])) {
                $fechas = is_array($filters['Fecha Creación']) ? $filters['Fecha Creación'] : [$filters['Fecha Creación']];
                $query->where(function($q) use ($fechas) {
                    foreach ($fechas as $fecha) {
                        $q->orWhereDate('created_at', $fecha);
                    }
                });
                \Log::info('Filtro Fecha Creación aplicado:', $fechas);
            }
            
            // Fecha Estimada
            if (isset($filters['Fecha Estimada']) && !empty($filters['Fecha Estimada'])) {
                $fechas = is_array($filters['Fecha Estimada']) ? $filters['Fecha Estimada'] : [$filters['Fecha Estimada']];
                $query->where(function($q) use ($fechas) {
                    foreach ($fechas as $fecha) {
                        $q->orWhereDate('fecha_estimada_de_entrega', $fecha);
                    }
                });
                \Log::info('Filtro Fecha Estimada aplicado:', $fechas);
            }
            
            // Asesor
            if (isset($filters['Asesor']) && !empty($filters['Asesor'])) {
                $asesores_names = is_array($filters['Asesor']) ? $filters['Asesor'] : [$filters['Asesor']];
                $asesoresIds = User::whereIn('name', $asesores_names)->pluck('id')->toArray();
                $query->whereIn('asesor_id', $asesoresIds);
                \Log::info('Filtro Asesor aplicado:', $asesores_names);
            }
        }
        
        // Obtener pedidos con paginación
        $pedidos = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());
        
        \Log::info('Total pedidos (filtrados): ' . $pedidos->total());
        \Log::info('Pedidos en página actual: ' . count($pedidos->items()));
        
        // Agregar nombre del asesor a cada pedido
        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->asesor_nombre = $pedido->asesora?->name ?? 'N/A';
            return $pedido;
        });
        
        \Log::info('=== FIN SUPERVISOR ASESORES - PEDIDOS INDEX ===');
        
        return view('supervisor-asesores.pedidos.index', compact('pedidos', 'asesores'));
    }

    /**
     * Obtener datos de pedidos en JSON
     */
    public function pedidosData(Request $request)
    {
        $asesoresIds = $this->getAsesores()->pluck('id')->toArray();
        $limit = $request->get('limit', 50);
        
        $query = PedidoProduccion::whereIn('asesor_id', $asesoresIds)
            ->with(['asesora' => function ($q) {
                $q->select('id', 'name', 'email');
            }]);

        // Filtro por asesor
        if ($request->has('asesor_id') && $request->asesor_id) {
            $query->where('asesor_id', $request->asesor_id);
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        $pedidos = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->select('id', 'numero_pedido', 'numero_cotizacion', 'cliente', 'asesor_id', 'estado', 'created_at')
            ->get()
            ->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'numero_cotizacion' => $pedido->numero_cotizacion,
                    'cliente' => $pedido->cliente,
                    'asesor_nombre' => $pedido->asesora?->name ?? 'Desconocido',
                    'estado' => $pedido->estado,
                    'created_at' => $pedido->created_at
                ];
            });

        return response()->json(['data' => $pedidos]);
    }

    /**
     * Mostrar lista de asesores
     */
    public function asesoresIndex()
    {
        return view('supervisor-asesores.asesores.index');
    }

    /**
     * Obtener datos de asesores en JSON
     */
    public function asesoresData()
    {
        $asesores = $this->getAsesores();
        
        \Log::info('SupervisorAsesoresController::asesoresData', [
            'total_asesores' => $asesores->count(),
            'asesores_ids' => $asesores->pluck('id')->toArray(),
        ]);
        
        $asesoresConCuentas = $asesores->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'cotizaciones_count' => Cotizacion::where('asesor_id', $user->id)->count(),
                'pedidos_count' => PedidoProduccion::where('asesor_id', $user->id)->count(),
            ];
        });

        return response()->json($asesoresConCuentas);
    }

    /**
     * Mostrar detalle de asesor
     */
    public function asesoresShow($id)
    {
        $asesor = $this->getAsesores()->where('id', $id)->first();
        
        if (!$asesor) {
            abort(403);
        }

        $cotizacionesCount = Cotizacion::where('asesor_id', $id)->count();
        $pedidosCount = PedidoProduccion::where('asesor_id', $id)->count();
        
        // Obtener últimas cotizaciones con nombre de cliente
        $ultimasCotizaciones = Cotizacion::where('asesor_id', $id)
            ->leftJoin('clientes', 'cotizaciones.cliente_id', '=', 'clientes.id')
            ->select(
                'cotizaciones.id',
                'cotizaciones.numero_cotizacion',
                'cotizaciones.estado',
                'cotizaciones.created_at',
                'clientes.nombre as cliente_nombre'
            )
            ->orderBy('cotizaciones.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($cot) {
                $cot->cliente = $cot->cliente_nombre ?? 'Sin cliente';
                unset($cot->cliente_nombre);
                return $cot;
            });
        
        // Obtener últimos pedidos
        $ultimosPedidos = PedidoProduccion::where('asesor_id', $id)
            ->select('id', 'numero_pedido', 'cliente', 'estado', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('supervisor-asesores.asesores.show', compact(
            'asesor',
            'cotizacionesCount',
            'pedidosCount',
            'ultimasCotizaciones',
            'ultimosPedidos'
        ));
    }

    /**
     * Mostrar página de reportes
     */
    public function reportesIndex()
    {
        return view('supervisor-asesores.reportes.index');
    }

    /**
     * Obtener datos de reportes en JSON
     */
    public function reportesData(Request $request)
    {
        try {
            $asesoresIds = $this->getAsesores()->pluck('id')->toArray();
            $period = $request->get('period', 'month');
            $asesorId = $request->get('asesor_id');

            // Construir query base
            $cotizacionesQuery = Cotizacion::whereIn('asesor_id', $asesoresIds);
            $pedidosQuery = PedidoProduccion::whereIn('asesor_id', $asesoresIds);

            // Si se filtra por asesor específico
            if ($asesorId) {
                $cotizacionesQuery->where('asesor_id', $asesorId);
                $pedidosQuery->where('asesor_id', $asesorId);
            }

            // Filtrar por período
            $dateFilter = $this->getDateFilter($period);
            $cotizacionesQuery->whereBetween('created_at', $dateFilter);
            $pedidosQuery->whereBetween('created_at', $dateFilter);

            // Datos principales
            $totalCotizaciones = $cotizacionesQuery->count();
            $totalPedidos = $pedidosQuery->count();
            $conversionRate = $totalCotizaciones > 0 ? round(($totalPedidos / $totalCotizaciones) * 100) : 0;
            $totalIngresos = 0; // PedidoProduccion no tiene monto_total

            // Cotizaciones por estado
            $cotizacionesPorEstado = Cotizacion::whereIn('asesor_id', $asesoresIds)
                ->whereBetween('created_at', $dateFilter)
                ->when($asesorId, fn($q) => $q->where('asesor_id', $asesorId))
                ->groupBy('estado')
                ->selectRaw('estado, COUNT(*) as cantidad')
                ->get();

            // Top asesores
            $topAsesores = $this->getAsesores()
                ->map(function ($user) use ($dateFilter) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'cotizaciones_count' => Cotizacion::where('asesor_id', $user->id)
                            ->whereBetween('created_at', $dateFilter)
                            ->count(),
                        'pedidos_count' => PedidoProduccion::where('asesor_id', $user->id)
                            ->whereBetween('created_at', $dateFilter)
                            ->count()
                    ];
                })
                ->sortByDesc('cotizaciones_count')
                ->take(5)
                ->values();

            // Prendas más cotizadas (simulado - ajustar según tu modelo)
            $prendasMasCotizadas = [];

            // Técnicas más usadas (simulado - ajustar según tu modelo)
            $tecnicasMasUsadas = [];

            // Top clientes
            $topClientes = Cotizacion::whereIn('asesor_id', $asesoresIds)
                ->leftJoin('clientes', 'cotizaciones.cliente_id', '=', 'clientes.id')
                ->whereBetween('cotizaciones.created_at', $dateFilter)
                ->when($asesorId, fn($q) => $q->where('cotizaciones.asesor_id', $asesorId))
                ->groupBy('cotizaciones.cliente_id', 'clientes.nombre')
                ->selectRaw('clientes.nombre, COUNT(*) as cotizaciones_count')
                ->orderByDesc('cotizaciones_count')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'nombre' => $item->nombre ?? 'Sin cliente',
                        'cotizaciones_count' => $item->cotizaciones_count,
                        'monto_total' => 0 // No hay campo monto_total en Cotizacion para este contexto
                    ];
                });

            return response()->json([
                'summary' => [
                    'total_cotizaciones' => $totalCotizaciones,
                    'total_pedidos' => $totalPedidos,
                    'conversion_rate' => $conversionRate . '%',
                    'total_ingresos' => $totalIngresos,
                    'cotizaciones_por_estado' => $cotizacionesPorEstado
                ],
                'top_asesores' => $topAsesores,
                'prendas_mas_cotizadas' => $prendasMasCotizadas,
                'tecnicas_mas_usadas' => $tecnicasMasUsadas,
                'top_clientes' => $topClientes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en reportesData:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar página de perfil
     */
    public function profileIndex()
    {
        return view('supervisor-asesores.profile.index');
    }

    /**
     * Obtener estadísticas del perfil en JSON
     */
    public function profileStats()
    {
        $asesoresIds = $this->getAsesores()->pluck('id')->toArray();

        $cotizacionesCount = Cotizacion::whereIn('user_id', $asesoresIds)->count();
        $pedidosCount = PedidoProduccion::whereIn('asesor_id', $asesoresIds)->count();
        $asesoresCount = count($asesoresIds);
        $conversionRate = $cotizacionesCount > 0 ? round(($pedidosCount / $cotizacionesCount) * 100) : 0;

        return response()->json([
            'cotizaciones_count' => $cotizacionesCount,
            'pedidos_count' => $pedidosCount,
            'asesores_count' => $asesoresCount,
            'conversion_rate' => $conversionRate . '%'
        ]);
    }

    /**
     * Actualizar contraseña del perfil
     */
    public function profilePasswordUpdate(Request $request)
    {
        try {
            $request->validate([
                'password_antigua' => 'required|current_password',
                'password_nueva' => 'required|min:8|confirmed',
                'password_confirmar' => 'required'
            ]);

            $user = Auth::user();
            $user->update([
                'password' => bcrypt($request->password_nueva)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Obtener filtro de fechas según período
     */
    private function getDateFilter($period)
    {
        $now = now();
        
        $filters = match ($period) {
            'week' => [
                $now->clone()->startOfWeek(),
                $now->clone()->endOfWeek()
            ],
            'month' => [
                $now->clone()->startOfMonth(),
                $now->clone()->endOfMonth()
            ],
            'quarter' => [
                $now->clone()->startOfQuarter(),
                $now->clone()->endOfQuarter()
            ],
            'year' => [
                $now->clone()->startOfYear(),
                $now->clone()->endOfYear()
            ],
            default => [
                $now->clone()->startOfMonth(),
                $now->clone()->endOfMonth()
            ]
        };
        
        return $filters;
    }

    /**
     * Obtener valores únicos para los filtros de cotizaciones del supervisor
     */
    public function cotizacionesFiltrosValores()
    {
        // Obtener todas las cotizaciones con relaciones
        $cotizacionesModelo = Cotizacion::with([
            'cliente',
            'asesor',
            'tipoCotizacion'
        ])->get();

        \Log::info('SupervisorAsesoresController: Obteniendo valores de filtro para cotizaciones', [
            'total_cotizaciones' => $cotizacionesModelo->count(),
        ]);

        // Mapeo de códigos de tipo a nombres legibles
        $tiposMap = [
            'PL' => 'Combinada',
            'L' => 'Logo',
            'RF' => 'Reflectivo',
        ];

        $datos = [
            'fechas' => $cotizacionesModelo->pluck('created_at')
                ->map(fn($f) => $f->format('d/m/Y'))
                ->unique()
                ->values()
                ->toArray(),
            'codigos' => $cotizacionesModelo->pluck('numero_cotizacion')
                ->filter()
                ->unique()
                ->values()
                ->toArray(),
            'clientes' => $cotizacionesModelo->map(fn($c) => $c->cliente ? $c->cliente->nombre : 'Sin cliente')
                ->filter(fn($v) => $v !== 'Sin cliente')
                ->unique()
                ->values()
                ->toArray(),
            'asesores' => $cotizacionesModelo->map(fn($c) => $c->asesor?->name ?? 'Desconocido')
                ->filter(fn($v) => $v !== 'Desconocido')
                ->unique()
                ->values()
                ->toArray(),
            'tipos' => $cotizacionesModelo->map(function($c) use ($tiposMap) {
                $codigo = $c->tipoCotizacion?->codigo ?? $c->tipo ?? 'PL'; // Default a Combinada
                return $tiposMap[$codigo] ?? $codigo;
            })
                ->filter(fn($v) => $v !== null && $v !== '')
                ->unique()
                ->values()
                ->toArray(),
            'estados' => $cotizacionesModelo->pluck('estado')
                ->map(fn($e) => EstadoHelper::labelCotizacion($e))
                ->filter(fn($v) => $v !== null && $v !== '')
                ->unique()
                ->values()
                ->toArray(),
        ];

        \Log::info('SupervisorAsesoresController: Valores de filtro para cotizaciones', $datos);

        return response()->json($datos);
    }
}
