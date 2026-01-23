<?php

namespace App\Application\Services\Asesores;

use App\Models\Pedidos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class ObtenerPedidosService
{
    /**
     * Obtener pedidos del asesor con filtros y bÃºsqueda
     * Soporta filtrado por tipo (logo, prendas, todos)
     * 
     * @param string|null $tipo Tipo de pedido: 'logo', 'prendas', null (todos)
     * @param array $filtros Filtros: ['estado', 'search']
     * @param int $perPage Resultados por pÃ¡gina
     * @return LengthAwarePaginator
     */
    public function obtener(?string $tipo = null, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        \Log::info(' [OBTENER PEDIDOS] Iniciando bÃºsqueda', [
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

        // PRENDAS o TODOS: Mostrar Pedidos
        return $this->obtenerPedidosProduccion($userId, $tipo, $filtros, $perPage);
    }

    /**
     * @deprecated La tabla logo_pedidos ha sido eliminada
     * Obtener LogoPedidos del asesor
     */
    private function obtenerLogoPedidos(string $userName, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        \Log::warning('[LOGO] Funcionalidad LogoPedido removida - retornando paginaciÃ³n vacÃ­a');
        
        // Retornar paginaciÃ³n vacÃ­a
        return Pedidos::where('id', '=', null)
            ->paginate($perPage);
    }

    /**
     * Obtener Pedidos del asesor
     */
    private function obtenerPedidosProduccion(int $userId, ?string $tipo, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Pedidos::where('asesor_id', $userId)
            ->with([
                'prendas' => function ($q) {
                    $q->with(['procesos' => function ($q2) {
                        $q2->orderBy('created_at', 'desc');
                    }]);
                },
                'asesora'
            ]);

        // Si es solo PRENDAS, no hace falta filtrar por logoPedidos ya que la tabla no existe
        // if ($tipo === 'prendas') {
        //     $query->whereDoesntHave('logoPedidos');
        // }

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
     * Aplicar filtros al query de Pedidos
     */
    private function aplicarFiltros($query, array $filtros): void
    {
        if (empty($filtros['estado'])) {
            return;
        }

        $estado = $filtros['estado'];

        // Si el estado es "No iniciado", filtrar por pendientes de aprobaciÃ³n
        if ($estado === 'No iniciado') {
            $query->where('estado', 'No iniciado')
                ->whereNull('aprobado_por_supervisor_en');
        }
        // Si el estado es "En EjecuciÃ³n", mostrar "No iniciado" y "En EjecuciÃ³n"
        elseif ($estado === 'En EjecuciÃ³n') {
            $query->whereIn('estado', ['No iniciado', 'En EjecuciÃ³n']);
        }
        // Otros estados exactos
        else {
            $query->where('estado', $estado);
        }
    }

    /**
     * Obtener bÃºsqueda aplicada
     */
    public function aplicarBusqueda($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('numero_pedido', 'LIKE', "%{$search}%")
                ->orWhere('cliente', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Obtener estados Ãºnicos disponibles
     */
    public function obtenerEstados(): array
    {
        \Log::info(' [ESTADOS] Obteniendo estados Ãºnicos');

        $estados = Pedidos::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado')
            ->toArray();

        \Log::info(' [ESTADOS] Encontrados', ['count' => count($estados), 'estados' => $estados]);

        return $estados;
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
            'pedidos_dia' => Pedidos::where('asesor_id', $userId)
                ->whereDate('created_at', $inicioHoy)
                ->count(),
            
            'pedidos_mes' => Pedidos::where('asesor_id', $userId)
                ->whereBetween('created_at', [$inicioMes, $ahora])
                ->count(),
            
            'pedidos_anio' => Pedidos::where('asesor_id', $userId)
                ->whereBetween('created_at', [$inicioAÃ±o, $ahora])
                ->count(),
            
            'pedidos_pendientes' => Pedidos::where('asesor_id', $userId)
                ->where('estado', 'No iniciado')
                ->whereNull('aprobado_por_supervisor_en')
                ->count(),
        ];

        \Log::info('ðŸ“ˆ [ESTADÃSTICAS] Calculadas', $estadisticas);

        return $estadisticas;
    }
}

