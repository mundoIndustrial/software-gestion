<?php

namespace App\Application\Bodega\Services;

use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use App\Models\PedidoAuditoria;
use App\Application\Services\EntregaService;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BodegaPedidoService
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private PedidoProduccionRepository $pedidoRepository,
        private BodegaRoleService $roleService,
        private BodegaRepository $bodegaRepository
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
        if ($request->query('view') === 'details') {
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
            ->where('estado', 'Anulada')
            ->pluck('numero_pedido')
            ->filter(fn($n) => !empty($n))
            ->unique()
            ->values();
        
        \Log::info('[BodegaPedidoService] Pedidos anulados encontrados', [
            'total' => $numerosAnulados->count(),
            'numeros' => $numerosAnulados->toArray(),
            'query_sql' => PedidoProduccion::where('estado', 'Anulada')->toSql()
        ]);

        // Cargar recibos por número de pedido SIN filtrar por estado del recibo,
        // para que el listado siempre muestre los pedidos anulados.
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosAnulados)
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Filtrar por áreas según rol
        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);

        // Paginar
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        // Procesar datos para vista
        if ($request->query('view') === 'details') {
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
            ->where('estado_bodega', 'Entregado')
            ->pluck('numero_pedido')
            ->filter(fn($n) => !empty($n))
            ->unique()
            ->values();
        
        \Log::info('[BodegaPedidoService] Pedidos con items entregados encontrados', [
            'total' => $numerosConEntregados->count(),
            'numeros' => $numerosConEntregados->toArray()
        ]);

        // Cargar recibos por número de pedido que tengan items entregados
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosConEntregados)
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Filtrar por áreas según rol
        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);

        // Paginar
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        // Procesar datos para vista de lista con cálculo de estados
        $pedidosPorPagina = [];
        $pedidosAgrupados = $pedidosFiltradosPorRol->groupBy('numero_pedido');

        foreach ($pedidosAgrupados as $numeroPedido => $pedidosDelNumero) {
            $primerPedido = $pedidosDelNumero->first();
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            // Verificar si hay items pendientes en bodega
            $tieneItemsPendientes = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                ->where('estado_bodega', 'Pendiente')
                ->exists();
            
            // Verificar si todos los items están entregados
            $totalItems = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                ->where('estado_bodega', '!=', 'Anulado')
                ->count();
            
            $itemsEntregados = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                ->where('estado_bodega', 'Entregado')
                ->count();
            
            $todosEntregados = $totalItems > 0 && $totalItems === $itemsEntregados;
            
            $pedidosPorPagina[] = [
                'id' => $primerPedido->id,
                'numero_pedido' => $numeroPedido,
                'cliente' => $primerPedido->cliente ?? 'N/A',
                'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? 'N/A',
                'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                'cantidad_items' => $pedidosDelNumero->count(),
                'viewed_at' => $pedidoProduccion?->viewed_at,
                'tiene_pendientes' => $tieneItemsPendientes,
                'todos_entregados' => $todosEntregados,
            ];

        }

        // Procesar datos para vista
        if ($request->query('view') === 'details') {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }

        return [
            'view_type' => 'list',
            'pedidos_por_pagina' => $pedidosPorPagina,
            'total_pedidos' => count($pedidosPorPagina),
            'pagina_actual' => $paginacion['pagina_actual'],
            'por_pagina' => $paginacion['por_pagina'],
        ];
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
        return ['Pendiente', 'EN EJECUCIÓN', 'NO INICIADO', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA', 'Entregado'];
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
            ->whereRaw('UPPER(TRIM(estado)) = ?', ['ANULADA'])
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
        $porPagina = 20;
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
                
                $fechaPedido = \Carbon\Carbon::parse($fechaPedido);
                
                // Verificar fecha desde
                if ($filtroFechaDesde) {
                    $fechaDesde = \Carbon\Carbon::parse($filtroFechaDesde)->startOfDay();
                    if ($fechaPedido->lt($fechaDesde)) {
                        return false;
                    }
                }
                
                // Verificar fecha hasta
                if ($filtroFechaHasta) {
                    $fechaHasta = \Carbon\Carbon::parse($filtroFechaHasta)->endOfDay();
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
            'view_type' => 'details',
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
                
                // Verificar si hay items pendientes en bodega
                $tieneItemsPendientes = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                    ->where('estado_bodega', 'Pendiente')
                    ->exists();
                
                // Verificar si todos los items están entregados
                $totalItems = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                    ->where('estado_bodega', '!=', 'Anulado')
                    ->count();
                
                $itemsEntregados = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                    ->where('estado_bodega', 'Entregado')
                    ->count();
                
                $todosEntregados = $totalItems > 0 && $totalItems === $itemsEntregados;
                
                $pedidosPorPagina[] = [
                    'id' => $primerPedido->id,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => $primerPedido->cliente ?? 'N/A',
                    'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? 'N/A',
                    'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                    'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                    'cantidad_items' => $pedidosDelNumero->count(),
                    'viewed_at' => $pedidoProduccion?->viewed_at,
                    'tiene_pendientes' => $tieneItemsPendientes,
                    'todos_entregados' => $todosEntregados,
                ];
            }
        }

        return [
            'view_type' => 'list',
            'pedidos_por_pagina' => $pedidosPorPagina,
            'total_pedidos' => $paginacion['total_pedidos'],
            'pagina_actual' => $paginacion['pagina_actual'],
            'por_pagina' => $paginacion['por_pagina'],
        ];
    }

    private function procesarItemsPedidoParaDespacho(Collection $recibos, array $rolesDelUsuario, array $areasPermitidas): array
    {
        $items = [];
        
        // Obtener info del pedido para usar en los items
        $numeroPedido = $recibos->first()->numero_pedido;
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        foreach ($recibos as $recibo) {
            try {
                $datosCompletos = $this->obtenerPedidoUseCase->ejecutar($recibo->id);
                
                // Procesar prendas
                if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                    $items = array_merge($items, $this->procesarPrendasParaDespacho($datosCompletos->prendas, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion));
                }
                
                // Procesar EPPs
                if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                    $items = array_merge($items, $this->procesarEpps($datosCompletos->epps, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion));
                }
                
                // Filtrar items para despacho: solo area EPP/Costura con epp_estado = Pendiente
                $items = $this->filtrarItemsParaDespacho($items);
                
            } catch (\Exception $e) {
                \Log::warning('[Bodega Show] Error al obtener datos del pedido', [
                    'recibo_id' => $recibo->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $items;
    }

    private function procesarPrendasParaDespacho(array $prendas, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        $items = [];
        
        foreach ($prendas as $prendaEnriquecida) {
            $variantes = $prendaEnriquecida['variantes'] ?? [];
            
            // Agrupar todas las variantes de esta prenda en un solo item
            $items[] = $this->crearItemPrendaConTallas($variantes, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
        }
        
        return $items;
    }

    private function procesarItemsPedido(Collection $recibos, array $rolesDelUsuario, array $areasPermitidas): array
    {
        $items = [];
        
        // Obtener info del pedido para usar en los items
        $numeroPedido = $recibos->first()->numero_pedido;
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        foreach ($recibos as $recibo) {
            try {
                $datosCompletos = $this->obtenerPedidoUseCase->ejecutar($recibo->id);
                
                // Procesar prendas
                if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                    $items = array_merge($items, $this->procesarPrendas($datosCompletos->prendas, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion));
                }
                
                // Procesar EPPs
                if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                    $items = array_merge($items, $this->procesarEpps($datosCompletos->epps, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion));
                }
                
            } catch (\Exception $e) {
                \Log::warning('[Bodega Show] Error al obtener datos del pedido', [
                    'recibo_id' => $recibo->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $items;
    }

    private function procesarPrendas(array $prendas, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        $items = [];
        
        foreach ($prendas as $prendaEnriquecida) {
            $variantes = $prendaEnriquecida['variantes'] ?? [];
            
            foreach ($variantes as $variante) {
                $items[] = $this->crearItemPrenda($variante, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
            }
        }
        
        return $items;
    }

    private function procesarEpps(array $epps, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        $items = [];
        
        foreach ($epps as $eppEnriquecido) {
            $items[] = $this->crearItemEpp($eppEnriquecido, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
        }
        
        return $items;
    }

    private function crearItemPrendaConTallas(array $variantes, array $prendaEnriquecida, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        // DEBUG: Ver qué datos vienen en la prenda
        \Log::info('[crearItemPrendaConTallas] Datos de prenda:', [
            'prenda_nombre' => $prendaEnriquecida['nombre'] ?? 'null',
            'procesos' => $prendaEnriquecida['procesos'] ?? 'null',
            'variantes_count' => count($variantes),
            'recibo_id' => $recibo->id
        ]);
        
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
            
            // Obtener datos de bodega para esta talla específica
            $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $talla, $prendaEnriquecida['nombre'] ?? null, $cantidad, $rolesDelUsuario);
            
            $tallas[] = [
                'talla' => $talla,
                'cantidad' => $cantidad,
                'pendientes' => $bodegaData['pendientes'] ?? 0,
                'area' => $bodegaData['area'] ?? '',
                'estado' => $bodegaData['estado'] ?? 'Pendiente',
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
            'estado_bodega' => 'Pendiente'
        ];
    }

    private function crearItemPrenda(array $variante, array $prendaEnriquecida, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        $talla = $variante['talla'] ?? '';
        $prendaNombre = $prendaEnriquecida['nombre'] ?? null;
        $cantidad = $variante['cantidad'] ?? 0;
        
        // Obtener datos de bodega
        $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $talla, $prendaNombre, $cantidad, $rolesDelUsuario);
        
        // Obtener asesor de forma segura
        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }
        
        // Obtener empresa
        $empresa = $recibo->cliente ?? 'N/A';
        
        \Log::debug('[crearItemPrenda] Datos', [
            'numero_pedido' => $recibo->numero_pedido,
            'asesor_id' => $recibo->asesor_id,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'cliente' => $recibo->cliente
        ]);
        
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
            'talla' => $talla,
            'cantidad' => $bodegaData['cantidad'] ?? $cantidad,
            'cantidad_total' => $cantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $bodegaData['pendientes'] ?? null,
            'fecha_entrega' => $bodegaData['fecha_entrega'],
            'fecha_pedido' => $bodegaData['fecha_pedido'],
            'estado_bodega' => $bodegaData['estado'],
            'costura_estado' => $bodegaData['costura_estado'] ?? null,
            'epp_estado' => $bodegaData['epp_estado'] ?? null,
            'area' => $bodegaData['area'],
            'usuario_bodega_nombre' => $bodegaData['usuario_nombre'],
            'bodega_id' => $bodegaData['id'],
        ];
    }

    private function crearItemEpp(array $eppEnriquecido, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        $eppNombre = $eppEnriquecido['nombre'] ?? 'EPP';
        $eppCantidad = $eppEnriquecido['cantidad'] ?? 0;
        // Para EPPs, usar el MD5 exacto como está guardado en la base de datos (sin prefijo)
        $eppId = md5($recibo->numero_pedido . '|' . $eppNombre . '|' . $eppCantidad);
        
        // El pedido_epp_id ya viene en los datos enriquecidos, no hay que buscarlo
        $pedidoEppId = $eppEnriquecido['pedido_epp_id'] ?? null;
        
        \Log::info('[crearItemEpp] Datos EPP para búsqueda:', [
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'epp_enriquecido_id' => $eppEnriquecido['id'] ?? 'null',
            'epp_nombre' => $eppNombre,
            'epp_cantidad' => $eppCantidad,
            'pedido_epp_id_directo' => $pedidoEppId,
            'epp_enriquecido_completo' => $eppEnriquecido
        ]);
        
        // Ya no necesitamos buscar en BD, el ID viene directamente
        \Log::info('[crearItemEpp] Resultado búsqueda pedido_epp:', [
            'pedido_epp_encontrado' => $pedidoEppId ? $pedidoEppId : 'null',
            'pedido_epp_datos' => $pedidoEppId ? [
                'id' => $pedidoEppId,
                'usado_directamente_desde' => 'epp_enriquecido.pedido_epp_id'
            ] : 'null'
        ]);
        
        // Obtener datos de bodega
        $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $eppId, $eppNombre, $eppCantidad, $rolesDelUsuario);
        
        // Obtener asesor de forma segura
        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }
        
        // Obtener empresa
        $empresa = $recibo->cliente ?? 'N/A';
        
        \Log::debug('[crearItemEpp] Datos', [
            'numero_pedido' => $recibo->numero_pedido,
            'epp_id' => $eppId,
            'epp_nombre' => $eppNombre,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'cliente' => $recibo->cliente,
            'bodega_area' => $bodegaData['area'] ?? 'null',
            'bodega_estado' => $bodegaData['estado'] ?? 'null'
        ]);
        
        \Log::info('[crearItemEpp] Datos generados para vista:', [
            'id' => $recibo->id,
            'tipo' => 'epp',
            'numero_pedido' => $recibo->numero_pedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'pedido_epp_id' => $pedidoEppId,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $eppEnriquecido,
            'talla' => $eppId,
            'cantidad' => $bodegaData['cantidad'] ?? $eppCantidad,
            'cantidad_total' => $eppCantidad,
            'estado_bodega' => $bodegaData['estado'],
            'area' => $bodegaData['area'],
            'pedido_epp_usado' => $pedidoEppId ? [
                'id' => $pedidoEppId,
                'fuente' => 'epp_enriquecido.pedido_epp_id (directo)'
            ] : null
        ]);
        
        return [
            'id' => $recibo->id,
            'tipo' => 'epp',
            'numero_pedido' => $recibo->numero_pedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'pedido_epp_id' => $pedidoEppId,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $eppEnriquecido,
            'talla' => $eppId,
            'cantidad' => $bodegaData['cantidad'] ?? $eppCantidad,
            'cantidad_total' => $eppCantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $bodegaData['pendientes'] ?? null,
            'fecha_entrega' => $bodegaData['fecha_entrega'],
            'fecha_pedido' => $bodegaData['fecha_pedido'],
            'estado_bodega' => $bodegaData['estado'],
            'costura_estado' => $bodegaData['costura_estado'] ?? null,
            'epp_estado' => $bodegaData['epp_estado'] ?? null,
            'area' => $bodegaData['area'],
            'tallas' => [[
                'talla' => $eppId,
                'cantidad' => $bodegaData['cantidad'] ?? $eppCantidad,
                'pendientes' => $bodegaData['pendientes'] ?? 0,
                'area' => $bodegaData['area'] ?? '',
                'estado' => $bodegaData['estado'] ?? 'Pendiente',
                'pedido_produccion_id' => $bodegaData['id'] ?? null,
                'observaciones' => $bodegaData['observaciones'] ?? '',
                'fecha_entrega' => $bodegaData['fecha_entrega'] ?? ''
            ]],
        ];
    }

    private function obtenerDatosBodega(string $numeroPedido, string $talla, ?string $prendaNombre, int $cantidad, array $rolesDelUsuario): array
    {
        // Para EPPs, el talla es un identificador único MD5, buscarlo directamente
        $bodegaDataBase = null;
        
        // Si es un EPP (el talla es un MD5 de 32 caracteres), buscar por el identificador exacto
        if (strlen($talla) === 32 && ctype_xdigit($talla)) {
            // Es un MD5, buscar directamente
            $bodegaDataBase = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->first();
        } else {
            // Para prendas, buscar por talla tradicional
            $bodegaDataBase = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->first();
        }
        
        // Obtener estado específico del rol
        $bodegaDataEstado = null;
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $bodegaDataEstado = EppBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->first();
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $bodegaDataEstado = CosturaBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->first();
        }
        
        // Determinar qué datos usar
        $datosFinales = in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)
            ? $bodegaDataEstado
            : $bodegaDataBase;

        $estado = $datosFinales?->estado_bodega ?? $bodegaDataBase?->estado_bodega;
        
        // Determinar el estado específico según el área
        $area = $datosFinales?->area ?? $bodegaDataBase?->area;
        $estadoEspecifico = $estado;
        
        if ($area === 'Costura') {
            $estadoEspecifico = $datosFinales?->costura_estado ?? $bodegaDataBase?->costura_estado ?? $estado;
        } elseif ($area === 'EPP') {
            $estadoEspecifico = $datosFinales?->epp_estado ?? $bodegaDataBase?->epp_estado ?? $estado;
        }
        
        return [
            'id' => $datosFinales?->id,
            'estado' => $estadoEspecifico,
            'estado_bodega' => $estado,
            'area' => $area,
            'cantidad' => $bodegaDataBase?->cantidad,
            'costura_estado' => $bodegaDataBase?->costura_estado,
            'epp_estado' => $bodegaDataBase?->epp_estado,
            'observaciones' => $datosFinales?->observaciones_bodega ?? $bodegaDataBase?->observaciones_bodega,
            'pendientes' => $datosFinales?->pendientes ?? $bodegaDataBase?->pendientes,
            'fecha_entrega' => $bodegaDataBase?->fecha_entrega ? Carbon::parse($bodegaDataBase->fecha_entrega)->format('Y-m-d') : null,
            'fecha_pedido' => $bodegaDataBase?->fecha_pedido ? Carbon::parse($bodegaDataBase->fecha_pedido)->format('Y-m-d') : null,
            'usuario_nombre' => $datosFinales?->usuario_bodega_nombre ?? $bodegaDataBase?->usuario_bodega_nombre,
        ];
    }

    private function calcularRowspans(array $items): array
    {
        // Agrupar por asesor para calcular rowspan
        $porAsesor = [];
        foreach ($items as $index => $item) {
            $asesor = $item['asesor'];
            if (!isset($porAsesor[$asesor])) {
                $porAsesor[$asesor] = [];
            }
            $porAsesor[$asesor][] = $index;
        }
        
        // Asignar rowspans para asesor
        foreach ($porAsesor as $asesor => $indices) {
            $rowspan = count($indices);
            foreach ($indices as $itemIndex) {
                $items[$itemIndex]['asesor_rowspan'] = $itemIndex === $indices[0] ? $rowspan : 0;
            }
        }
        
        // Agrupar por artículo para calcular rowspan
        $porArticulo = [];
        foreach ($items as $index => $item) {
            $nombreArticulo = $item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? 'Sin nombre';
            $asesor = $item['asesor'];
            $clave = $asesor . '|' . $nombreArticulo;
            
            if (!isset($porArticulo[$clave])) {
                $porArticulo[$clave] = [];
            }
            $porArticulo[$clave][] = $index;
        }
        
        // Asignar rowspans para artículo
        foreach ($porArticulo as $clave => $indices) {
            $rowspan = count($indices);
            foreach ($indices as $itemIndex) {
                $items[$itemIndex]['descripcion_rowspan'] = $itemIndex === $indices[0] ? $rowspan : 0;
            }
        }
        
        return $items;
    }

    private function guardarDatosBasicos(array $validatedData, $pedido, $usuario, array $rolesDelUsuario)
    {
        try {
            // Preparar datos básicos para guardar
            $datosBasicos = [
                'pedido_produccion_id' => $pedido->id,
                'recibo_prenda_id' => $validatedData['recibo_prenda_id'] ?? null,
                'prenda_id' => $validatedData['prenda_id'] ?? null,
                'pedido_epp_id' => $validatedData['pedido_epp_id'] ?? null,
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'asesor' => $validatedData['asesor'] ?? null,
                'empresa' => $validatedData['empresa'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
                'pendientes' => $validatedData['pendientes'] ?? null,
                'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
                'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
                'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
                'usuario_bodega_id' => $usuario->id,
                'usuario_bodega_nombre' => $usuario->name,
            ];
            
            // Log para verificar que $datosBasicos está definida
            \Log::info('[DEBUG] $datosBasicos definida correctamente', [
                'datosBasicos_existe' => isset($datosBasicos),
                'datosBasicos_count' => count($datosBasicos),
                'linea' => __LINE__
            ]);
            
            // Procesar área
            $areaInput = $validatedData['area'] ?? null;
            $areaInput = is_string($areaInput) ? trim($areaInput) : $areaInput;
            
            if (empty($areaInput)) {
                $areaExistente = BodegaDetallesTalla::where('pedido_produccion_id', $pedido->id)
                    ->where('numero_pedido', $validatedData['numero_pedido'])
                    ->where('talla', $validatedData['talla'])
                    ->when(isset($validatedData['prenda_nombre']), fn($q) => $q->where('prenda_nombre', $validatedData['prenda_nombre']))
                    ->when(isset($validatedData['cantidad']), fn($q) => $q->where('cantidad', $validatedData['cantidad']))
                    ->value('area');
                $areaFinal = $areaExistente;
            } else {
                $areaFinal = $areaInput;
            }
            
            if (!empty($areaFinal)) {
                $datosBasicos['area'] = $areaFinal;
            }

            // Procesar estados
            $estadoBodegaGuardado = null;
            
            if (array_key_exists('estado_bodega', $validatedData) && $validatedData['estado_bodega'] !== null) {
                $datosBasicos['estado_bodega'] = $validatedData['estado_bodega'] ?: 'Pendiente';
                $estadoBodegaGuardado = $datosBasicos['estado_bodega'];
            }
            
            if (array_key_exists('estado', $validatedData) && $validatedData['estado'] !== null) {
                $datosBasicos['estado_bodega'] = $validatedData['estado'];
                $estadoBodegaGuardado = $datosBasicos['estado_bodega'];
                
                if (!empty($areaFinal)) {
                    if ($areaFinal === 'Costura') {
                        $datosBasicos['costura_estado'] = $validatedData['estado'];
                    } elseif ($areaFinal === 'EPP') {
                        $datosBasicos['epp_estado'] = $validatedData['estado'];
                    }
                }
            }
            
            if (array_key_exists('costura_estado', $validatedData)) {
                $datosBasicos['costura_estado'] = $validatedData['costura_estado'];
                if (!array_key_exists('estado', $validatedData)) {
                    $datosBasicos['estado_bodega'] = $validatedData['costura_estado'];
                    $estadoBodegaGuardado = $datosBasicos['estado_bodega'];
                }
            }
            
            if (array_key_exists('epp_estado', $validatedData)) {
                $datosBasicos['epp_estado'] = $validatedData['epp_estado'];
                if (!array_key_exists('estado', $validatedData)) {
                    $datosBasicos['estado_bodega'] = $validatedData['epp_estado'];
                    $estadoBodegaGuardado = $datosBasicos['estado_bodega'];
                }
            }
            
            // Log para ver qué datos se van a guardar
            \Log::info('[guardarDatosBasicos] Datos a guardar:', [
                'validatedData' => $validatedData,
                'datosBasicos' => $datosBasicos,
                'pedido_id' => $pedido->id,
                'prenda_id' => $datosBasicos['prenda_id'] ?? 'NO_INCLUIDO',
                'pedido_epp_id' => $datosBasicos['pedido_epp_id'] ?? 'NO_INCLUIDO'
            ]);
            
            // Buscar el registro existente
            $query = BodegaDetallesTalla::where([
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ]);
            
            \Log::info('[guardarDatosBasicos] Búsqueda de registro:', [
                'condiciones' => [
                    'pedido_produccion_id' => $pedido->id,
                    'numero_pedido' => $validatedData['numero_pedido'],
                    'talla' => $validatedData['talla'],
                    'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                    'cantidad' => $validatedData['cantidad'] ?? 0,
                ],
                'sql_generada' => $query->toSql(),
                'encontrados' => $query->count(),
                'registros_existentes' => $query->get(['id', 'prenda_id', 'pedido_epp_id', 'area'])->toArray()
            ]);
            
            // Priorizar registro con IDs correctos
            $registros = $query->get();
            $detalleExistente = null;
            
            if ($registros->count() > 0) {
                $detalleExistente = $registros->first(function($registro) use ($datosBasicos) {
                    if ($datosBasicos['prenda_id'] && $registro->prenda_id == $datosBasicos['prenda_id']) {
                        return true;
                    }
                    if ($datosBasicos['pedido_epp_id'] && $registro->pedido_epp_id == $datosBasicos['pedido_epp_id']) {
                        return true;
                    }
                    return $registro->prenda_id || $registro->pedido_epp_id;
                });
                
                if (!$detalleExistente) {
                    $detalleExistente = $registros->first();
                }
            }
            
            // Si no existe, crearlo
            if (!$detalleExistente) {
                $detalleExistente = new BodegaDetallesTalla();
                $detalleExistente->pedido_produccion_id = $pedido->id;
                $detalleExistente->numero_pedido = $validatedData['numero_pedido'];
                $detalleExistente->talla = $validatedData['talla'];
                $detalleExistente->prenda_nombre = $validatedData['prenda_nombre'] ?? null;
                $detalleExistente->cantidad = $validatedData['cantidad'] ?? 0;
            }
            
            // Actualizar todos los campos
            $detalleExistente->fill($datosBasicos);
            $detalleExistente->prenda_id = $datosBasicos['prenda_id'] ?? null;
            $detalleExistente->pedido_epp_id = $datosBasicos['pedido_epp_id'] ?? null;
            $detalleExistente->area = $datosBasicos['area'] ?? null;
            
            $detalleExistente->save();
            $detalleGuardado = $detalleExistente;
            
            // Log para ver qué se guardó realmente
            \Log::info('[guardarDatosBasicos] Registro guardado en BD:', [
                'detalle_id' => $detalleGuardado->id,
                'prenda_id_guardado' => $detalleGuardado->prenda_id,
                'pedido_epp_id_guardado' => $detalleGuardado->pedido_epp_id,
                'area_guardada' => $detalleGuardado->area,
                'es_nuevo' => $detalleGuardado->wasRecentlyCreated,
                'cambios' => $detalleGuardado->getChanges(),
                'datos_basicos_enviados' => $datosBasicos,
                'area_enviada' => $datosBasicos['area'] ?? 'NO_ENVIADA'
            ]);
            
            // NOTA: SINCRONIZACIÓN AUTOMÁTICA DESHABILITADA
            // El estado del pedido ahora se maneja exclusivamente por el método verificarYActualizarEstadoPedido()
            // que solo permite cambios a "Pendiente" desde bodega
            /*
            if ($estadoBodegaGuardado === 'Entregado') {
                $this->registrarEntregaPrenda([
                    'prenda_nombre' => $validatedData['prenda_nombre'] ?? '',
                    'talla' => $validatedData['talla'],
                    'cantidad' => $validatedData['cantidad'] ?? 0,
                    'observaciones_entrega' => 'Entregado desde bodega'
                ], $pedido->id);
                
                $this->sincronizarEstadoPedido($pedido->id, 'Entregado');
            } elseif ($estadoBodegaGuardado === 'Pendiente') {
                // Si se guarda con estado 'Pendiente', sincronizar el pedido a 'Pendiente'
                $this->sincronizarEstadoPedido($pedido->id, 'Pendiente');
            }
            */
            
            return $detalleGuardado;
            
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
     * Registrar la entrega de una prenda cuando un pedido cambia a estado 'Entregado'
     */
    public function registrarEntregaPrenda(array $datosPrenda, int $pedidoProduccionId): \App\Models\BodegaDetallesTalla
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
    
    /**
     * Sincronizar el estado del pedido cuando un item en bodega se pone en 'Pendiente' o 'Entregado'
     */
    private function sincronizarEstadoPedido(int $pedidoProduccionId, string $estado): void
    {
        try {
            $pedidoProduccion = \App\Models\PedidoProduccion::find($pedidoProduccionId);
            
            if ($pedidoProduccion) {
                // Solo actualizar si el estado actual es diferente y no es un estado final
                $estadosFinales = ['Entregado', 'Anulada'];
                if ($pedidoProduccion->estado !== $estado && !in_array($pedidoProduccion->estado, $estadosFinales)) {
                    
                    \Log::info('[SINCRONIZACIÓN] Actualizando estado del pedido', [
                        'pedido_produccion_id' => $pedidoProduccionId,
                        'numero_pedido' => $pedidoProduccion->numero_pedido,
                        'estado_anterior' => $pedidoProduccion->estado,
                        'estado_nuevo' => $estado
                    ]);
                    
                    $pedidoProduccion->estado = $estado;
                    $pedidoProduccion->save();
                    
                    \Log::info('[SINCRONIZACIÓN] Estado del pedido actualizado exitosamente', [
                        'pedido_produccion_id' => $pedidoProduccionId,
                        'numero_pedido' => $pedidoProduccion->numero_pedido,
                        'estado_final' => $estado
                    ]);
                } else {
                    \Log::info('[SINCRONIZACIÓN] No se actualiza el estado', [
                        'pedido_produccion_id' => $pedidoProduccionId,
                        'numero_pedido' => $pedidoProduccion->numero_pedido,
                        'estado_actual' => $pedidoProduccion->estado,
                        'estado_solicitado' => $estado,
                        'motivo' => in_array($pedidoProduccion->estado, $estadosFinales) ? 'Estado final' : 'Mismo estado'
                    ]);
                }
            } else {
                \Log::warning('[SINCRONIZACIÓN] No se encontró el pedido', [
                    'pedido_produccion_id' => $pedidoProduccionId
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('[SINCRONIZACIÓN] Error al sincronizar estado del pedido', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'estado' => $estado,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function guardarEstadoPorRol(array $validatedData, $pedido, $usuario, array $rolesDelUsuario)
    {
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $estadoNuevo = $validatedData['epp_estado'] ?? ($validatedData['estado_bodega'] ?? 'Pendiente');
            if (empty($estadoNuevo)) {
                $estadoNuevo = 'Pendiente';
            }
            return $this->guardarEstadoEpp($validatedData, $pedido, $usuario, $estadoNuevo);
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $estadoNuevo = $validatedData['costura_estado'] ?? ($validatedData['estado_bodega'] ?? 'Pendiente');
            if (empty($estadoNuevo)) {
                $estadoNuevo = 'Pendiente';
            }
            return $this->guardarEstadoCostura($validatedData, $pedido, $usuario, $estadoNuevo);
        }
        
        // Para bodeguero, ya se guardó en guardarDatosBasicos
        return null;
    }

    private function guardarEstadoEpp(array $validatedData, $pedido, $usuario, string $estadoNuevo)
    {
        $datosEpp = [
            'pedido_produccion_id' => $pedido->id,
            'numero_pedido' => $validatedData['numero_pedido'],
            'talla' => $validatedData['talla'],
            'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
            'asesor' => $validatedData['asesor'] ?? null,
            'empresa' => $validatedData['empresa'] ?? null,
            'cantidad' => $validatedData['cantidad'] ?? 0,
            'pendientes' => $validatedData['pendientes'] ?? null,
            'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
            'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
            'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
            'area' => $validatedData['area'] ?? 'EPP',
            'estado_bodega' => $estadoNuevo,
            'usuario_bodega_id' => $usuario->id,
            'usuario_bodega_nombre' => $usuario->name,
        ];

        // No tocar estado_bodega si no viene explícitamente en el request
        if (!array_key_exists('estado_bodega', $validatedData)) {
            unset($datosEpp['estado_bodega']);
        }
        
        $guardado = EppBodegaDetalle::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ],
            $datosEpp
        );

        // Sincronizar estado en tabla base: siempre epp_estado; estado_bodega solo si viene en el request
        $updateBase = [
            'epp_estado' => $validatedData['epp_estado'] ?? $estadoNuevo,
            'area' => $validatedData['area'] ?? 'EPP',
        ];
        if (array_key_exists('estado_bodega', $validatedData)) {
            $updateBase['estado_bodega'] = $estadoNuevo;
        }

        BodegaDetallesTalla::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ],
            $updateBase
        );

        return $guardado;
    }

    private function guardarEstadoCostura(array $validatedData, $pedido, $usuario, string $estadoNuevo)
    {
        $datosCostura = [
            'pedido_produccion_id' => $pedido->id,
            'numero_pedido' => $validatedData['numero_pedido'],
            'talla' => $validatedData['talla'],
            'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
            'asesor' => $validatedData['asesor'] ?? null,
            'empresa' => $validatedData['empresa'] ?? null,
            'cantidad' => $validatedData['cantidad'] ?? 0,
            'pendientes' => $validatedData['pendientes'] ?? null,
            'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
            'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
            'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
            'area' => $validatedData['area'] ?? 'Costura',
            'estado_bodega' => $estadoNuevo,
            'usuario_bodega_id' => $usuario->id,
            'usuario_bodega_nombre' => $usuario->name,
        ];

        // No tocar estado_bodega si no viene explícitamente en el request
        if (!array_key_exists('estado_bodega', $validatedData)) {
            unset($datosCostura['estado_bodega']);
        }
        
        $guardado = CosturaBodegaDetalle::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ],
            $datosCostura
        );

        // Sincronizar estado en tabla base: siempre costura_estado; estado_bodega solo si viene en el request
        $updateBase = [
            'costura_estado' => $validatedData['costura_estado'] ?? $estadoNuevo,
            'area' => $validatedData['area'] ?? 'Costura',
        ];
        if (array_key_exists('estado_bodega', $validatedData)) {
            $updateBase['estado_bodega'] = $estadoNuevo;
        }

        BodegaDetallesTalla::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
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
     * Filtrar items para despacho: mostrar todos los pedidos con estados permitidos
     * Estados permitidos: 'Pendiente','Entregado','En Ejecución','No iniciado','PENDIENTE_SUPERVISOR','PENDIENTE_INSUMOS','DEVUELTO_A_ASESORA'
     */
    private function filtrarItemsParaDespacho(array $items): array
    {
        $itemsFiltrados = [];
        $estadosPermitidos = ['Pendiente','Entregado','En Ejecución','No iniciado','PENDIENTE_SUPERVISOR','PENDIENTE_INSUMOS','DEVUELTO_A_ASESORA'];
        
        \Log::info('[DESPACHO-FILTRO] Inicio filtrado', [
            'items_totales' => count($items),
            'estados_permitidos' => $estadosPermitidos,
            'items_recibidos' => array_map(fn($item) => [
                'numero_pedido' => $item['numero_pedido'],
                'tipo' => $item['tipo'] ?? 'unknown',
                'tiene_tallas' => isset($item['tallas']) && is_array($item['tallas'])
            ], $items)
        ]);
        
        foreach ($items as $item) {
            // Obtener el estado del pedido desde pedidos_produccion
            $estadoPedido = null;
            try {
                $pedidoProduccion = \App\Models\PedidoProduccion::where('numero_pedido', $item['numero_pedido'])->first();
                if ($pedidoProduccion) {
                    $estadoPedido = $pedidoProduccion->estado;
                }
            } catch (\Exception $e) {
                \Log::warning('[DESPACHO-FILTRO] Error al obtener estado del pedido', [
                    'numero_pedido' => $item['numero_pedido'],
                    'error' => $e->getMessage()
                ]);
            }
            
            \Log::info('[DESPACHO-FILTRO] Verificando item', [
                'numero_pedido' => $item['numero_pedido'],
                'estado_pedido' => $estadoPedido,
                'estado_permitido' => in_array($estadoPedido, $estadosPermitidos)
            ]);
            
            // Mostrar el item si el estado del pedido está en la lista de permitidos
            if ($estadoPedido && in_array($estadoPedido, $estadosPermitidos)) {
                $itemsFiltrados[] = $item;
                \Log::info('[DESPACHO-FILTRO] Item agregado a filtrados', [
                    'numero_pedido' => $item['numero_pedido'],
                    'estado' => $estadoPedido
                ]);
            } else {
                \Log::warning('[DESPACHO-FILTRO] Item no agregado - estado no permitido', [
                    'numero_pedido' => $item['numero_pedido'],
                    'estado_pedido' => $estadoPedido
                ]);
            }
        }
        
        \Log::info('[DESPACHO-FILTRO] Resultado final', [
            'items_originales' => count($items),
            'items_filtrados' => count($itemsFiltrados),
            'items_filtrados_pedidos' => array_map(fn($item) => [
                'numero_pedido' => $item['numero_pedido'],
                'tipo' => $item['tipo'] ?? 'unknown'
            ], $itemsFiltrados)
        ]);
        
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
                \Log::info('[BodegaPedidoService] No hay ítems en bodega para el pedido', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido
                ]);
                return;
            }
            
            // Contar estados de los ítems
            $estadosCount = $itemsBodega->groupBy('estado_bodega')->map->count();
            
            \Log::info('[BodegaPedidoService] Análisis de estados del pedido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_actual' => $pedido->estado,
                'estados_count' => $estadosCount->toArray(),
                'total_items' => $itemsBodega->count(),
                'hay_pendientes' => isset($estadosCount['Pendiente']) && $estadosCount['Pendiente'] > 0
            ]);
            
            // Determinar el nuevo estado del pedido - SOLO cambiar a "Pendiente"
            $nuevoEstado = $pedido->estado; // Mantener el actual por defecto
            
            // Únicamente cambiar a "Pendiente" si hay ítems pendientes
            if (isset($estadosCount['Pendiente']) && $estadosCount['Pendiente'] > 0) {
                $nuevoEstado = 'Pendiente';
            }
            // IMPORTANTE: NUNCA cambiar a "Entregado" o "Anulada" desde bodega
            // Esos cambios solo deben manejarse desde el módulo de despacho
            // Para otros estados, mantener el estado actual del pedido
            
            // Actualizar el estado si cambió
            if ($nuevoEstado !== $pedido->estado) {
                $estadoAnterior = $pedido->estado;
                $pedido->update([
                    'estado' => $nuevoEstado,
                    'updated_at' => now()
                ]);
                
                \Log::info('[BodegaPedidoService] Estado del pedido actualizado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $nuevoEstado,
                    'motivo' => 'Cambio desde bodega - Solo permite Pendiente'
                ]);
            } else {
                \Log::info('[BodegaPedidoService] No se actualiza el estado - sin cambios', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_actual' => $pedido->estado,
                    'nuevo_estado' => $nuevoEstado
                ]);
            }
            
            // SIEMPRE disparar evento de actualización cuando hay cambios en bodega
            // para que el frontend de despacho se actualice en tiempo real
            \Log::info('[BodegaPedidoService] Disparando evento PedidoActualizado (siempre)', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_actual' => $pedido->estado,
                'items_pendientes' => isset($estadosCount['Pendiente']) && $estadosCount['Pendiente'] > 0,
                'total_items' => $itemsBodega->count(),
                'estados_count' => $estadosCount->toArray()
            ]);
            
            // Incluir información adicional sobre los cambios en bodega
            $changedFields = [
                'estado' => $pedido->estado,
                'bodega_items_count' => $itemsBodega->count(),
                'bodega_pendientes_count' => $estadosCount['Pendiente'] ?? 0,
                'bodega_entregados_count' => $estadosCount['Entregado'] ?? 0,
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
