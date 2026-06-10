<?php

namespace App\Infrastructure\Repositories\Operario;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperarioRecibosRepository
{
    public function obtenerRolIdPorNombre(string $nombreRol): ?int
    {
        $id = Role::where('name', $nombreRol)->value('id');
        return $id ? (int) $id : null;
    }

    public function obtenerNombresUsuariosPorRolId(int $rolId): Collection
    {
        return User::query()
            ->where(function ($q) use ($rolId) {
                $q->whereJsonContains('roles_ids', $rolId)
                    ->orWhere('role_id', $rolId);
            })
            ->pluck('name')
            ->map(fn($n) => strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->values();
    }

    public function obtenerRecibosActivosPorTiposYAreas(array $tiposRecibo, array $areas): Collection
    {
        return ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->whereIn('area', $areas)
            ->select([
                'id',
                'prenda_id',
                'pedido_produccion_id',
                'tipo_recibo',
                'consecutivo_actual',
                'consecutivo_inicial',
                'notas',
                'area',
                'created_at',
                'activo',
            ])
            ->with([
                'prenda:id,pedido_produccion_id,nombre_prenda,descripcion,de_bodega,created_at',
                'prenda.pedidoProduccion:id,numero_pedido,cliente,created_at',
                'prenda.procesosPrenda',
                'prenda.tallas:id,prenda_pedido_id,genero,talla,cantidad,tipo_talla,es_sobremedida,tela,colores',
                'pedido:id,numero_pedido,cliente,created_at',
                'pedido.prendas:id,pedido_produccion_id,nombre_prenda,descripcion,de_bodega,created_at',
                'pedido.prendas.tallas:id,prenda_pedido_id,genero,talla,cantidad,tipo_talla,es_sobremedida,tela,colores',
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function obtenerCompletadosPorReciboIds(array $reciboIds): Collection
    {
        if (empty($reciboIds)) {
            return collect();
        }

        return DB::table('prenda_recibo_completado')
            ->whereIn('id_recibo', $reciboIds)
            ->select(['id_recibo', 'area', 'fecha_completado', 'tallas_control_calidad'])
            ->get();
    }

    public function existeReciboPorPartes(
        int $pedidoProduccionId,
        string $tipoRecibo,
        string $consecutivoOriginal,
        ?int $prendaPedidoId = null
    ): bool
    {
        return ReciboPorPartes::query()
            ->where('pedido_produccion_id', $pedidoProduccionId)
            ->when($prendaPedidoId !== null, fn($q) => $q->where('prenda_pedido_id', $prendaPedidoId))
            ->where('tipo_recibo', $tipoRecibo)
            ->where('consecutivo_original', $consecutivoOriginal)
            ->exists();
    }

    public function obtenerPedidoParcialCreatedAt(int $parcialId): mixed
    {
        return DB::table('pedidos_parciales')
            ->where('id', $parcialId)
            ->whereNull('deleted_at')
            ->value('created_at');
    }

    public function obtenerPedidosParcialesCreatedAtMap(array $parcialIds): Collection
    {
        if (empty($parcialIds)) {
            return collect();
        }

        return DB::table('pedidos_parciales')
            ->whereIn('id', $parcialIds)
            ->whereNull('deleted_at')
            ->pluck('created_at', 'id');
    }

    public function obtenerReciboPorPartesKeys(): array
    {
        $rows = DB::table('recibo_por_partes')
            ->select(['pedido_produccion_id', 'tipo_recibo', 'consecutivo_original'])
            ->distinct()
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = (int) $row->pedido_produccion_id . '|' . strtoupper(trim((string) $row->tipo_recibo)) . '|' . trim((string) $row->consecutivo_original);
            $map[$key] = true;
        }

        return $map;
    }

    public function obtenerAnexosActivosPorTipos(array $tiposParcial): Collection
    {
        return DB::table('pedidos_parciales as pp')
            ->join('pedidos_produccion as p', 'pp.pedido_produccion_id', '=', 'p.id')
            ->join('prendas_pedido as pr', 'pp.prenda_pedido_id', '=', 'pr.id')
            ->where('pp.activo', 1)
            ->whereIn('pp.tipo_recibo', $tiposParcial)
            ->whereNull('pp.deleted_at')
            ->select('pp.*', 'p.numero_pedido', 'pr.nombre_prenda', 'pr.descripcion')
            ->orderBy('pp.created_at', 'asc')
            ->get();
    }

    public function obtenerProcesosPorPrendaIds(array $prendaIds): Collection
    {
        if (empty($prendaIds)) {
            return collect();
        }

        return ProcesoPrenda::query()
            ->whereIn('prenda_pedido_id', $prendaIds)
            ->whereNull('deleted_at')
            ->get();
    }

    public function obtenerParcialesCompletadosEnCorteMap(array $parcialesIds): array
    {
        if (empty($parcialesIds)) {
            return [];
        }

        $ids = DB::table('prenda_recibo_completado')
            ->whereIn('id_parcial', $parcialesIds)
            ->where('area', 'Corte')
            ->pluck('id_parcial')
            ->all();

        return array_flip($ids);
    }

    public function existeCompletadoParcialEnCorte(int $parcialId): bool
    {
        return DB::table('prenda_recibo_completado')
            ->where('id_parcial', $parcialId)
            ->where('area', 'Corte')
            ->exists();
    }

    public function obtenerTallasAnexo(int $parcialId): Collection
    {
        return DB::table('pedidos_parciales_tallas')
            ->where('pedido_parcial_id', $parcialId)
            ->get();
    }

    public function obtenerCompletadoParcialEnCostura(int $parcialId): ?object
    {
        return DB::table('prenda_recibo_completado')
            ->where('area', 'Costura')
            ->where('id_parcial', $parcialId)
            ->first();
    }

    public function obtenerCompletadoParcialEnControlCalidad(int $parcialId): ?object
    {
        return DB::table('prenda_recibo_completado')
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('id_parcial', $parcialId)
            ->first();
    }

    public function buscarUsuarioPorNombreNormalizado(string $nombreNormalizado): ?User
    {
        return User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$nombreNormalizado])
            ->first();
    }

    public function obtenerNumerosPedidoAsignadosPorEncargado(string $encargadoNormalizado): array
    {
        return ProcesoPrenda::query()
            ->select('numero_pedido')
            ->whereNotNull('encargado')
            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$encargadoNormalizado])
            ->distinct()
            ->pluck('numero_pedido')
            ->all();
    }

    public function buscarUltimoProcesoPorNumeroPedidoPrendaReciboYProcesoSinParcial(
        int $numeroPedido,
        int $prendaPedidoId,
        string $numeroRecibo,
        string $proceso
    ): ?ProcesoPrenda {
        return ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->where('numero_recibo', $numeroRecibo)
            ->where(function ($query) {
                $query->whereNull('numero_recibo_parcial')
                    ->orWhere('numero_recibo_parcial', '')
                    ->orWhere('numero_recibo_parcial', 0);
            })
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();
    }

    public function buscarUltimoProcesoPorNumeroPedidoPrendaReciboSinParcial(
        int $numeroPedido,
        int $prendaPedidoId,
        string $numeroRecibo
    ): ?ProcesoPrenda {
        return ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->where('numero_recibo', $numeroRecibo)
            ->where(function ($query) {
                $query->whereNull('numero_recibo_parcial')
                    ->orWhere('numero_recibo_parcial', '')
                    ->orWhere('numero_recibo_parcial', 0);
            })
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();
    }

    public function buscarUltimoProcesoPorPedidoProduccionYPrenda(
        int $pedidoProduccionId,
        int $prendaPedidoId,
        string $proceso,
        string $consecutivo,
        bool $esParcial
    ): ?ProcesoPrenda {
        $query = ProcesoPrenda::query()
            ->where('numero_pedido', $pedidoProduccionId)
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->where('proceso', strtoupper(trim($proceso)))
            ->whereNull('deleted_at');

        if ($esParcial) {
            $query->where('numero_recibo_parcial', $consecutivo);
        } else {
            $query->where('numero_recibo', $consecutivo)
                ->where(function ($q) {
                    $q->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', '')
                        ->orWhere('numero_recibo_parcial', 0);
                });
        }

        return $query->latest('created_at')->first();
    }

    public function obtenerProcesosCortePorPrendaBodegaIdsYEncargado(array $prendaBodegaIds, string $encargadoNormalizado): Collection
    {
        if (empty($prendaBodegaIds)) {
            return collect();
        }

        return ProcesoPrenda::query()
            ->whereIn('prenda_bodega_id', $prendaBodegaIds)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$encargadoNormalizado])
            ->whereNull('deleted_at')
            ->get();
    }

    public function contarPrendasBodegaEnProcesoCortePorEncargado(array $prendaBodegaIds, string $encargadoNormalizado): int
    {
        if (empty($prendaBodegaIds)) {
            return 0;
        }

        return ProcesoPrenda::query()
            ->whereIn('prenda_bodega_id', $prendaBodegaIds)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$encargadoNormalizado])
            ->whereNull('deleted_at')
            ->distinct('prenda_bodega_id')
            ->count();
    }
}
