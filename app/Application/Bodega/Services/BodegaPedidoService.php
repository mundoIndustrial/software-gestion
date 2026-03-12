<?php

namespace App\Application\Bodega\Services;

use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetallesTalla;
use App\Application\Bodega\Constants\WarehouseConstants;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use App\Models\PedidoVistoSupervisor;
use App\Models\PedidoRevisado;
use App\Application\Services\EntregaService;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Bodega\Calculators\PedidoEstadoCalculator;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BodegaPedidoService
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private PedidoProduccionRepository $pedidoRepository,
        private BodegaRoleService $roleService,
        private BodegaRepository $bodegaRepository,
        private PedidoEstadoCalculator $estadoCalculator
    ) {}

    /**
     * Obtener pedidos paginados según rol del usuario
     */
    public function obtenerPedidosPaginados(Request $request): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        
        // Determinar configuración según rol
        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);
        $estadosPermitidos = $this->obtenerEstadosPermitidos();
        
        // Obtener pedidos base
        $todosLosPedidos = $this->bodegaRepository->obtenerPedidosBase($estadosPermitidos);

        // Excluir pedidos anulados (estado del pedido principal)
        $todosLosPedidos = $this->filtrarPedidosAnulados($todosLosPedidos);
        
        // Filtrar por áreas según rol
        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);
        
        // Paginar
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);
        
        // Procesar datos para vista
        if ($request->query('view') === WarehouseConstants::VIEW_DETAILS) {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }
        
        return $this->procesarVistaLista($paginacion, $pedidosFiltradosPorRol);
    }

    /**
     * Obtener pedidos anulados paginados (estado del pedido principal = ANULADA)
     */
    public function obtenerPedidosAnuladosPaginados(Request $request): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();

        // Determinar configuración según rol
        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);

        // Obtener números de pedidos anulados desde pedidos_produccion
        // El ENUM tiene el valor exacto 'Anulada'
        $numerosAnulados = PedidoProduccion::query()
            ->where('estado', WarehouseConstants::STATE_CANCELLED)
            ->pluck('numero_pedido')
            ->filter(fn($n) => !empty($n))
            ->unique()
            ->values();

        // Cargar recibos por número de pedido SIN filtrar por estado del recibo,
        // para que el listado siempre muestre los pedidos anulados.
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosAnulados)
            ->orderBy('numero_pedido', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Filtrar por áreas según rol
        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);

        // Paginar
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        // Procesar datos para vista
        if ($request->query('view') === WarehouseConstants::VIEW_DETAILS) {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }

        return $this->procesarVistaLista($paginacion, $pedidosFiltradosPorRol);
    }

    /**
     * Obtener pedidos entregados paginados
     */
    public function obtenerPedidosEntregadosPaginados(Request $request): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();

        // Determinar configuración según rol
        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);

        // Obtener números de pedidos que tienen items con estado 'Entregado'
        $numerosConEntregados = DB::table('bodega_detalles_talla')
            ->where('estado_bodega', WarehouseConstants::STATE_DELIVERED)
            ->pluck('numero_pedido')
            ->filter(fn($n) => !empty($n))
            ->unique()
            ->values();

        // Cargar recibos por número de pedido que tengan items entregados
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosConEntregados)
            ->orderBy('numero_pedido', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Filtrar por áreas según rol
        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);

        // Paginar (incluye filtrado por búsqueda)
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        // Procesar datos para vista
        if ($request->query('view') === WarehouseConstants::VIEW_DETAILS) {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }

        return $this->procesarVistaLista($paginacion, $pedidosFiltradosPorRol);
    }

    /**
     * Obtener detalles de un pedido específico
     */
    public function obtenerDetallePedido(int $pedidoId, bool $paraDespacho = false): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);
        $estadosPermitidos = $this->obtenerEstadosPermitidos();
        
        // Obtener el recibo base
        $primerRecibo = ReciboPrenda::findOrFail($pedidoId);
        $numeroPedido = $primerRecibo->numero_pedido;
        
        // Validar que el recibo tenga número de pedido
        if (empty($numeroPedido)) {
            \Log::error('ReciboPrenda sin numero_pedido', [
                'recibo_id' => $pedidoId,
                'recibo_data' => $primerRecibo->toArray()
            ]);
            throw new \Exception('El recibo (ID: ' . $pedidoId . ') no tiene número de pedido asociado');
        }
        
        // Obtener info del pedido principal
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        // Obtener todos los recibos del pedido
        // Si el pedido principal está ANULADO/ANULADA, no filtrar por estadosPermitidos
        // porque normalmente esa lista excluye ANULADA.
        $estadoPP = strtoupper(trim($pedidoProduccion?->estado ?? ''));
        $esAnulada = str_starts_with($estadoPP, 'ANULAD');

        $recibos = $esAnulada
            ? ReciboPrenda::with(['asesor'])->where('numero_pedido', $numeroPedido)->get()
            : $this->bodegaRepository->obtenerRecibosPedido($numeroPedido, $estadosPermitidos);
        
        // Procesar ítems
        $items = $paraDespacho 
            ? $this->procesarItemsPedidoParaDespacho($recibos, $rolesDelUsuario, $areasPermitidas)
            : $this->procesarItemsPedido($recibos, $rolesDelUsuario, $areasPermitidas);
        
        // Calcular rowspans
        $items = $this->calcularRowspans($items);
        
        return [
            'pedido' => [
                'id' => $primerRecibo->id,
                'numero_pedido' => $numeroPedido,
                'estado' => $pedidoProduccion?->estado ?? $primerRecibo->estado,
                'cliente' => $primerRecibo->cliente ?? 'Cliente no especificado',
                'asesor' => $primerRecibo->asesor?->nombre ?? $primerRecibo->asesor?->name ?? null,
            ],
            'items' => $items,
        ];
    }

    /**
     * Guardar detalles de bodega por talla
     */
    public function guardarDetalles(array $validatedData): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        
        // Obtener el pedido
        $pedido = PedidoProduccion::where('numero_pedido', $validatedData['numero_pedido'])->first();
        if (!$pedido) {
            throw new \Exception('Pedido no encontrado');
        }

        // Guardar datos básicos en bodega_detalles_talla
        $detalleBasico = $this->guardarDatosBasicos($validatedData, $pedido, $usuario, $rolesDelUsuario);
        
        // Guardar estado específico según rol
        $detalle = $this->guardarEstadoPorRol($validatedData, $pedido, $usuario, $rolesDelUsuario);
        
        // Verificar y actualizar el estado del pedido - SOLO para "Pendiente"
        $this->verificarYActualizarEstadoPedido($pedido);
        
        // Disparar evento de tiempo real
        $this->dispararEventoTiempoReal($validatedData);
        
        return [
            'success' => true,
            'message' => 'Detalle guardado correctamente',
            'data' => $detalle
        ];
    }

    /**
     * Obtener datos de factura para modal
     */
    public function obtenerDatosFactura(int $id): array
    {
        try {
            // Obtener el ReciboPrenda y su numero_pedido
            $reciboPrenda = ReciboPrenda::select('id', 'numero_pedido')
                ->findOrFail($id);
            
            // Obtener el PedidoProduccion
            $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)
                ->firstOrFail();
            
            // Usar el repositorio para obtener datos completos
            $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
            
            return [
                'success' => true,
                'data' => $datos
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Pedido no encontrado'
            ];
        } catch (\Exception $e) {
            \Log::error('[ERROR] obtenerDatosFactura | ID: ' . $id . ' | ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener datos',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    // Métodos privados

    private function obtenerEstadosPermitidos(): array
    {
        return WarehouseConstants::getEstadosPermitidos();
    }

    private function filtrarPedidosPorArea(Collection $pedidos, array $areasPermitidas): Collection
    {
        return $pedidos->filter(function($item) use ($areasPermitidas) {
            $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)->get();
            
            if ($bdDetalles->isEmpty()) {
                return in_array(null, $areasPermitidas);
            }
            
            foreach ($bdDetalles as $detalle) {
                if (in_array($detalle->area, $areasPermitidas)) {
                    return true;
                }
            }
            
            return false;
        })->values();
    }

    private function filtrarPedidosAnulados(Collection $pedidos): Collection
    {
        $numerosPedido = $pedidos->pluck('numero_pedido')
            ->filter(fn($n) => !empty($n))
            ->unique()
            ->values();

        if ($numerosPedido->isEmpty()) {
            return $pedidos;
        }

        $numerosAnulados = PedidoProduccion::query()
            ->whereIn('numero_pedido', $numerosPedido)
            ->whereRaw('UPPER(TRIM(estado)) = ?', [WarehouseConstants::STATE_CANCELLED_UPPER])
            ->pluck('numero_pedido')
            ->unique();

        if ($numerosAnulados->isEmpty()) {
            return $pedidos;
        }

        return $pedidos->reject(fn($p) => $numerosAnulados->contains($p->numero_pedido))->values();
    }

    private function paginarPedidos(Collection $pedidos, Request $request): array
    {
        // Aplicar filtro de búsqueda si existe
        $search = $request->get('search');
        if ($search) {
            $pedidos = $pedidos->filter(function ($pedido) use ($search) {
                // Buscar por número de pedido
                if (stripos($pedido->numero_pedido, $search) !== false) {
                    return true;
                }
                
                // Buscar por nombre del cliente
                if ($pedido->cliente && stripos($pedido->cliente, $search) !== false) {
                    return true;
                }
                
                return false;
            });
        }
        
        // Aplicar filtros adicionales si existen
        $pedidos = $this->aplicarFiltrosAvanzados($pedidos, $request);
        
        $numerosPedidosUnicos = $pedidos->pluck('numero_pedido')->unique()->values();
        $totalPedidos = $numerosPedidosUnicos->count();

        $paginaActual = $request->get('page', 1);
        $porPagina = WarehouseConstants::ITEMS_PER_PAGE;
        $offset = ($paginaActual - 1) * $porPagina;

        $pedidosPaginados = $numerosPedidosUnicos->slice($offset, $porPagina);

        $paginacion = new LengthAwarePaginator(
            $pedidosPaginados,
            $totalPedidos,
            $porPagina,
            $paginaActual,
            [
                'path' => route('gestion-bodega.pedidos'),
                'query' => $request->query(),
            ]
        );

        return [
            'pedidos_paginados' => $pedidosPaginados,
            'total_pedidos' => $totalPedidos,
            'pagina_actual' => $paginaActual,
            'por_pagina' => $porPagina,
            'paginacion_obj' => $paginacion
        ];
    }
    
    private function aplicarFiltrosAvanzados(Collection $pedidos, Request $request): Collection
    {
        // Filtro por número de pedido
        $filtroNumeroPedido = $request->get('filtro_numero_pedido');
        if ($filtroNumeroPedido) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroNumeroPedido) {
                return stripos($pedido->numero_pedido, $filtroNumeroPedido) !== false;
            });
        }
        
        // Filtro por estado
        $filtroEstado = $request->get('filtro_estado');
        if ($filtroEstado) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroEstado) {
                $estadoPedido = strtoupper(trim($pedido->estado ?? ''));
                return $estadoPedido === strtoupper(trim($filtroEstado));
            });
        }
        
        // Filtro por asesor
        $filtroAsesor = $request->get('filtro_asesor');
        if ($filtroAsesor) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroAsesor) {
                $nombreAsesor = $pedido->asesor?->nombre ?? $pedido->asesor?->name ?? '';
                return stripos($nombreAsesor, $filtroAsesor) !== false;
            });
        }
        
        // Filtro por cliente
        $filtroCliente = $request->get('filtro_cliente');
        if ($filtroCliente) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroCliente) {
                return $pedido->cliente && stripos($pedido->cliente, $filtroCliente) !== false;
            });
        }
        
        // Filtro por rango de fechas
        $filtroFechaDesde = $request->get('filtro_fecha_desde');
        $filtroFechaHasta = $request->get('filtro_fecha_hasta');
        
        if ($filtroFechaDesde || $filtroFechaHasta) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroFechaDesde, $filtroFechaHasta) {
                $fechaPedido = $pedido->created_at ?? $pedido->fecha_pedido;
                
                if (!$fechaPedido) {
                    return false;
                }
                
                $fechaPedido = Carbon::parse($fechaPedido);
                
                // Verificar fecha desde
                if ($filtroFechaDesde) {
                    $fechaDesde = Carbon::parse($filtroFechaDesde)->startOfDay();
                    if ($fechaPedido->lt($fechaDesde)) {
                        return false;
                    }
                }
                
                // Verificar fecha hasta
                if ($filtroFechaHasta) {
                    $fechaHasta = Carbon::parse($filtroFechaHasta)->endOfDay();
                    if ($fechaPedido->gt($fechaHasta)) {
                        return false;
                    }
                }
                
                return true;
            });
        }
        
        return $pedidos;
    }

    private function procesarVistaDetallada(array $paginacion, array $rolesDelUsuario, array $areasPermitidas): array
    {
        // Implementar procesamiento para vista detallada
        // Esta es la lógica compleja del método index() original
        return [
            'view_type' => WarehouseConstants::VIEW_DETAILS,
            'pagination' => $paginacion
        ];
    }

    private function procesarVistaLista(array $paginacion, Collection $pedidosFiltrados): array
    {
        $pedidosPorPagina = [];
        foreach ($paginacion['pedidos_paginados'] as $numeroPedido) {
            $pedidosDelNumero = $pedidosFiltrados->filter(fn($p) => $p->numero_pedido === $numeroPedido)->values();
            if ($pedidosDelNumero->isNotEmpty()) {
                $primerPedido = $pedidosDelNumero->first();
                $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
                
                // Calcular estados del pedido usando la calculadora centralizada
                $estadosPedido = $this->estadoCalculator->calcular($numeroPedido);
                
                $tieneItemsPendientes = $estadosPedido['tiene_pendientes'];
                $totalItems = $estadosPedido['total_items'];
                $itemsEntregados = $estadosPedido['items_entregados'];
                $itemsPendientes = $estadosPedido['items_pendientes'];
                $todosEntregados = $estadosPedido['todos_entregados'];
                $todosPendientes = $estadosPedido['todos_pendientes'];
                
                // Verificar si el pedido ha sido marcado como visto por el usuario actual
                $userId = auth()->id();
                $vistoPorUsuario = \App\Models\PedidoVistoSupervisor::where('pedido_id', $primerPedido->id)
                    ->where('user_id', $userId)
                    ->first();

                // Verificar si el pedido ha sido revisado por el usuario actual
                $pedidoRevisado = PedidoRevisado::where('pedido_id', $numeroPedido)
                    ->where('user_id', $userId)
                    ->first();
                
                $pedidosPorPagina[] = [
                    'id' => $numeroPedido,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => $primerPedido->cliente ?? WarehouseConstants::DEFAULT_NA,
                    'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? WarehouseConstants::DEFAULT_NA,
                    'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                    'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                    'cantidad_items' => $pedidosDelNumero->count(),
                    'viewed_at' => $vistoPorUsuario?->created_at, // Usar la fecha de la tabla pedidos_vistos_supervisor
                    'pedido_revisado' => !empty($pedidoRevisado),
                    'tiene_pendientes' => $tieneItemsPendientes,
                    'todos_pendientes' => $todosPendientes,
                    'todos_entregados' => $todosEntregados,
                ];
            }
        }

        return [
            'view_type' => WarehouseConstants::VIEW_LIST,
            'pedidos_por_pagina' => $pedidosPorPagina,
            'total_pedidos' => $paginacion['total_pedidos'],
            'pagina_actual' => $paginacion['pagina_actual'],
            'por_pagina' => $paginacion['por_pagina'],
        ];
    }

    private function procesarItemsPedidoParaDespacho(Collection $recibos, array $rolesDelUsuario, array $areasPermitidas): array
    {
        $items = $this->procesarItemsPedido($recibos, $rolesDelUsuario, $areasPermitidas, paraDespacho: true);
        return $this->filtrarItemsParaDespacho($items);
    }

    private function procesarPrendasParaDespacho(array $prendas, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $items = [];
        
        foreach ($prendas as $prendaEnriquecida) {
            $variantes = $prendaEnriquecida['variantes'] ?? [];
            
            // Agrupar todas las variantes de esta prenda en un solo item
            $items[] = $this->crearItemPrendaConTallas($variantes, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
        }
        
        return $items;
    }

    private function procesarItemsPedido(Collection $recibos, array $rolesDelUsuario, array $areasPermitidas, bool $paraDespacho = false): array
    {
        $items = [];
        $numeroPedido = $recibos->first()->numero_pedido;
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        foreach ($recibos as $recibo) {
            try {
                $datosCompletos = $this->obtenerPedidoUseCase->ejecutar($recibo->id);
                
                if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                    $items = array_merge($items, 
                        $paraDespacho 
                            ? $this->procesarPrendasParaDespacho($datosCompletos->prendas, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion)
                            : $this->procesarPrendas($datosCompletos->prendas, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion)
                    );
                }
                
                if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                    $eppsProcesados = $this->procesarEpps($datosCompletos->epps, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
                    \Log::debug('[procesarItemsPedido] EPPs procesados', [
                        'cantidad' => count($eppsProcesados),
                        'areas' => array_map(fn($epp) => $epp['area'] ?? 'null', $eppsProcesados)
                    ]);
                    $items = array_merge($items, $eppsProcesados);
                }
                
            } catch (\Exception $e) {
                \Log::warning('[Bodega Show] Error al obtener datos del pedido', [
                    'numero_pedido' => $numeroPedido,
                    'recibo_id' => $recibo->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $items;
    }

    private function procesarPrendas(array $prendas, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $items = [];
        
        foreach ($prendas as $prendaEnriquecida) {
            $variantes = $prendaEnriquecida['variantes'] ?? [];
            
            foreach ($variantes as $variante) {
                $items = array_merge($items, $this->procesarVariante($variante, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion));
            }
        }
        
        return $items;
    }

    private function procesarVariante(array $variante, array $prendaEnriquecida, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $coloresDetalle = $variante['colores_detalle'] ?? null;
        
        if (!is_array($coloresDetalle) || empty($coloresDetalle)) {
            return [$this->crearItemPrenda($variante, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion)];
        }
        
        $items = [];
        foreach ($coloresDetalle as $colorDetalle) {
            $cantidadColor = (int)($colorDetalle['cantidad'] ?? 0);
            
            if ($cantidadColor <= 0) {
                continue;
            }
            
            $tallaColorId = $colorDetalle['talla_color_id'] ?? ($colorDetalle['tallaColorId'] ?? null);
            $items[] = $this->crearItemPrenda(
                $variante,
                $prendaEnriquecida,
                $recibo,
                $rolesDelUsuario,
                $areasPermitidas,
                $pedidoProduccion,
                $tallaColorId,
                $cantidadColor
            );
        }
        
        return $items;
    }

    private function procesarEpps(array $epps, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $items = [];
        $eppsProcesados = []; // Para evitar duplicados
        
        \Log::debug('[procesarEpps] Iniciando procesamiento', [
            'cantidad_epps' => count($epps),
            'numero_pedido' => $recibo->numero_pedido,
            'epps_ids' => array_map(fn($epp) => $epp['pedido_epp_id'] ?? 'null', $epps)
        ]);
        
        foreach ($epps as $eppEnriquecido) {
            $pedidoEppId = $eppEnriquecido['pedido_epp_id'] ?? null;
            
            // Saltar si ya fue procesado
            if (in_array($pedidoEppId, $eppsProcesados)) {
                continue;
            }
            
            \Log::debug('[procesarEpps] Procesando EPP', [
                'pedido_epp_id' => $pedidoEppId,
                'epp_nombre' => $eppEnriquecido['nombre'] ?? 'unknown'
            ]);
            
            if ($pedidoEppId) {
                $pedidoEpp = \App\Models\PedidoEpp::withTrashed()->find($pedidoEppId);
                
                if ($pedidoEpp) {
                    // SOLO PROCESAR EPPs ORIGINALES (homologado_de IS NULL)
                    if ($pedidoEpp->homologado_de === null) {
                        \Log::debug('[procesarEpps] EPP ORIGINAL encontrado', [
                            'pedido_epp_id' => $pedidoEppId,
                            'deleted_at' => $pedidoEpp->deleted_at
                        ]);
                        
                        // Obtener historial completo de cambios
                        $historialeHomologaciones = $this->obtenerHistorialEpp($pedidoEppId);
                        
                        // Crear item pasando el historial
                        $items[] = $this->crearItemEpp(
                            $eppEnriquecido,
                            $recibo,
                            $rolesDelUsuario,
                            $areasPermitidas,
                            $pedidoProduccion,
                            $historialeHomologaciones
                        );
                        
                        $eppsProcesados[] = $pedidoEppId;
                        
                        \Log::debug('[procesarEpps] EPP ORIGINAL incluido en tabla', [
                            'pedido_epp_id' => $pedidoEppId,
                            'historial_cambios' => count($historialeHomologaciones)
                        ]);
                    } else {
                        // Este EPP es un cambio de otro, será procesado como parte del historial del original
                        \Log::debug('[procesarEpps] EPP de cambio ignorado en tabla (se mostrará en expansión)', [
                            'pedido_epp_id' => $pedidoEppId,
                            'homologado_de' => $pedidoEpp->homologado_de
                        ]);
                    }
                } else {
                    \Log::debug('[procesarEpps] PedidoEpp NO encontrado', [
                        'pedido_epp_id' => $pedidoEppId
                    ]);
                }
            }
        }
        
        \Log::debug('[procesarEpps] Resultado final', [
            'items_creados' => count($items),
            'numero_pedido' => $recibo->numero_pedido
        ]);
        
        return $items;
    }

    private function crearItemPrendaConTallas(array $variantes, array $prendaEnriquecida, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        // DEBUG: Ver qué datos vienen en la prenda
        // Información almacenada para verificación si es necesario
        $prendaDebug = [
            'nombre' => $prendaEnriquecida['nombre'] ?? WarehouseConstants::DEFAULT_SIN_NOMBRE,
            'variantes_count' => count($variantes),
        ];
        
        // Obtener asesor de forma segura
        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }
        
        // Obtener empresa
        $empresa = $recibo->cliente ?? 'N/A';
        
        // Generar array de tallas para esta prenda
        $tallas = [];
        $cantidadTotal = 0;
        
        foreach ($variantes as $variante) {
            $talla = $variante['talla'] ?? '';
            $cantidad = $variante['cantidad'] ?? 0;
            $prendaId = $prendaEnriquecida['id'] ?? null;
            $genero = $variante['genero'] ?? null;
            
            // Obtener datos de bodega para esta talla específica con prenda_id y genero
            $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $talla, $prendaEnriquecida['nombre'] ?? null, $cantidad, $rolesDelUsuario, null, $prendaId, $genero);
            
            $tallas[] = [
                'talla' => $talla,
                'cantidad' => $cantidad,
                'pendientes' => $bodegaData['pendientes'] ?? 0,
                'area' => $bodegaData['area'] ?? '',
                'estado_bodega' => $bodegaData['estado_bodega'] ?? WarehouseConstants::STATE_PENDING,
                'pedido_produccion_id' => $bodegaData['id'] ?? null,
                'observaciones' => $bodegaData['observaciones'] ?? '',
                'fecha_entrega' => $bodegaData['fecha_entrega'] ?? ''
            ];
            
            $cantidadTotal += $cantidad;
        }
        
        // Calcular rowspan para la descripción
        $descripcionRowspan = count($tallas);
        
        return [
            'id' => $recibo->id,
            'tipo' => 'prenda',
            'numero_pedido' => $recibo->numero_pedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'prenda_id' => $prendaEnriquecida['id'] ?? null,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $prendaEnriquecida,
            'tallas' => $tallas,
            'cantidad' => $cantidadTotal,
            'descripcion_rowspan' => $descripcionRowspan,
            'observaciones' => null, // Se obtendrá de la primera talla si existe
            'fecha_pedido' => $recibo->created_at->format('Y-m-d H:i:s'),
            'fecha_entrega' => null,
            'area' => null,
            'estado_bodega' => WarehouseConstants::STATE_PENDING
        ];
    }

    private function crearItemPrenda(array $variante, array $prendaEnriquecida, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion, ?int $tallaColorId = null, ?int $cantidadOverride = null): array
    {
        $talla = $variante['talla'] ?? '';
        $prendaNombre = $prendaEnriquecida['nombre'] ?? null;
        $cantidad = $cantidadOverride !== null ? (int)$cantidadOverride : ($variante['cantidad'] ?? 0);
        $prendaId = $prendaEnriquecida['id'] ?? null;
        $genero = $variante['genero'] ?? null;
        
        // Obtener datos de bodega con prenda_id y genero para identificación única
        $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $talla, $prendaNombre, $cantidad, $rolesDelUsuario, $tallaColorId, $prendaId, $genero);
        
        $asesor = 'N/A';
        if ($recibo->asesor) {
$asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? WarehouseConstants::DEFAULT_NA;
        }

        $empresa = $recibo->cliente ?? WarehouseConstants::DEFAULT_NA;
        
        return [
            'id' => $recibo->id,
            'tipo' => 'prenda',
            'numero_pedido' => $recibo->numero_pedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'prenda_id' => $prendaEnriquecida['id'] ?? null,
            'talla_color_id' => $tallaColorId,
            'genero' => $genero,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $prendaEnriquecida,
            'talla' => $talla,
            'cantidad' => $bodegaData['cantidad'] ?? $cantidad,
            'cantidad_total' => $cantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $bodegaData['pendientes'] ?? null,
            'fecha_entrega' => $bodegaData['fecha_entrega'],
            'fecha_pedido' => $bodegaData['fecha_pedido'],
            'estado_bodega' => $bodegaData['estado_bodega'],
            'costura_estado' => $bodegaData['costura_estado'] ?? null,
            'epp_estado' => $bodegaData['epp_estado'] ?? null,
            'area' => $bodegaData['area'],
            'usuario_bodega_nombre' => $bodegaData['usuario_nombre'],
            'bodega_id' => $bodegaData['id'],
        ];
    }

    /**
     * Obtener historial completo de homologaciones de un EPP original
     * Sigue la cadena: 371 → 372 → 373
     */
    private function obtenerHistorialEpp(int $pedidoEppIdOriginal): array
    {
        $historial = [];
        $pedidoEppIdActual = $pedidoEppIdOriginal;
        $intentos = 0;
        $maxIntentos = 10; // Protección contra loops infinitos
        
        while ($pedidoEppIdActual !== null && $intentos < $maxIntentos) {
            $intentos++;
            
            $pedidoEpp = \App\Models\PedidoEpp::withTrashed()
                ->with('epp')
                ->find($pedidoEppIdActual);
            
            if (!$pedidoEpp) {
                \Log::warning('[obtenerHistorialEpp] EPP no encontrado', [
                    'pedido_epp_id' => $pedidoEppIdActual
                ]);
                break;
            }
            
            // Agregar al historial
            $historial[] = [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $pedidoEpp->epp_id,
                'epp_nombre' => $pedidoEpp->epp->nombre_completo ?? 'EPP sin nombre',
                'cantidad' => $pedidoEpp->cantidad,
                'fecha_creacion' => $pedidoEpp->created_at?->format('Y-m-d H:i'),
                'deleted_at' => $pedidoEpp->deleted_at?->format('Y-m-d H:i'),
                'observaciones' => $pedidoEpp->observaciones ?? '',
                'es_original' => $pedidoEpp->homologado_de === null,
            ];
            
            // Buscar el siguiente en la cadena (el que tiene homologado_de = id actual)
            $siguiente = \App\Models\PedidoEpp::where('homologado_de', $pedidoEppIdActual)
                ->withTrashed()
                ->first();
            
            $pedidoEppIdActual = $siguiente?->id;
        }
        
        \Log::debug('[obtenerHistorialEpp] Historial completo obtenido', [
            'pedido_epp_id_original' => $pedidoEppIdOriginal,
            'cantidad_cambios' => count($historial)
        ]);
        
        return $historial;
    }

    private function crearItemEpp(array $eppEnriquecido, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion, array $historialeHomologaciones = []): array
    {
        $eppNombre = $eppEnriquecido['nombre'] ?? WarehouseConstants::AREA_EPP;
        $eppCantidad = $eppEnriquecido['cantidad'] ?? 0;
        // Para EPPs, usar el MD5 exacto como está guardado en la base de datos (sin prefijo)
        $eppId = md5($recibo->numero_pedido . '|' . $eppNombre . '|' . $eppCantidad);
        
        // El pedido_epp_id ya viene en los datos enriquecidos, no hay que buscarlo
        $pedidoEppId = $eppEnriquecido['pedido_epp_id'] ?? null;
        
        // PROCESAR HISTORIAL DE HOMOLOGACIONES
        // Si hay más de 1 registro en el historial, existe un botón "Ver cambios"
        $tieneHistorial = count($historialeHomologaciones) > 1;
        
        \Log::debug('[crearItemEpp] Historial recibido', [
            'pedido_epp_id' => $pedidoEppId,
            'cantidad_registros_en_historial' => count($historialeHomologaciones),
            'tiene_botón_ver_cambios' => $tieneHistorial
        ]);
        
        // Obtener datos de bodega
        $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $eppId, $eppNombre, $eppCantidad, $rolesDelUsuario);
        
        \Log::debug('[crearItemEpp] Datos obtenidos de bodega', [
            'eppNombre' => $eppNombre,
            'eppId' => $eppId,
            'bodegaData_keys' => array_keys($bodegaData),
            'area' => $bodegaData['area'] ?? 'NULL',
            'estado_bodega' => $bodegaData['estado_bodega'] ?? 'NULL'
        ]);
        
        // Obtener asesor de forma segura
        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }
        
        // Obtener empresa
        $empresa = $recibo->cliente ?? 'N/A';
        
        // Safeguards para EPPs sin registro en bodega_detalles_talla
        $area = $bodegaData['area'] ?? WarehouseConstants::AREA_EPP;
        $estadoBodega = $bodegaData['estado_bodega'] ?? WarehouseConstants::STATE_PENDING;
        $cantidad = $bodegaData['cantidad'] ?? $eppCantidad;
        $pendientes = $bodegaData['pendientes'] ?? 0;
        $fechaEntrega = $bodegaData['fecha_entrega'] ?? null;
        $fechaPedido = $bodegaData['fecha_pedido'] ?? null;
        
        $itemEpp = [
            'id' => $recibo->id,
            'tipo' => WarehouseConstants::AREA_EPP,
            'numero_pedido' => $recibo->numero_pedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'pedido_epp_id' => $pedidoEppId,
            'tiene_historial' => $tieneHistorial,
            'historial_homologaciones' => $historialeHomologaciones,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $eppEnriquecido,
            'talla' => $eppId,
            'cantidad' => $cantidad,
            'cantidad_total' => $eppCantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $pendientes,
            'fecha_entrega' => $fechaEntrega,
            'fecha_pedido' => $fechaPedido,
            'estado_bodega' => $estadoBodega,
            'costura_estado' => $bodegaData['costura_estado'] ?? null,
            'epp_estado' => $bodegaData['epp_estado'] ?? null,
            'area' => $area,
            'tallas' => [[
                'talla' => $eppId,
                'cantidad' => $cantidad,
                'pendientes' => $pendientes,
                'area' => $area,
                'estado_bodega' => $estadoBodega,
                'pedido_produccion_id' => $bodegaData['id'] ?? null,
                'observaciones' => $bodegaData['observaciones'] ?? '',
                'fecha_entrega' => $fechaEntrega ?? ''
            ]],
        ];
        
        \Log::debug('[crearItemEpp] Item creado', [
            'area' => $itemEpp['area'],
            'tipo' => $itemEpp['tipo'],
            'eppNombre' => $eppNombre,
            'tiene_historial' => $tieneHistorial
        ]);
        
        return $itemEpp;
    }

    private function obtenerDatosBodega(string $numeroPedido, string $talla, ?string $prendaNombre, int $cantidad, array $rolesDelUsuario, ?int $tallaColorId = null, ?int $prendaId = null, ?string $genero = null): array
    {
        // Para EPPs, el talla es un identificador único MD5, buscarlo directamente
        $bodegaDataBase = null;
        
        // Si es un EPP (el talla es un MD5 de 32 caracteres), buscar por el identificador exacto
        if (strlen($talla) === WarehouseConstants::MD5_LENGTH && ctype_xdigit($talla)) {
            // Es un MD5, buscar directamente
            $bodegaDataBase = BodegaDetallesTalla::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                ->where(WarehouseConstants::FIELD_TALLA, $talla)
                ->when($prendaNombre, fn($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                ->first();
        } else {
            // Para prendas, normalizar género a mayúsculas y calcular row_hash
            $generoNormalizado = $genero ? strtoupper($genero) : null;
            
            if ($prendaId && $talla && $generoNormalizado) {
                $rowHash = md5($numeroPedido . '_' . $prendaId . '_' . $talla . '_' . ($tallaColorId ?? '') . '_' . $generoNormalizado);
                
                $bodegaDataBase = BodegaDetallesTalla::where(WarehouseConstants::FIELD_ROW_HASH, $rowHash)->first();
            }
            
            // Si no se encontró por row_hash, buscar por los criterios tradicionales (registros antiguos sin row_hash)
            if (!$bodegaDataBase) {
                $bodegaDataBase = BodegaDetallesTalla::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                    ->where(WarehouseConstants::FIELD_TALLA, $talla)
                    ->when($tallaColorId !== null, function ($q) use ($tallaColorId) {
                        return $q->where(WarehouseConstants::FIELD_TALLA_COLOR_ID, $tallaColorId);
                    }, function ($q) {
                        return $q->whereNull(WarehouseConstants::FIELD_TALLA_COLOR_ID);
                    })
                    ->when($prendaNombre, fn($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                    ->when($prendaId !== null, fn($q) => $q->where(WarehouseConstants::FIELD_PRENDA_ID, $prendaId))
                    ->when($generoNormalizado !== null, fn($q) => $q->where(WarehouseConstants::FIELD_GENERO, $generoNormalizado))
                    ->first();
            }
        }
        
        // Obtener estado específico del rol
        $bodegaDataEstado = null;
        if (in_array(WarehouseConstants::ROLE_EPP_BODEGA, $rolesDelUsuario)) {
            $bodegaDataEstado = EppBodegaDetalle::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                ->where(WarehouseConstants::FIELD_TALLA, $talla)
                ->when($prendaNombre, fn($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                ->first();
        } elseif (in_array(WarehouseConstants::ROLE_COSTURA_BODEGA, $rolesDelUsuario)) {
            $bodegaDataEstado = CosturaBodegaDetalle::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                ->where(WarehouseConstants::FIELD_TALLA, $talla)
                ->when($prendaNombre, fn($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                ->first();
        }
        
        // Determinar qué datos usar
        $datosFinales = $this->seleccionarDatosSegunRol($bodegaDataEstado, $bodegaDataBase, $rolesDelUsuario);

        $estado = $datosFinales?->estado_bodega ?? $bodegaDataBase?->estado_bodega;
        
        // Determinar el estado específico según el área
        $area = $datosFinales?->area ?? $bodegaDataBase?->area;
        $estadoEspecifico = $this->obtenerEstadoEspecifico($area, $estado, $datosFinales, $bodegaDataBase);
        
        // Asegurar que el área tiene valor (especialmente para EPPs sin registro en BD)
        if (empty($area)) {
            $area = WarehouseConstants::AREA_EPP;
        }
        
        // Asegurar que estado_bodega tiene valor por defecto
        if (empty($estado)) {
            $estado = WarehouseConstants::STATE_PENDING;
        }
        
        $resultado = [
            'id' => $datosFinales?->id,
            'estado' => $estadoEspecifico,
            WarehouseConstants::FIELD_ESTADO_BODEGA => $estado,
            WarehouseConstants::FIELD_AREA => $area,
            WarehouseConstants::FIELD_CANTIDAD => $bodegaDataBase?->cantidad,
            WarehouseConstants::FIELD_COSTURA_ESTADO => $bodegaDataBase?->costura_estado,
            WarehouseConstants::FIELD_EPP_ESTADO => $bodegaDataBase?->epp_estado,
            'observaciones' => $datosFinales?->observaciones_bodega ?? $bodegaDataBase?->observaciones_bodega,
            WarehouseConstants::FIELD_PENDIENTES => $datosFinales?->pendientes ?? $bodegaDataBase?->pendientes,
            WarehouseConstants::FIELD_FECHA_ENTREGA => $bodegaDataBase?->fecha_entrega ? Carbon::parse($bodegaDataBase->fecha_entrega)->format('Y-m-d') : null,
            WarehouseConstants::FIELD_FECHA_PEDIDO => $bodegaDataBase?->fecha_pedido ? Carbon::parse($bodegaDataBase->fecha_pedido)->format('Y-m-d') : null,
            'usuario_nombre' => $datosFinales?->usuario_bodega_nombre ?? $bodegaDataBase?->usuario_bodega_nombre,
        ];
        
        \Log::debug('[obtenerDatosBodega] Resultado final', [
            'numeroPedido' => $numeroPedido,
            'talla' => $talla,
            'area' => $resultado[WarehouseConstants::FIELD_AREA],
            'estado_bodega' => $resultado[WarehouseConstants::FIELD_ESTADO_BODEGA],
            'bodegaDataBase_exists' => $bodegaDataBase ? true : false
        ]);
        
        return $resultado;
    }

    private function calcularRowspans(array $items): array
    {
        $items = $this->aplicarRowspans($items, 'asesor_rowspan', fn($item) => $item['asesor']);
        $items = $this->aplicarRowspans($items, 'descripcion_rowspan', fn($item) => $item['asesor'] . '|' . $this->obtenerIdArticulo($item));
        $items = $this->aplicarRowspans($items, 'genero_rowspan', fn($item) => 
            $item['asesor'] . '|' . $this->obtenerIdArticulo($item) . '|' . $this->obtenerGeneroDelItem($item)
        );
        
        return $items;
    }

    /**
     * Aplicar rowspan genérico basado en clave de agrupamiento
     * 
     * @param array $items Items a procesar
     * @param string $fieldRowspan Campo donde guardar el rowspan
     * @param callable $keyFunction Función que retorna la clave de agrupamiento
     */
    private function aplicarRowspans(array $items, string $fieldRowspan, callable $keyFunction): array
    {
        $grupos = [];
        
        // Agrupar items por clave
        foreach ($items as $index => $item) {
            $clave = $keyFunction($item);
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [];
            }
            $grupos[$clave][] = $index;
        }
        
        // Asignar rowspan solo al primer item de cada grupo
        foreach ($grupos as $indices) {
            $rowspan = count($indices);
            foreach ($indices as $itemIndex) {
                $items[$itemIndex][$fieldRowspan] = $itemIndex === $indices[0] ? $rowspan : 0;
            }
        }
        
        return $items;
    }

    private function obtenerIdArticulo(array $item): string
    {
        if (isset($item['prenda_id']) && !empty($item['prenda_id'])) {
            return 'prenda_' . $item['prenda_id'];
        }
        
        if (isset($item['pedido_epp_id']) && !empty($item['pedido_epp_id'])) {
            return 'epp_' . $item['pedido_epp_id'];
        }
        
        $nombreArticulo = $item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? WarehouseConstants::DEFAULT_SIN_NOMBRE;
        return 'nombre_' . md5(strtolower(trim($nombreArticulo)));
    }

    private function obtenerGeneroDelItem(array $item): string
    {
        $genero = '';
        
        if (isset($item['descripcion']['variantes']) && is_array($item['descripcion']['variantes']) && count($item['descripcion']['variantes']) > 0) {
            foreach ($item['descripcion']['variantes'] as $variante) {
                if (($variante['talla'] ?? '') === ($item['talla'] ?? '')) {
                    $genero = $variante['genero'] ?? '';
                    break;
                }
            }
            if (empty($genero)) {
                $genero = $item['descripcion']['variantes'][0]['genero'] ?? '';
            }
        } elseif (isset($item['genero'])) {
            $genero = $item['genero'];
        }
        
        $genero = strtoupper(trim($genero));
        return (empty($genero) || $genero === WarehouseConstants::GENERIC_GENDER) ? WarehouseConstants::GENERIC_GENDER : $genero;
    }

    private function seleccionarDatosSegunRol($bodegaDataEstado, $bodegaDataBase, array $rolesDelUsuario)
    {
        $tieneRolEspecifico = in_array(WarehouseConstants::ROLE_EPP_BODEGA, $rolesDelUsuario) || in_array(WarehouseConstants::ROLE_COSTURA_BODEGA, $rolesDelUsuario);
        return $tieneRolEspecifico ? $bodegaDataEstado : $bodegaDataBase;
    }

    private function obtenerEstadoEspecifico(?string $area, ?string $estado, $datosFinales, $bodegaDataBase): string
    {
        $estadoMap = [
            WarehouseConstants::AREA_COSTURA => fn() => $datosFinales?->costura_estado ?? $bodegaDataBase?->costura_estado ?? $estado,
            WarehouseConstants::AREA_EPP => fn() => $datosFinales?->epp_estado ?? $bodegaDataBase?->epp_estado ?? $estado,
        ];
        
        $estadoResultado = isset($estadoMap[$area]) ? $estadoMap[$area]() : $estado;
        
        return $estadoResultado ?? WarehouseConstants::STATE_PENDING;
    }

    private function guardarDatosBasicos(array $validatedData, PedidoProduccion $pedido, ?User $usuario, array $rolesDelUsuario): BodegaDetallesTalla
    {
        try {
            // Paso 1: Preparar datos básicos
            $datosBasicos = $this->prepararDatosBasicos($validatedData, $usuario);
            
            // Paso 2: Procesar área
            $areaFinal = $this->procesarAreaEnDatos($datosBasicos, $validatedData, $pedido);
            
            // Paso 3: Procesar estados
            $this->procesarEstadosEnDatos($datosBasicos, $validatedData, $areaFinal);
            
            // Paso 4: Buscar o crear registro
            $detalleExistente = $this->encontrarOCrearDetalle($validatedData, $pedido, $datosBasicos);
            
            // Paso 5: Guardar
            $detalleExistente->fill($datosBasicos);
            $detalleExistente->save();
            
            return $detalleExistente;
            
        } catch (\Throwable $e) {
            \Log::error('[ERROR] Error en guardarDatosBasicos:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Preparar el array base de datos a guardar
     */
    private function prepararDatosBasicos(array $validatedData, ?User $usuario): array
    {
        return [
            'pedido_produccion_id' => $validatedData['pedido_produccion_id'] ?? null,
            'recibo_prenda_id' => $validatedData['recibo_prenda_id'] ?? null,
            'prenda_id' => $validatedData['prenda_id'] ?? null,
            'pedido_epp_id' => $validatedData['pedido_epp_id'] ?? null,
            'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
            'talla_color_id' => $validatedData['talla_color_id'] ?? null,
            'asesor' => $validatedData['asesor'] ?? null,
            'empresa' => $validatedData['empresa'] ?? null,
            'cantidad' => $validatedData['cantidad'] ?? 0,
            'pendientes' => $validatedData['pendientes'] ?? null,
            'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
            'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
            'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
            'usuario_bodega_id' => $usuario?->id,
            'usuario_bodega_nombre' => $usuario?->name,
        ];
    }

    /**
     * Procesar y establecer el área en los datos
     */
    private function procesarAreaEnDatos(array &$datosBasicos, array $validatedData, PedidoProduccion $pedido): ?string
    {
        $areaInput = $validatedData[WarehouseConstants::FIELD_AREA] ?? null;
        $areaInput = is_string($areaInput) ? trim($areaInput) : $areaInput;
        
        if (empty($areaInput)) {
            // Buscar área de registro existente
            $areaExistente = BodegaDetallesTalla::where(WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID, $pedido->id)
                ->where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO])
                ->where(WarehouseConstants::FIELD_TALLA, $validatedData[WarehouseConstants::FIELD_TALLA])
                ->where(WarehouseConstants::FIELD_TALLA_COLOR_ID, $validatedData[WarehouseConstants::FIELD_TALLA_COLOR_ID] ?? null)
                ->when(isset($validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE]), 
                    fn($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE]))
                ->when(isset($validatedData[WarehouseConstants::FIELD_CANTIDAD]), 
                    fn($q) => $q->where(WarehouseConstants::FIELD_CANTIDAD, $validatedData[WarehouseConstants::FIELD_CANTIDAD]))
                ->value(WarehouseConstants::FIELD_AREA);
            $areaFinal = $areaExistente;
        } else {
            $areaFinal = $areaInput;
        }
        
        // Establecer área si existe
        if (!empty($areaFinal)) {
            $datosBasicos[WarehouseConstants::FIELD_AREA] = $areaFinal;
        }
        
        return $areaFinal;
    }

    /**
     * Procesar y establecer los estados en los datos
     */
    private function procesarEstadosEnDatos(array &$datosBasicos, array $validatedData, ?string $areaFinal): void
    {
        // Procesar estado_bodega directo
        if (array_key_exists(WarehouseConstants::FIELD_ESTADO_BODEGA, $validatedData) 
            && $validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] !== null) {
            $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = 
                $validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] ?: WarehouseConstants::STATE_PENDING;
        }
        
        // Procesar estado genérico y propagar a área específica
        if (array_key_exists('estado', $validatedData) && $validatedData['estado'] !== null) {
            $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = $validatedData['estado'];
            
            if (!empty($areaFinal)) {
                if ($areaFinal === WarehouseConstants::AREA_COSTURA) {
                    $datosBasicos[WarehouseConstants::FIELD_COSTURA_ESTADO] = $validatedData['estado'];
                } elseif ($areaFinal === WarehouseConstants::AREA_EPP) {
                    $datosBasicos[WarehouseConstants::FIELD_EPP_ESTADO] = $validatedData['estado'];
                }
            }
        }
        
        // Procesar costura_estado
        if (array_key_exists(WarehouseConstants::FIELD_COSTURA_ESTADO, $validatedData)) {
            $datosBasicos[WarehouseConstants::FIELD_COSTURA_ESTADO] = $validatedData[WarehouseConstants::FIELD_COSTURA_ESTADO];
            if (!array_key_exists('estado', $validatedData)) {
                $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = $validatedData[WarehouseConstants::FIELD_COSTURA_ESTADO];
            }
        }
        
        // Procesar epp_estado
        if (array_key_exists(WarehouseConstants::FIELD_EPP_ESTADO, $validatedData)) {
            $datosBasicos[WarehouseConstants::FIELD_EPP_ESTADO] = $validatedData[WarehouseConstants::FIELD_EPP_ESTADO];
            if (!array_key_exists('estado', $validatedData)) {
                $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = $validatedData[WarehouseConstants::FIELD_EPP_ESTADO];
            }
        }
    }

    /**
     * Buscar registro existente o crear uno nuevo
     */
    private function encontrarOCrearDetalle(array $validatedData, PedidoProduccion $pedido, array $datosBasicos): BodegaDetallesTalla
    {
        $criterios = [
            WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
            WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
            WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
            WarehouseConstants::FIELD_TALLA_COLOR_ID => $validatedData[WarehouseConstants::FIELD_TALLA_COLOR_ID] ?? null,
            WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
            WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
        ];
        
        $registros = BodegaDetallesTalla::where($criterios)->get();
        
        // Priorizar registro por IDs
        $detalle = null;
        if ($registros->count() > 0) {
            $detalle = $registros->first(function($registro) use ($datosBasicos) {
                if ($datosBasicos['prenda_id'] && $registro->prenda_id == $datosBasicos['prenda_id']) {
                    return true;
                }
                if ($datosBasicos['pedido_epp_id'] && $registro->pedido_epp_id == $datosBasicos['pedido_epp_id']) {
                    return true;
                }
                return $registro->prenda_id || $registro->pedido_epp_id;
            });
            
            if (!$detalle) {
                $detalle = $registros->first();
            }
        }
        
        // Si no existe, crear nuevo
        if (!$detalle) {
            $detalle = new BodegaDetallesTalla();
            $detalle->pedido_produccion_id = $pedido->id;
            $detalle->numero_pedido = $validatedData['numero_pedido'];
            $detalle->talla = $validatedData['talla'];
            $detalle->talla_color_id = $validatedData['talla_color_id'] ?? null;
            $detalle->prenda_nombre = $validatedData['prenda_nombre'] ?? null;
            $detalle->cantidad = $validatedData['cantidad'] ?? 0;
        }
        
        return $detalle;
    }

    
    /**
     * Registrar la entrega de una prenda cuando un pedido cambia a estado 'Entregado'
     */
    public function registrarEntregaPrenda(array $datosPrenda, int $pedidoProduccionId): array
    {
        try {
            return app(EntregaService::class)->registrarEntregaPrenda($datosPrenda, $pedidoProduccionId);
        } catch (\Exception $e) {
            \Log::error('[BODEGA] Error al registrar entrega de prenda', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'datos_prenda' => $datosPrenda,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Registrar entregas masivas cuando un pedido cambia a estado 'Entregado'
     */
    public function registrarEntregasMasivas(int $pedidoProduccionId, array $prendasEntregadas): array
    {
        try {
            return app(EntregaService::class)->registrarEntregasMasivas($pedidoProduccionId, $prendasEntregadas);
        } catch (\Exception $e) {
            \Log::error('[BODEGA] Error al registrar entregas masivas', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prendas_entregadas_count' => count($prendasEntregadas),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    private function guardarEstadoPorRol(array $validatedData, PedidoProduccion $pedido, ?User $usuario, array $rolesDelUsuario): ?Model
    {
        if (in_array(WarehouseConstants::ROLE_EPP_BODEGA, $rolesDelUsuario)) {
            $estadoNuevo = $validatedData[WarehouseConstants::FIELD_EPP_ESTADO] ?? ($validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] ?? WarehouseConstants::STATE_PENDING);
            if (empty($estadoNuevo)) {
                $estadoNuevo = WarehouseConstants::STATE_PENDING;
            }
            return $this->guardarEstadoArea(
                $validatedData,
                $pedido,
                $usuario,
                $estadoNuevo,
                EppBodegaDetalle::class,
                WarehouseConstants::AREA_EPP,
                WarehouseConstants::FIELD_EPP_ESTADO
            );
        } elseif (in_array(WarehouseConstants::ROLE_COSTURA_BODEGA, $rolesDelUsuario)) {
            $estadoNuevo = $validatedData[WarehouseConstants::FIELD_COSTURA_ESTADO] ?? ($validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] ?? WarehouseConstants::STATE_PENDING);
            if (empty($estadoNuevo)) {
                $estadoNuevo = WarehouseConstants::STATE_PENDING;
            }
            return $this->guardarEstadoArea(
                $validatedData,
                $pedido,
                $usuario,
                $estadoNuevo,
                CosturaBodegaDetalle::class,
                WarehouseConstants::AREA_COSTURA,
                WarehouseConstants::FIELD_COSTURA_ESTADO
            );
        }
        
        // Para bodeguero, ya se guardó en guardarDatosBasicos
        return null;
    }

    /**
     * Guardar estado en tabla específica de área (EPP o Costura)
     * Función genérica que elimina duplicación entre guardarEstadoEpp y guardarEstadoCostura
     */
    private function guardarEstadoArea(
        array $validatedData,
        PedidoProduccion $pedido,
        ?User $usuario,
        string $estadoNuevo,
        string $modelClass,
        string $areaDefault,
        string $stateFieldName
    )
    {
        $datosArea = [
            WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
            WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
            WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
            WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
            WarehouseConstants::FIELD_ASESOR => $validatedData[WarehouseConstants::FIELD_ASESOR] ?? null,
            WarehouseConstants::FIELD_EMPRESA => $validatedData[WarehouseConstants::FIELD_EMPRESA] ?? null,
            WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
            WarehouseConstants::FIELD_PENDIENTES => $validatedData[WarehouseConstants::FIELD_PENDIENTES] ?? null,
            WarehouseConstants::FIELD_OBSERVACIONES_BODEGA => $validatedData[WarehouseConstants::FIELD_OBSERVACIONES_BODEGA] ?? null,
            WarehouseConstants::FIELD_FECHA_PEDIDO => $validatedData[WarehouseConstants::FIELD_FECHA_PEDIDO] ?? null,
            WarehouseConstants::FIELD_FECHA_ENTREGA => $validatedData[WarehouseConstants::FIELD_FECHA_ENTREGA] ?? null,
            WarehouseConstants::FIELD_AREA => $validatedData[WarehouseConstants::FIELD_AREA] ?? $areaDefault,
            WarehouseConstants::FIELD_ESTADO_BODEGA => $estadoNuevo,
            WarehouseConstants::FIELD_USUARIO_BODEGA_ID => $usuario->id,
            WarehouseConstants::FIELD_USUARIO_BODEGA_NOMBRE => $usuario->name,
        ];

        // No tocar estado_bodega si no viene explícitamente en el request
        if (!array_key_exists(WarehouseConstants::FIELD_ESTADO_BODEGA, $validatedData)) {
            unset($datosArea[WarehouseConstants::FIELD_ESTADO_BODEGA]);
        }
        
        // Guardar en tabla específica de área (EppBodegaDetalle o CosturaBodegaDetalle)
        $guardado = call_user_func(
            [$modelClass, 'updateOrCreate'],
            [
                WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
                WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
                WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
                WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
                WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
            ],
            $datosArea
        );

        // 🔄 SINCRONIZAR en bodega_detalles_talla
        // Actualizar solo los campos que corresponden a esta área
        $updateBase = [
            $stateFieldName => $validatedData[$stateFieldName] ?? $estadoNuevo,
            WarehouseConstants::FIELD_AREA => $validatedData[WarehouseConstants::FIELD_AREA] ?? $areaDefault,
        ];
        
        // Solo actualizar estado_bodega si vino explícitamente en el request
        if (array_key_exists(WarehouseConstants::FIELD_ESTADO_BODEGA, $validatedData)) {
            $updateBase[WarehouseConstants::FIELD_ESTADO_BODEGA] = $estadoNuevo;
        }

        // Sincronizar en tabla base
        BodegaDetallesTalla::updateOrCreate(
            [
                WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
                WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
                WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
                WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
                WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
            ],
            $updateBase
        );

        return $guardado;
    }

    private function dispararEventoTiempoReal(array $validatedData)
    {
        try {
            \App\Events\BodegaDetallesActualizados::dispatch(
                $validatedData['numero_pedido'],
                $validatedData['talla'],
                [
                    'pendientes' => $validatedData['pendientes'] ?? null,
                    'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
                    'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
                    'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
                    'estado_bodega' => $validatedData['estado_bodega'] ?? null,
                    'area' => $validatedData['area'] ?? null,
                ]
            );
        } catch (\Exception $websocketError) {
            \Log::warning('WebSocket no disponible para tiempo real, pero datos guardados correctamente', [
                'websocket_error' => $websocketError->getMessage(),
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla']
            ]);
        }
    }
    
    /**
     * Filtrar items para despacho: mostrar solo items con estado "Pendiente"
     * - Para todas las áreas (Costura, EPP, Otro): estado_bodega = 'Pendiente'
     */
    private function filtrarItemsParaDespacho(array $items): array
    {
        $itemsFiltrados = [];
        
        \Log::info('[DESPACHO-FILTRO] Inicio filtrado por estado_bodega = Pendiente', [
            'items_totales' => count($items),
            'items_recibidos' => array_map(fn($item) => [
                'numero_pedido' => $item['numero_pedido'],
                'tipo' => $item['tipo'] ?? 'unknown',
                'area' => $item['area'] ?? 'unknown',
                'tiene_tallas' => isset($item['tallas']) && is_array($item['tallas'])
            ], $items)
        ]);
        
        foreach ($items as $item) {
            $area = $item['area'] ?? '';
            $tallas = $item['tallas'] ?? [];
            
            // Filtrar tallas por estado_bodega (aplica para todas las áreas)
            $tallasFiltradas = [];
            foreach ($tallas as $talla) {
                // Verificar estado_bodega = 'Pendiente' para todas las áreas
                $estadoPendiente = ($talla['estado_bodega'] ?? '') === WarehouseConstants::STATE_PENDING;
                
                if ($estadoPendiente) {
                    $tallasFiltradas[] = $talla;
                }
            }
            
            // Solo incluir el item si tiene tallas con estado Pendiente
            if (!empty($tallasFiltradas)) {
                $item['tallas'] = $tallasFiltradas; // Reemplazar con tallas filtradas
                $itemsFiltrados[] = $item;
            }
        }
        
        return $itemsFiltrados;
    }

    /**
     * Verificar y actualizar el estado del pedido según los ítems en bodega_detalles_talla
     */
    private function verificarYActualizarEstadoPedido(PedidoProduccion $pedido): void
    {
        try {
            // Obtener todos los ítems del pedido en bodega_detalles_talla
            $itemsBodega = \DB::table('bodega_detalles_talla')
                ->where('pedido_produccion_id', $pedido->id)
                ->get();
            
            if ($itemsBodega->isEmpty()) {
                return;
            }
            
            // Contar estados de los ítems
            $estadosCount = $itemsBodega->groupBy('estado_bodega')->map->count();
            
            // IMPORTANTE: NO actualizar el estado del pedido principal desde bodega
            // Los cambios de estado deben manejarse exclusivamente desde el módulo de despacho
            
            // SIEMPRE disparar evento de actualización cuando hay cambios en bodega
            // para que el frontend de despacho se actualice en tiempo real
            
            // Incluir información adicional sobre los cambios en bodega
            $changedFields = [
                'estado' => $pedido->estado,
                'bodega_items_count' => $itemsBodega->count(),
                'bodega_pendientes_count' => $estadosCount[WarehouseConstants::STATE_PENDING] ?? 0,
                'bodega_entregados_count' => $estadosCount[WarehouseConstants::STATE_DELIVERED] ?? 0,
                'ultima_actualizacion_bodega' => now()->toISOString()
            ];
            
            event(new \App\Events\PedidoActualizado($pedido, auth()->user(), $changedFields, 'updated'));
            
        } catch (\Exception $e) {
            \Log::error('[BodegaPedidoService] Error verificando estado del pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
