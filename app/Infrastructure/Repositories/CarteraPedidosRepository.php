<?php

namespace App\Infrastructure\Repositories;

use App\Models\PedidoProduccion;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

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

        $total = $query->count();

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('created_at', $sortOrder);
        }

        $pedidos = $query->forPage($page, $perPage)->get();

        return [
            'pedidos' => $pedidos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
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

        $total = $query->count();

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('aprobado_por_cartera_en', $sortOrder);
        }

        $pedidos = $query->forPage($page, $perPage)->get();

        return [
            'pedidos' => $pedidos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
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

        $total = $query->count();

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('rechazado_por_cartera_en', $sortOrder);
        }

        $pedidos = $query->forPage($page, $perPage)->get();

        return [
            'pedidos' => $pedidos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
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

        $total = $query->count();

        if ($sortBy === 'cliente') {
            $query->orderBy('cliente', $sortOrder);
        } else {
            $query->orderBy('updated_at', $sortOrder);
        }

        $pedidos = $query->forPage($page, $perPage)->get();

        return [
            'pedidos' => $pedidos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
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
}
