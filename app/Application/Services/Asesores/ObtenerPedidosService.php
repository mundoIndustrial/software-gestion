<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class ObtenerPedidosService
{
    /**
     * Obtener pedidos del asesor con filtros y búsqueda - OPTIMIZADO
     * Soporta filtrado por tipo (logo, prendas, todos)
     * 
     *  OPTIMIZACIONES:
     * - Select solo columnas necesarias
     * - Limit en procesos (máximo 3)
     * - Cache en estados
     * - Sin logs en producción
     * 
     * @param string|null $tipo Tipo de pedido: 'logo', 'prendas', null (todos)
     * @param array $filtros Filtros: ['estado', 'search']
     * @param int $perPage Resultados por página
     * @return LengthAwarePaginator
     */
    public function obtener(?string $tipo = null, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        if (app()->isLocal()) {
            \Log::info('[OBTENER PEDIDOS] Iniciando búsqueda', [
                'tipo' => $tipo,
                'filtros' => $filtros,
                'por_pagina' => $perPage
            ]);
        }

        $userId = Auth::id();
        $userName = Auth::user()->name ?? 'Sin nombre';

        // Si es LOGO, mostrar LogoPedidos directamente
        if ($tipo === 'logo') {
            return $this->obtenerLogoPedidos($userName, $filtros, $perPage);
        }

        // PRENDAS o TODOS: Mostrar Pedidos
        return $this->obtenerPedidosProduccion($userId, $tipo, $filtros, $perPage);
    }

    /**
     * @deprecated La tabla logo_pedidos ha sido eliminada
     * Obtener LogoPedidos del asesor
     */
    private function obtenerLogoPedidos(string $userName, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        if (app()->isLocal()) {
            \Log::warning('[LOGO] Funcionalidad LogoPedido removida - retornando paginación vacía');
        }
        
        // Retornar paginación vacía
        return PedidoProduccion::where('id', '=', null)
            ->paginate($perPage);
    }

    /**
     * Obtener Pedidos del asesor - OPTIMIZADO
     * 
     *  Cambios:
     * - Select solo columnas necesarias
     * - Limit 3 en procesos para evitar N+1
     * - Cache en estados
     */
    private function obtenerPedidosProduccion(int $userId, ?string $tipo, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        // 🔥 OPTIMIZACIÓN 1: Select solo columnas necesarias
        $query = PedidoProduccion::where('asesor_id', $userId)
            ->select([
                'id',
                'numero_pedido',
                'cliente',
                'estado',
                'forma_de_pago',
                'novedades',
                'created_at',
                'asesor_id'
            ])
            // 🔥 OPTIMIZACIÓN 2: With relaciones optimizadas
            ->with([
                'prendas' => function ($q) {
                    // Select solo columnas necesarias de prendas
                    $q->select([
                        'id',
                        'pedido_produccion_id',
                        'nombre_prenda',
                        'cantidad',
                        'descripcion'
                    ])
                    // 🔥 CRÍTICO: Eager load procesos CON LIMIT
                    ->with(['procesos' => function ($q2) {
                        $q2->select([
                            'id',
                            'prenda_pedido_id',
                            'tipo_proceso',
                            'created_at'
                        ])
                        ->limit(3)  // ⚡ MÁXIMO 3 procesos por prenda
                        ->orderBy('created_at', 'desc');
                    }]);
                },
                'asesora' => function ($q) {
                    $q->select(['id', 'name', 'email']);
                }
            ]);

        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);

        if (app()->isLocal()) {
            \Log::info('[PRENDAS] Filtros aplicados', [
                'tipo' => $tipo ?? 'todos',
                'estado' => $filtros['estado'] ?? 'ninguno',
                'search' => $filtros['search'] ?? 'ninguno'
            ]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Aplicar filtros al query de Pedidos
     */
    private function aplicarFiltros($query, array $filtros): void
    {
        if (empty($filtros['estado'])) {
            return;
        }

        $estado = $filtros['estado'];

        // Si el estado es "No iniciado", filtrar por pendientes de aprobación
        if ($estado === 'No iniciado') {
            $query->where('estado', 'No iniciado')
                ->whereNull('aprobado_por_supervisor_en');
        }
        // Si el estado es "En Ejecución", mostrar "No iniciado" y "En Ejecución"
        elseif ($estado === 'En Ejecución') {
            $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
        }
        // Otros estados exactos
        else {
            $query->where('estado', $estado);
        }
    }

    /**
     * Obtener búsqueda aplicada
     */
    public function aplicarBusqueda($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('numero_pedido', 'LIKE', "%{$search}%")
                ->orWhere('cliente', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Obtener estados únicos disponibles - CON CACHE
     * 
     *  OPTIMIZACIÓN: Cache por 1 hora para evitar full table scan
     */
    public function obtenerEstados(): array
    {
        // 🔥 Cache por 1 hora (3600 segundos)
        return Cache::remember('pedidos_estados_list', 3600, function () {
            if (app()->isLocal()) {
                \Log::info('[ESTADOS] Obteniendo estados únicos');
            }

            $estados = PedidoProduccion::select('estado')
                ->whereNotNull('estado')
                ->distinct()
                ->pluck('estado')
                ->toArray();

            if (app()->isLocal()) {
                \Log::info('[ESTADOS] Encontrados', ['count' => count($estados), 'estados' => $estados]);
            }

            return $estados;
        });
    }

    /**
     * Obtener estadÃ­sticas de pedidos para dashboard
     */
    public function obtenerEstadisticas(): array
    {
        $userId = Auth::id();

        \Log::info('ðŸ“ˆ [ESTADÃSTICAS] Calculando para usuario: ' . $userId);

        $ahora = now();
        $inicioMes = $ahora->clone()->startOfMonth();
        $inicioAÃ±o = $ahora->clone()->startOfYear();
        $inicioHoy = $ahora->clone()->startOfDay();

        $estadisticas = [
            'pedidos_dia' => PedidoProduccion::where('asesor_id', $userId)
                ->whereDate('created_at', $inicioHoy)
                ->count(),
            
            'pedidos_mes' => PedidoProduccion::where('asesor_id', $userId)
                ->whereBetween('created_at', [$inicioMes, $ahora])
                ->count(),
            
            'pedidos_anio' => PedidoProduccion::where('asesor_id', $userId)
                ->whereBetween('created_at', [$inicioAÃ±o, $ahora])
                ->count(),
            
            'pedidos_pendientes' => PedidoProduccion::where('asesor_id', $userId)
                ->where('estado', 'No iniciado')
                ->whereNull('aprobado_por_supervisor_en')
                ->count(),
        ];

        \Log::info('ðŸ“ˆ [ESTADÃSTICAS] Calculadas', $estadisticas);

        return $estadisticas;
    }
}

