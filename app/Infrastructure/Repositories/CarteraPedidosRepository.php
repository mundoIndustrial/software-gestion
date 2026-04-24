<?php

namespace App\Infrastructure\Repositories;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\ProcesoPrenda;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarteraPedidosRepository
{
    /**
     * Obtener pedidos pendientes de cartera con filtros
     */
    public function obtenerPedidosPendientes(
        int $page,
        int $perPage,
        string $search = '',
        string $cliente = '',
        string $fechaDesde = '',
        string $fechaHasta = '',
        string $sortBy = 'fecha',
        string $sortOrder = 'desc'
    ): array {
        $estadosPendientes = ['pendiente_cartera'];
        $estadosExcluidos = ['Entregado', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'];

        $query = PedidoProduccion::whereIn('estado', $estadosPendientes)
            ->whereNotIn('estado', $estadosExcluidos)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->whereHas('prendas');

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'like', $search)
                  ->orWhere('numero_pedido', 'like', $search)
                  ->orWhere('id', 'like', $search);
            });
        }

        if (!empty($cliente)) {
            $query->where('cliente', 'like', '%' . $cliente . '%');
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('created_at', $sortOrder);
        }

        // Obtener TODOS sin paginar primero para poder filtrar
        $todosPedidos = $query->get();
        
        // Aplicar filtro de prendas sin procesos
        $pedidosFiltrados = $this->filtrarPedidosSinProcesosProductivos($todosPedidos);
        
        // Ahora paginar los resultados filtrados
        $totalFiltrado = $pedidosFiltrados->count();
        $pedidosPaginados = $pedidosFiltrados
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return [
            'pedidos' => $pedidosPaginados,
            'total' => $totalFiltrado,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($totalFiltrado / $perPage)
        ];
    }

    /**
     * Obtener pedidos aprobados por cartera (PENDIENTE_SUPERVISOR)
     */
    public function obtenerPedidosAprobados(
        int $page,
        int $perPage,
        string $search = '',
        string $cliente = '',
        string $fechaDesde = '',
        string $fechaHasta = '',
        string $sortBy = 'fecha',
        string $sortOrder = 'desc'
    ): array {
        $estadosPermitidos = ['Pendiente', 'Entregado', 'En Ejecución', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'];

        $query = PedidoProduccion::whereNotNull('aprobado_por_cartera_en')
            ->whereIn('estado', $estadosPermitidos)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->whereHas('prendas');

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'like', $search)
                  ->orWhere('numero_pedido', 'like', $search)
                  ->orWhere('id', 'like', $search);
            });
        }

        if (!empty($cliente)) {
            $query->where('cliente', 'like', '%' . $cliente . '%');
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('aprobado_por_cartera_en', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('aprobado_por_cartera_en', '<=', $fechaHasta);
        }

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('aprobado_por_cartera_en', $sortOrder);
        }

        // Obtener TODOS sin paginar primero para poder filtrar
        $todosPedidos = $query->get();
        
        // Aplicar filtro de prendas sin procesos
        $pedidosFiltrados = $this->filtrarPedidosSinProcesosProductivos($todosPedidos);
        
        // Ahora paginar los resultados filtrados
        $totalFiltrado = $pedidosFiltrados->count();
        $pedidosPaginados = $pedidosFiltrados
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return [
            'pedidos' => $pedidosPaginados,
            'total' => $totalFiltrado,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($totalFiltrado / $perPage)
        ];
    }

    /**
     * Obtener pedidos rechazados por cartera (RECHAZADO_CARTERA)
     */
    public function obtenerPedidosRechazados(
        int $page,
        int $perPage,
        string $search = '',
        string $cliente = '',
        string $fechaDesde = '',
        string $fechaHasta = '',
        string $sortBy = 'fecha',
        string $sortOrder = 'desc'
    ): array {
        $query = PedidoProduccion::whereIn('estado', ['RECHAZADO_CARTERA'])
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->whereHas('prendas');

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'like', $search)
                  ->orWhere('numero_pedido', 'like', $search)
                  ->orWhere('id', 'like', $search);
            });
        }

        if (!empty($cliente)) {
            $query->where('cliente', 'like', '%' . $cliente . '%');
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('rechazado_por_cartera_en', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('rechazado_por_cartera_en', '<=', $fechaHasta);
        }

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('rechazado_por_cartera_en', $sortOrder);
        }

        // Obtener TODOS sin paginar primero para poder filtrar
        $todosPedidos = $query->get();
        
        // Aplicar filtro de prendas sin procesos
        $pedidosFiltrados = $this->filtrarPedidosSinProcesosProductivos($todosPedidos);
        
        // Ahora paginar los resultados filtrados
        $totalFiltrado = $pedidosFiltrados->count();
        $pedidosPaginados = $pedidosFiltrados
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return [
            'pedidos' => $pedidosPaginados,
            'total' => $totalFiltrado,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($totalFiltrado / $perPage)
        ];
    }

    /**
     * Obtener pedidos anulados (Anulada)
     */
    public function obtenerPedidosAnulados(
        int $page,
        int $perPage,
        string $search = '',
        string $cliente = '',
        string $fechaDesde = '',
        string $fechaHasta = '',
        string $sortBy = 'fecha',
        string $sortOrder = 'desc'
    ): array {
        $query = PedidoProduccion::whereIn('estado', ['Anulada'])
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->whereHas('prendas');

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'like', $search)
                  ->orWhere('numero_pedido', 'like', $search)
                  ->orWhere('id', 'like', $search);
            });
        }

        if (!empty($cliente)) {
            $query->where('cliente', 'like', '%' . $cliente . '%');
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('updated_at', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('updated_at', '<=', $fechaHasta);
        }

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('updated_at', $sortOrder);
        }

        // Obtener TODOS sin paginar primero para poder filtrar
        $todosPedidos = $query->get();
        
        // Aplicar filtro de prendas sin procesos
        $pedidosFiltrados = $this->filtrarPedidosSinProcesosProductivos($todosPedidos);
        
        // Ahora paginar los resultados filtrados
        $totalFiltrado = $pedidosFiltrados->count();
        $pedidosPaginados = $pedidosFiltrados
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return [
            'pedidos' => $pedidosPaginados,
            'total' => $totalFiltrado,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($totalFiltrado / $perPage)
        ];
    }

    /**
     * Obtener opciones de filtro (clientes y fechas)
     */
    public function obtenerOpcionesFiltro(): array
    {
        $estadosPendientes = ['pendiente_cartera'];
        $estadosExcluidos = ['Entregado', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'];

        $clientes = PedidoProduccion::whereIn('estado', $estadosPendientes)
            ->whereNotIn('estado', $estadosExcluidos)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->select('cliente')
            ->distinct()
            ->orderBy('cliente')
            ->pluck('cliente')
            ->filter()
            ->values()
            ->toArray();

        $fechas = PedidoProduccion::whereIn('estado', $estadosPendientes)
            ->whereNotIn('estado', $estadosExcluidos)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->selectRaw('DATE(created_at) as fecha')
            ->distinct()
            ->orderBy('fecha', 'desc')
            ->pluck('fecha')
            ->filter()
            ->values()
            ->toArray();

        return [
            'clientes' => $clientes,
            'fechas' => $fechas
        ];
    }

    /**
     * Obtener un pedido por ID
     */
    public function obtenerPedido(int $id): ?PedidoProduccion
    {
        return PedidoProduccion::find($id);
    }

    /**
     * Aprobar pedido (cambiar estado a PENDIENTE_SUPERVISOR)
     */
    public function aprobarPedido(
        PedidoProduccion $pedido,
        ?int $usuarioId = null
    ): PedidoProduccion {
        $pedido->update([
            'estado' => 'PENDIENTE_SUPERVISOR',
            'aprobado_por_usuario_cartera' => $usuarioId,
            'aprobado_por_cartera_en' => now(),
        ]);

        return $pedido->fresh();
    }

    /**
     * Rechazar pedido
     */
    public function rechazarPedido(
        PedidoProduccion $pedido,
        string $motivo,
        string $novedades,
        ?int $usuarioId = null
    ): PedidoProduccion {
        $pedido->update([
            'estado' => 'RECHAZADO_CARTERA',
            'motivo_rechazo_cartera' => $motivo,
            'rechazado_por_usuario_cartera' => $usuarioId,
            'rechazado_por_cartera_en' => now(),
            'novedades' => $novedades,
        ]);

        return $pedido->fresh();
    }

    /**
     * Generar o actualizar consecutivo COSTURA-BODEGA
     */
    public function generarConsecutivoCosturaBodega(PedidoProduccion $pedido): int
    {
        // Obtener el consecutivo actual con lock
        $consecutivoRecibo = DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'COSTURA-BODEGA')
            ->lockForUpdate()
            ->first();

        if (!$consecutivoRecibo) {
            throw new \Exception('No existe consecutivo COSTURA-BODEGA en consecutivos_recibos');
        }

        // Incrementar
        $nuevoConsecutivo = $consecutivoRecibo->consecutivo_actual + 1;

        // Actualizar consecutivo global
        DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'COSTURA-BODEGA')
            ->update([
                'consecutivo_actual' => $nuevoConsecutivo,
                'updated_at' => now()
            ]);

        // Verificar si existe registro para este pedido
        $existeRegistro = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedido->id)
            ->where('tipo_recibo', 'COSTURA-BODEGA')
            ->first();

        if ($existeRegistro) {
            DB::table('consecutivos_recibos_pedidos')
                ->where('id', $existeRegistro->id)
                ->update([
                    'consecutivo_actual' => $nuevoConsecutivo,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('consecutivos_recibos_pedidos')->insert([
                'pedido_produccion_id' => $pedido->id,
                'tipo_recibo' => 'COSTURA-BODEGA',
                'consecutivo_actual' => $nuevoConsecutivo,
                'consecutivo_inicial' => $nuevoConsecutivo,
                'prenda_id' => null,
                'activo' => 1,
                'notas' => 'Generado automáticamente cuando CARTERA aprobó el pedido',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $nuevoConsecutivo;
    }

    /**
     * Filtrar pedidos que tengan SOLO prendas de bodega sin procesos o SOLO EPPs
     * 
     * Excluir si:
     * - Solo tiene EPPs
     * - Solo tiene prendas de bodega SIN procesos
     * - Tiene EPPs + prendas de bodega sin procesos (sin prendas normales)
     * 
     * Mantener si:
     * - Tiene al menos una prenda normal (de_bodega = false)
     * - Tiene al menos una prenda de bodega CON procesos
     * 
     * @param \Illuminate\Support\Collection $pedidos Collection de PedidoProduccion
     * @return \Illuminate\Support\Collection Pedidos filtrados
     */
    private function filtrarPedidosSinProcesosProductivos($pedidos)
    {
        $pedidosAExcluir = collect();

        foreach ($pedidos as $pedido) {
            $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)
                ->whereNull('deleted_at')
                ->get(['id', 'nombre_prenda', 'de_bodega']);

            // Sin prendas productivas, se interpreta como pedido solo EPP o inconsistente
            if ($prendas->isEmpty()) {
                $pedidosAExcluir->push($pedido->id);

                Log::info('[CARTERA-FILTRO] Pedido EXCLUIDO (solo EPPs o sin prendas)', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'pedido_id' => $pedido->id,
                ]);
                continue;
            }

            // Solo es bodega si viene explicitamente como 1/true/'1'
            $prendasNormales = $prendas->filter(function ($prenda) {
                return !in_array($prenda->de_bodega, [1, true, '1'], true);
            })->count();

            // Si tiene prendas de confeccion, mantener
            if ($prendasNormales > 0) {
                continue;
            }

            $prendasBodega = $prendas->filter(function ($prenda) {
                return in_array($prenda->de_bodega, [1, true, '1'], true);
            })->values();

            if ($prendasBodega->isNotEmpty()) {
                $prendaIdsBodega = $prendasBodega->pluck('id');

                $procesosNuevos = PedidosProcesosPrendaDetalle::whereIn('prenda_pedido_id', $prendaIdsBodega)
                    ->whereNull('deleted_at')
                    ->selectRaw('prenda_pedido_id, COUNT(*) as total')
                    ->groupBy('prenda_pedido_id')
                    ->pluck('total', 'prenda_pedido_id');

                $procesosLegacy = ProcesoPrenda::whereIn('prenda_pedido_id', $prendaIdsBodega)
                    ->whereNull('deleted_at')
                    ->selectRaw('prenda_pedido_id, COUNT(*) as total')
                    ->groupBy('prenda_pedido_id')
                    ->pluck('total', 'prenda_pedido_id');

                $tieneAlgunaPrendaConProcesos = false;
                $detallesPrendas = [];

                foreach ($prendasBodega as $prenda) {
                    $cantidadProcesos = (int) ($procesosNuevos[$prenda->id] ?? 0)
                        + (int) ($procesosLegacy[$prenda->id] ?? 0);

                    $detallesPrendas[] = [
                        'nombre' => $prenda->nombre_prenda,
                        'procesos' => $cantidadProcesos,
                    ];

                    if ($cantidadProcesos > 0) {
                        $tieneAlgunaPrendaConProcesos = true;
                    }
                }

                if (!$tieneAlgunaPrendaConProcesos) {
                    $pedidosAExcluir->push($pedido->id);

                    Log::info('[CARTERA-FILTRO] Pedido EXCLUIDO (solo bodega sin procesos)', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'pedido_id' => $pedido->id,
                        'prendas_normales' => $prendasNormales,
                        'prendas_bodega' => $prendasBodega->count(),
                        'detalles' => $detallesPrendas,
                    ]);
                } else {
                    Log::info('[CARTERA-FILTRO] Pedido MANTIENE (bodega con procesos)', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'detalles' => $detallesPrendas,
                    ]);
                }
            } else {
                // No hay prendas de confeccion ni de bodega validas
                $pedidosAExcluir->push($pedido->id);

                Log::info('[CARTERA-FILTRO] Pedido EXCLUIDO (sin prendas productivas)', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'pedido_id' => $pedido->id,
                ]);
            }
        }

        return $pedidos->reject(function ($pedido) use ($pedidosAExcluir) {
            return $pedidosAExcluir->contains($pedido->id);
        });
    }
}

