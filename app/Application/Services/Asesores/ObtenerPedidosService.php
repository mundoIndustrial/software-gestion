<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class ObtenerPedidosService
{
    /**
     * Obtener pedidos del asesor con filtros y búsqueda
     * Soporta filtrado por tipo (logo, prendas, todos)
     * 
     * @param string|null $tipo Tipo de pedido: 'logo', 'prendas', null (todos)
     * @param array $filtros Filtros: ['estado', 'search']
     * @param int $perPage Resultados por página
     * @return LengthAwarePaginator
     */
    public function obtener(?string $tipo = null, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        \Log::info(' [OBTENER PEDIDOS] Iniciando búsqueda', [
            'tipo' => $tipo,
            'filtros' => $filtros,
            'por_pagina' => $perPage
        ]);

        $userId = Auth::id();
        $userName = Auth::user()->name ?? 'Sin nombre';

        // Si es LOGO, mostrar LogoPedidos directamente
        if ($tipo === 'logo') {
            return $this->obtenerLogoPedidos($userName, $filtros, $perPage);
        }

        // PRENDAS o TODOS: Mostrar PedidoProduccion
        return $this->obtenerPedidosProduccion($userId, $tipo, $filtros, $perPage);
    }

    /**
     * Obtener LogoPedidos del asesor
     */
    private function obtenerLogoPedidos(string $userName, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        \Log::info('[LOGO] Filtrando logo_pedidos para usuario: ' . $userName);

        $query = LogoPedido::query()
            ->where(function ($q) use ($userName) {
                $q->where('asesora', $userName)
                    ->orWhereNull('asesora'); // Incluir pedidos sin asesora asignada
            })
            ->with('procesos');

        // Aplicar filtro de estado si existe
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        // Aplicar búsqueda
        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'LIKE', "%{$search}%")
                    ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }

        \Log::info('[LOGO] Filtros aplicados', [
            'estado' => $filtros['estado'] ?? 'ninguno',
            'search' => $filtros['search'] ?? 'ninguno'
        ]);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Obtener PedidoProduccion del asesor
     */
    private function obtenerPedidosProduccion(int $userId, ?string $tipo, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PedidoProduccion::where('asesor_id', $userId)
            ->with([
                'prendas' => function ($q) {
                    $q->with(['procesos' => function ($q2) {
                        $q2->orderBy('created_at', 'desc');
                    }]);
                },
                'asesora',
                'logoPedidos'
            ]);

        // Si es solo PRENDAS, excluir los que tienen logo
        if ($tipo === 'prendas') {
            $query->whereDoesntHave('logoPedidos');
        }

        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);

        \Log::info('[PRENDAS] Filtros aplicados', [
            'tipo' => $tipo ?? 'todos',
            'estado' => $filtros['estado'] ?? 'ninguno',
            'search' => $filtros['search'] ?? 'ninguno'
        ]);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Aplicar filtros al query de PedidoProduccion
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
     * Obtener estados únicos disponibles
     */
    public function obtenerEstados(): array
    {
        \Log::info(' [ESTADOS] Obteniendo estados únicos');

        $estados = PedidoProduccion::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado')
            ->toArray();

        \Log::info(' [ESTADOS] Encontrados', ['count' => count($estados), 'estados' => $estados]);

        return $estados;
    }

    /**
     * Obtener estadísticas de pedidos para dashboard
     */
    public function obtenerEstadisticas(): array
    {
        $userId = Auth::id();

        \Log::info('📈 [ESTADÍSTICAS] Calculando para usuario: ' . $userId);

        $ahora = now();
        $inicioMes = $ahora->clone()->startOfMonth();
        $inicioAño = $ahora->clone()->startOfYear();
        $inicioHoy = $ahora->clone()->startOfDay();

        $estadisticas = [
            'pedidos_dia' => PedidoProduccion::where('asesor_id', $userId)
                ->whereDate('created_at', $inicioHoy)
                ->count(),
            
            'pedidos_mes' => PedidoProduccion::where('asesor_id', $userId)
                ->whereBetween('created_at', [$inicioMes, $ahora])
                ->count(),
            
            'pedidos_anio' => PedidoProduccion::where('asesor_id', $userId)
                ->whereBetween('created_at', [$inicioAño, $ahora])
                ->count(),
            
            'pedidos_pendientes' => PedidoProduccion::where('asesor_id', $userId)
                ->where('estado', 'No iniciado')
                ->whereNull('aprobado_por_supervisor_en')
                ->count(),
        ];

        \Log::info('📈 [ESTADÍSTICAS] Calculadas', $estadisticas);

        return $estadisticas;
    }
}
