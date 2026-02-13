<?php

namespace App\Application\Bodega\Services;

use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use App\Models\PedidoAuditoria;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * Obtener detalles de un pedido específico
     */
    public function obtenerDetallePedido(int $pedidoId): array
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
        
        // Obtener todos los recibos del pedido
        $recibos = $this->bodegaRepository->obtenerRecibosPedido($numeroPedido, $estadosPermitidos);
        
        // Procesar ítems
        $items = $this->procesarItemsPedido($recibos, $rolesDelUsuario, $areasPermitidas);
        
        // Calcular rowspans
        $items = $this->calcularRowspans($items);
        
        // Obtener info del pedido
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
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
        return ['ENTREGADO', 'EN EJECUCIÓN', 'NO INICIADO', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'];
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
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $prendaEnriquecida,
            'talla' => $talla,
            'cantidad_total' => $cantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $bodegaData['pendientes'] ?? null,
            'fecha_entrega' => $bodegaData['fecha_entrega'],
            'fecha_pedido' => $bodegaData['fecha_pedido'],
            'estado_bodega' => $bodegaData['estado'],
            'area' => $bodegaData['area'],
            'usuario_bodega_nombre' => $bodegaData['usuario_nombre'],
            'bodega_id' => $bodegaData['id'],
        ];
    }

    private function crearItemEpp(array $eppEnriquecido, $recibo, array $rolesDelUsuario, array $areasPermitidas, $pedidoProduccion): array
    {
        $eppNombre = $eppEnriquecido['nombre'] ?? 'EPP';
        $eppCantidad = $eppEnriquecido['cantidad'] ?? 0;
        $eppId = md5($recibo->numero_pedido . '|' . $eppNombre . '|' . $eppCantidad);
        
        // Obtener datos de bodega
        $bodegaData = $this->obtenerDatosBodega($recibo->numero_pedido, $eppId, null, $eppCantidad, $rolesDelUsuario);
        
        // Obtener asesor de forma segura
        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }
        
        // Obtener empresa
        $empresa = $recibo->cliente ?? 'N/A';
        
        \Log::debug('[crearItemEpp] Datos', [
            'numero_pedido' => $recibo->numero_pedido,
            'asesor_id' => $recibo->asesor_id,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'cliente' => $recibo->cliente
        ]);
        
        return [
            'id' => $recibo->id,
            'tipo' => 'epp',
            'numero_pedido' => $recibo->numero_pedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $eppEnriquecido,
            'talla' => $eppId,
            'cantidad_total' => $eppCantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $bodegaData['pendientes'] ?? null,
            'fecha_entrega' => $bodegaData['fecha_entrega'],
            'fecha_pedido' => $bodegaData['fecha_pedido'],
            'estado_bodega' => $bodegaData['estado'],
            'area' => $bodegaData['area'],
            'usuario_bodega_nombre' => $bodegaData['usuario_nombre'],
            'bodega_id' => $bodegaData['id'],
        ];
    }

    private function obtenerDatosBodega(string $numeroPedido, string $talla, ?string $prendaNombre, int $cantidad, array $rolesDelUsuario): array
    {
        // Obtener datos base
        $bodegaDataBase = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('talla', $talla)
            ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
            ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
            ->first();
        
        // Obtener estado específico del rol
        $bodegaDataEstado = null;
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $bodegaDataEstado = EppBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
                ->first();
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $bodegaDataEstado = CosturaBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->when($prendaNombre, fn($q) => $q->where('prenda_nombre', $prendaNombre))
                ->when($cantidad, fn($q) => $q->where('cantidad', $cantidad))
                ->first();
        }
        
        // Determinar qué datos usar
        $datosFinales = in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)
            ? $bodegaDataEstado
            : $bodegaDataBase;
        
        return [
            'id' => $datosFinales?->id,
            'estado' => $datosFinales?->estado_bodega ?? $bodegaDataBase?->estado_bodega,
            'area' => $datosFinales?->area ?? $bodegaDataBase?->area,
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
        $datosBasicos = [
            'pedido_produccion_id' => $pedido->id,
            'recibo_prenda_id' => $validatedData['recibo_prenda_id'] ?? null,
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
        
        // Solo agregar estado_bodega si es Bodeguero
        if (!in_array('EPP-Bodega', $rolesDelUsuario) && !in_array('Costura-Bodega', $rolesDelUsuario)) {
            $datosBasicos['estado_bodega'] = $validatedData['estado_bodega'] ?? 'Pendiente';
            $datosBasicos['area'] = $validatedData['area'] ?? null;
        }
        
        return BodegaDetallesTalla::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ],
            $datosBasicos
        );
    }

    private function guardarEstadoPorRol(array $validatedData, $pedido, $usuario, array $rolesDelUsuario)
    {
        $estadoNuevo = $validatedData['estado_bodega'] ?? 'Pendiente';
        if (empty($estadoNuevo)) {
            $estadoNuevo = 'Pendiente';
        }
        
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            return $this->guardarEstadoEpp($validatedData, $pedido, $usuario, $estadoNuevo);
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
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
        
        return EppBodegaDetalle::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ],
            $datosEpp
        );
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
        
        return CosturaBodegaDetalle::updateOrCreate(
            [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
                'cantidad' => $validatedData['cantidad'] ?? 0,
            ],
            $datosCostura
        );
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
}
