<?php

namespace App\Domain\Pedidos\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Repository: RecibosRepository
 * 
 * Responsabilidad: Centralizar TODAS las queries de recibos (consecutivos_recibos_pedidos)
 * Patrón: Repository (acceso a datos)
 * 
 * Ventajas:
 * - Separa lógica de acceso a datos de UseCases y Controlador
 * - Fácil de testear (mock del repository)
 * - Fácil cambiar BD sin tocar capas superiores
 * - Reutilizable en múltiples UseCases
 */
class RecibosRepository
{
    /**
     * Obtener un recibo de COSTURA por ID
     */
    public function obtenerReciboCostura(int $id): ?object
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->select([
                'consecutivos_recibos_pedidos.*',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.cliente'
            ])
            ->join('pedidos_produccion', 'pedidos_produccion.id', '=', 'consecutivos_recibos_pedidos.pedido_produccion_id')
            ->where('consecutivos_recibos_pedidos.id', $id)
            ->where('consecutivos_recibos_pedidos.tipo_recibo', 'COSTURA')
            ->where('consecutivos_recibos_pedidos.activo', 1)
            ->first();
    }

    /**
     * Obtener un recibo de REFLECTIVO por ID
     */
    public function obtenerReciboReflectivo(int $id): ?object
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->select([
                'consecutivos_recibos_pedidos.*',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.cliente'
            ])
            ->join('pedidos_produccion', 'pedidos_produccion.id', '=', 'consecutivos_recibos_pedidos.pedido_produccion_id')
            ->where('consecutivos_recibos_pedidos.id', $id)
            ->where('consecutivos_recibos_pedidos.tipo_recibo', 'REFLECTIVO')
            ->where('consecutivos_recibos_pedidos.activo', 1)
            ->first();
    }

    /**
     * Construir query base para recibos COSTURA activos
     * (Sin ejecutar - permite encadenar filtros adicionales)
     */
    public function queryRecibosCozturaActivos(): Builder
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->select([
                'consecutivos_recibos_pedidos.*',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado',
                'pedidos_produccion.area',
            ])
            ->join('pedidos_produccion', 'pedidos_produccion.id', '=', 'consecutivos_recibos_pedidos.pedido_produccion_id')
            ->where('consecutivos_recibos_pedidos.tipo_recibo', 'COSTURA')
            ->where('consecutivos_recibos_pedidos.activo', 1);
    }

    /**
     * Construir query base para recibos REFLECTIVO activos
     */
    public function queryRecibosReflectivoActivos(): Builder
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->select([
                'consecutivos_recibos_pedidos.*',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado',
                'pedidos_produccion.area',
            ])
            ->join('pedidos_produccion', 'pedidos_produccion.id', '=', 'consecutivos_recibos_pedidos.pedido_produccion_id')
            ->where('consecutivos_recibos_pedidos.tipo_recibo', 'REFLECTIVO')
            ->where('consecutivos_recibos_pedidos.activo', 1);
    }

    /**
     * Obtener recibos COSTURA activos (sin filtros adicionales)
     */
    public function obtenerRecibosCozturaActivos(): Collection
    {
        return $this->queryRecibosCozturaActivos()
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Obtener recibos REFLECTIVO activos (sin filtros adicionales)
     */
    public function obtenerRecibosReflectivoActivos(): Collection
    {
        return $this->queryRecibosReflectivoActivos()
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Contar recibos de COSTURA en ejecución (área Corte) excluyendo vistos por usuario
     */
    public function contarRecibosEjecutandoCorte(int $userId): int
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('estado', 'En Ejecucion')
            ->where('area', 'Corte')
            ->where('activo', 1)
            ->whereNotIn('id', function($query) use ($userId) {
                $query->select('consecutivo_recibo_id')
                    ->from('recibos_usuario_vistos')
                    ->where('user_id', $userId)
                    ->where('tipo_recibo', 'COSTURA');
            })
            ->count();
    }

    /**
     * Obtener recibos COSTURA en ejecución (área Corte) excluyendo vistos por usuario
     */
    public function obtenerRecibosEjecutandoCorte(int $userId): Collection
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->select([
                'id',
                'consecutivo_actual as numero_recibo',
                'pedido_produccion_id',
                'prenda_id',
                'created_at'
            ])
            ->where('tipo_recibo', 'COSTURA')
            ->where('estado', 'En Ejecucion')
            ->where('area', 'Corte')
            ->where('activo', 1)
            ->whereNotIn('id', function($query) use ($userId) {
                $query->select('consecutivo_recibo_id')
                    ->from('recibos_usuario_vistos')
                    ->where('user_id', $userId)
                    ->where('tipo_recibo', 'COSTURA');
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Marcar un recibo como visto por el usuario
     */
    public function marcarReciboVisto(int $reciboId, int $userId, string $tipo = 'COSTURA'): void
    {
        DB::table('recibos_usuario_vistos')->insertOrIgnore([
            'consecutivo_recibo_id' => $reciboId,
            'user_id' => $userId,
            'tipo_recibo' => $tipo,
            'viewed_at' => now(),
        ]);
    }

    /**
     * Obtener recibos con tabla de tallas/colores completa (para vista detallada)
     */
    public function obtenerReciboConTallasColores(int $reciboId, string $tipo = 'COSTURA'): ?object
    {
        $recibo = $tipo === 'COSTURA' 
            ? $this->obtenerReciboCostura($reciboId)
            : $this->obtenerReciboReflectivo($reciboId);

        if (!$recibo) {
            return null;
        }

        // Agregar tabla de tallas/colores
        $tallas = DB::table('prenda_pedido_talla_colores')
            ->join('prenda_pedido_tallas', 'prenda_pedido_talla_colores.prenda_pedido_talla_id', '=', 'prenda_pedido_tallas.id')
            ->where('prenda_pedido_tallas.prenda_pedido_id', $recibo->prenda_id)
            ->select(['prenda_pedido_talla_colores.*', 'prenda_pedido_tallas.talla'])
            ->get();

        $recibo->tallas_colores = $tallas;

        return $recibo;
    }

    /**
     * Filtrar recibos COSTURA por estado
     */
    public function filtrarRecibosCozturaEstado(array $estados, int $limit = 100): Collection
    {
        return $this->queryRecibosCozturaActivos()
            ->whereIn('consecutivos_recibos_pedidos.estado', $estados)
            ->limit($limit)
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Filtrar recibos COSTURA por cliente
     */
    public function filtrarRecibosCozturaCliente(string $cliente, int $limit = 100): Collection
    {
        return $this->queryRecibosCozturaActivos()
            ->where('pedidos_produccion.cliente', 'LIKE', "%{$cliente}%")
            ->limit($limit)
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Filtrar recibos por número de recibo (consecutivo)
     */
    public function filtrarRecibosNumero(array $numeros, string $tipo = 'COSTURA', int $limit = 100): Collection
    {
        $query = $tipo === 'COSTURA' 
            ? $this->queryRecibosCozturaActivos()
            : $this->queryRecibosReflectivoActivos();

        return $query->whereIn('consecutivos_recibos_pedidos.consecutivo_actual', $numeros)
            ->limit($limit)
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Filtrar recibos por día de entrega
     */
    public function filtrarRecibosEntreguaPorDia(array $dias, string $tipo = 'COSTURA', int $limit = 100): Collection
    {
        $query = $tipo === 'COSTURA' 
            ? $this->queryRecibosCozturaActivos()
            : $this->queryRecibosReflectivoActivos();

        return $query->whereIn('pedidos_produccion.dia_de_entrega', $dias)
            ->limit($limit)
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Filtrar recibos REFLECTIVO aprobados por proceso (REFLECTIVO=tipo_proceso_id:1)
     */
    public function obtenerRecibosReflectivoAprobados(int $limit = 100): Collection
    {
        $prendasAprobadas = DB::table('pedidos_procesos_prenda_detalles')
            ->where('tipo_proceso_id', 1) // REFLECTIVO
            ->where('estado', 'APROBADO')
            ->whereNull('deleted_at')
            ->pluck('prenda_pedido_id')
            ->unique()
            ->toArray();

        return $this->queryRecibosReflectivoActivos()
            ->whereIn('consecutivos_recibos_pedidos.prenda_id', $prendasAprobadas)
            ->limit($limit)
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }

    /**
     * Contar total de recibos COSTURA activos
     */
    public function contarRecibosCozturaActivos(): int
    {
        return $this->queryRecibosCozturaActivos()->count();
    }

    /**
     * Contar total de recibos REFLECTIVO activos
     */
    public function contarRecibosReflectivoActivos(): int
    {
        return $this->queryRecibosReflectivoActivos()->count();
    }

    /**
     * Obtener recibos por rango de fechas
     */
    public function filtrarRecibosEntreFechas(
        string $fechaInicio,
        string $fechaFin,
        string $tipo = 'COSTURA',
        int $limit = 100
    ): Collection {
        $query = $tipo === 'COSTURA' 
            ? $this->queryRecibosCozturaActivos()
            : $this->queryRecibosReflectivoActivos();

        return $query->whereBetween('consecutivos_recibos_pedidos.created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->limit($limit)
            ->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
            ->get();
    }
}
