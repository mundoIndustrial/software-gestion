<?php

namespace App\Application\Services\Asesores;

use App\Models\BodegaNota;
use App\Models\PedidoObservacionesDespacho;
use App\Models\PedidoProduccion;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ObservacionesDespachoApplicationService
{
    public function validarAccesoPedidoPorId(?Authenticatable $usuario, int $pedidoId): int
    {
        if (!$usuario) {
            throw new AuthenticationException('No autenticado');
        }

        $pedido = PedidoProduccion::query()
            ->select(['id', 'asesor_id'])
            ->find($pedidoId);

        if (!$pedido) {
            throw new NotFoundHttpException('Pedido no encontrado');
        }

        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('asesor')) {
            if ((string) $pedido->asesor_id !== (string) $usuario->id) {
                throw new AccessDeniedHttpException('No tienes permiso para ver este pedido');
            }
        }

        return (int) $pedido->id;
    }

    public function validarAccesoPedido(?Authenticatable $usuario, PedidoProduccion $pedido): void
    {
        if (!$usuario) {
            throw new AuthenticationException('No autenticado');
        }

        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('asesor')) {
            if ((string) $pedido->asesor_id !== (string) $usuario->id) {
                throw new AccessDeniedHttpException('No tienes permiso para ver este pedido');
            }
        }
    }

    public function obtenerObservacionesUnificadas(int $pedidoId): array
    {
        $despachoRows = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->orderByDesc('created_at')
            ->get();

        $bodegaRows = BodegaNota::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->orderByDesc('created_at')
            ->get();

        $observacionesDespacho = $despachoRows->map(fn ($row) => $this->mapDespacho($row));
        $observacionesBodega = $bodegaRows->map(fn ($row) => $this->mapBodega($row));

        return $observacionesDespacho
            ->concat($observacionesBodega)
            ->sortByDesc(fn ($item) => $item['updated_at'] ?: $item['created_at'] ?: '')
            ->values()
            ->all();
    }

    public function obtenerResumen(array $pedidoIds, ?Authenticatable $usuario): array
    {
        if (!$usuario) {
            throw new AuthenticationException('No autenticado');
        }

        $ids = array_values(array_unique(array_map('intval', $pedidoIds)));

        $permitidos = $ids;
        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('asesor')) {
            $permitidos = PedidoProduccion::query()
                ->whereIn('id', $ids)
                ->where('asesor_id', $usuario->id)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $conteosDespacho = PedidoObservacionesDespacho::query()
            ->selectRaw('pedido_produccion_id, COUNT(*) as unread')
            ->whereIn('pedido_produccion_id', $permitidos)
            ->where('usuario_rol', 'Despacho')
            ->whereNull('visto_at')
            ->groupBy('pedido_produccion_id')
            ->pluck('unread', 'pedido_produccion_id');

        $conteosBodega = BodegaNota::query()
            ->selectRaw('pedido_produccion_id, COUNT(*) as unread')
            ->whereIn('pedido_produccion_id', $permitidos)
            ->whereNull('visto_at')
            ->groupBy('pedido_produccion_id')
            ->pluck('unread', 'pedido_produccion_id');

        $map = [];
        foreach ($permitidos as $pedidoId) {
            $unreadDespacho = (int) ($conteosDespacho[(int) $pedidoId] ?? 0);
            $unreadBodega = (int) ($conteosBodega[(int) $pedidoId] ?? 0);
            $map[(int) $pedidoId] = [
                'unread' => $unreadDespacho + $unreadBodega,
                'unread_despacho' => $unreadDespacho,
                'unread_bodega' => $unreadBodega,
            ];
        }

        return $map;
    }

    public function marcarBodegaVistas(int $pedidoId): void
    {
        BodegaNota::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('visto_at')
            ->update(['visto_at' => now()]);
    }

    public function marcarDespachoLeidas(int $pedidoId): void
    {
        PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->where('usuario_rol', 'Despacho')
            ->whereNull('visto_at')
            ->update(['visto_at' => now()]);
    }

    public function guardar(
        int $pedidoId,
        string $contenido,
        ?Authenticatable $usuario,
        ?string $ipAddress
    ): PedidoObservacionesDespacho {
        return PedidoObservacionesDespacho::create([
            'pedido_produccion_id' => $pedidoId,
            'uuid' => (string) Str::uuid(),
            'contenido' => $contenido,
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name,
            'usuario_rol' => method_exists($usuario, 'getCurrentRole') ? $usuario?->getCurrentRole()?->name : null,
            'ip_address' => $ipAddress,
            'estado' => 0,
        ]);
    }

    public function actualizar(
        int $pedidoId,
        string $observacionId,
        string $contenido,
        ?Authenticatable $usuario
    ): PedidoObservacionesDespacho {
        $row = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->where('uuid', $observacionId)
            ->first();

        if (!$row) {
            throw new NotFoundHttpException('Observación no encontrada');
        }

        if ((string) $row->usuario_id !== (string) ($usuario?->id)) {
            throw new AccessDeniedHttpException('No tienes permiso para editar esta observación');
        }

        $row->contenido = $contenido;
        $row->save();

        return $row;
    }

    public function eliminar(
        int $pedidoId,
        string $observacionId,
        ?Authenticatable $usuario
    ): PedidoObservacionesDespacho {
        $row = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->where('uuid', $observacionId)
            ->first();

        if (!$row) {
            throw new NotFoundHttpException('Observación no encontrada');
        }

        if ((string) $row->usuario_id !== (string) ($usuario?->id)) {
            throw new AccessDeniedHttpException('No tienes permiso para eliminar esta observación');
        }

        $row->delete();

        return $row;
    }

    public function mapPayload(PedidoObservacionesDespacho $row): array
    {
        return $this->mapDespacho($row);
    }

    private function mapDespacho(PedidoObservacionesDespacho $row): array
    {
        return [
            'source' => 'despacho',
            'id' => (string) $row->uuid,
            'contenido' => $row->contenido,
            'talla' => null,
            'usuario_id' => $row->usuario_id,
            'usuario_nombre' => $row->usuario_nombre,
            'usuario_rol' => $row->usuario_rol,
            'ip_address' => $row->ip_address,
            'estado' => (int) $row->estado,
            'created_at' => optional($row->created_at)->toISOString(),
            'updated_at' => optional($row->updated_at)->toISOString(),
        ];
    }

    private function mapBodega(BodegaNota $row): array
    {
        $itemNombre = null;
        $itemArea = null;
        $tallaValor = (string) ($row->talla ?? '');
        $esHashTecnico = (bool) preg_match('/^[a-f0-9]{32}$/i', trim($tallaValor));

        // 1) Relación explícita por pedido_epp_id (si existe en la nota)
        if (!$itemNombre && !empty($row->pedido_epp_id) && Schema::hasTable('pedido_epp')) {
            $eppPorPedidoEppId = DB::table('pedido_epp as pe')
                ->leftJoin('epps as e', 'pe.epp_id', '=', 'e.id')
                ->where('pe.id', (int) $row->pedido_epp_id)
                ->whereNull('pe.deleted_at')
                ->select('e.nombre_completo as epp_nombre')
                ->first();

            if ($eppPorPedidoEppId && !empty($eppPorPedidoEppId->epp_nombre)) {
                $itemNombre = (string) $eppPorPedidoEppId->epp_nombre;
                $itemArea = 'EPP';
            }
        }

        // 2) Hash técnico -> resolver como local_id de pedido_epp (antes de cualquier fallback de talla)
        if (!$itemNombre && $esHashTecnico && Schema::hasTable('pedido_epp')) {
            $hash = trim($tallaValor);
            $eppDesdeLocalId = DB::table('pedido_epp as pe')
                ->leftJoin('epps as e', 'pe.epp_id', '=', 'e.id')
                ->where('pe.pedido_produccion_id', $row->pedido_produccion_id)
                ->where('pe.local_id', $hash)
                ->whereNull('pe.deleted_at')
                ->orderByDesc('pe.id')
                ->select('e.nombre_completo as epp_nombre')
                ->first();

            if (!$eppDesdeLocalId) {
                $eppDesdeLocalId = DB::table('pedido_epp as pe')
                    ->leftJoin('epps as e', 'pe.epp_id', '=', 'e.id')
                    ->where('pe.pedido_produccion_id', $row->pedido_produccion_id)
                    ->where('e.id', $hash)
                    ->whereNull('pe.deleted_at')
                    ->orderByDesc('pe.id')
                    ->select('e.nombre_completo as epp_nombre')
                    ->first();
            }

            if ($eppDesdeLocalId && !empty($eppDesdeLocalId->epp_nombre)) {
                $itemNombre = (string) $eppDesdeLocalId->epp_nombre;
                $itemArea = 'EPP';
            }
        }

        if (Schema::hasTable('bodega_detalles_talla')) {
            $detalleItem = null;

            // Relación explícita (nueva): usar id estable del detalle si existe.
            if (!empty($row->bodega_detalle_talla_id)) {
                $detalleItem = DB::table('bodega_detalles_talla')
                    ->where('id', (int) $row->bodega_detalle_talla_id)
                    ->select('prenda_nombre', 'area')
                    ->first();
            }

            if (!$detalleItem && !$itemNombre && $esHashTecnico) {
                $detalleItem = DB::table('bodega_detalles_talla')
                    ->where('pedido_produccion_id', $row->pedido_produccion_id)
                    ->where('row_hash', trim($tallaValor))
                    ->select('prenda_nombre', 'area')
                    ->first();

                // Fallback: algunos históricos no resuelven bien por pedido_produccion_id,
                // pero sí por numero_pedido + row_hash.
                if (!$detalleItem) {
                    $detalleItem = DB::table('bodega_detalles_talla')
                        ->where('numero_pedido', $row->numero_pedido)
                        ->where('row_hash', trim($tallaValor))
                        ->select('prenda_nombre', 'area')
                        ->first();
                }

                // Fallback global: usar solo row_hash (casos históricos desalineados).
                if (!$detalleItem) {
                    $detalleItem = DB::table('bodega_detalles_talla')
                        ->where('row_hash', trim($tallaValor))
                        ->where('area', 'EPP')
                        ->select('prenda_nombre', 'area', 'updated_at')
                        ->orderByDesc('updated_at')
                        ->first();
                }
            }

            if (!$detalleItem && !$esHashTecnico) {
                $detalleItem = DB::table('bodega_detalles_talla')
                    ->where('pedido_produccion_id', $row->pedido_produccion_id)
                    ->where('talla', $row->talla)
                    ->when($row->talla_color_id !== null, function ($q) use ($row) {
                        return $q->where('talla_color_id', $row->talla_color_id);
                    }, function ($q) {
                        return $q->whereNull('talla_color_id');
                    })
                    ->select('prenda_nombre', 'area')
                    ->first();

                // Fallback adicional por numero_pedido para datos históricos.
                if (!$detalleItem) {
                    $detalleItem = DB::table('bodega_detalles_talla')
                        ->where('numero_pedido', $row->numero_pedido)
                        ->where('talla', $row->talla)
                        ->when($row->talla_color_id !== null, function ($q) use ($row) {
                            return $q->where('talla_color_id', $row->talla_color_id);
                        }, function ($q) {
                            return $q->whereNull('talla_color_id');
                        })
                        ->select('prenda_nombre', 'area')
                        ->first();
                }
            }

            if ($detalleItem) {
                $itemNombre = $detalleItem->prenda_nombre ?? $itemNombre;
                $itemArea = $detalleItem->area ?? $itemArea;
            }

            // Último fallback defensivo para nombre del ítem
            if (!$itemNombre && $row->numero_pedido) {
                $itemNombre = DB::table('bodega_detalles_talla')
                    ->where('numero_pedido', $row->numero_pedido)
                    ->when($esHashTecnico, function ($q) use ($tallaValor) {
                        return $q->where('row_hash', trim($tallaValor));
                    }, function ($q) use ($row) {
                        return $q->where('talla', $row->talla);
                    })
                    ->value('prenda_nombre');
            }

            // Fallback exacto para notas EPP históricas:
            // el hash guardado en talla suele corresponder a pedido_epp.local_id.
            if (!$itemNombre && $esHashTecnico && Schema::hasTable('pedido_epp')) {
                $eppDesdePedido = DB::table('pedido_epp as pe')
                    ->leftJoin('epps as e', 'pe.epp_id', '=', 'e.id')
                    ->where('pe.pedido_produccion_id', $row->pedido_produccion_id)
                    ->where(function ($q) use ($tallaValor) {
                        $hash = trim($tallaValor);
                        $q->where('pe.local_id', $hash)
                          ->orWhere('e.id', $hash);
                    })
                    ->whereNull('pe.deleted_at')
                    ->orderByDesc('pe.id')
                    ->select('e.nombre_completo as epp_nombre')
                    ->first();

                if ($eppDesdePedido && !empty($eppDesdePedido->epp_nombre)) {
                    $itemNombre = (string) $eppDesdePedido->epp_nombre;
                    $itemArea = 'EPP';
                }
            }

            // Diagnóstico para casos donde no se logra resolver el nombre del ítem.
            if (!$itemNombre) {
                $hash = trim($tallaValor);

                $candidatosPorHash = $esHashTecnico
                    ? DB::table('bodega_detalles_talla')
                        ->where('row_hash', $hash)
                        ->select('id', 'pedido_produccion_id', 'numero_pedido', 'talla', 'talla_color_id', 'prenda_nombre', 'area', 'updated_at')
                        ->orderByDesc('updated_at')
                        ->limit(5)
                        ->get()
                    : collect();

                $candidatosPorPedidoTalla = DB::table('bodega_detalles_talla')
                    ->where('numero_pedido', $row->numero_pedido)
                    ->where('talla', $row->talla)
                    ->select('id', 'pedido_produccion_id', 'numero_pedido', 'talla', 'talla_color_id', 'prenda_nombre', 'area', 'updated_at')
                    ->orderByDesc('updated_at')
                    ->limit(5)
                    ->get();

                Log::debug('[OBS_DESPACHO][MAP_BODEGA] No se resolvió item_nombre', [
                    'nota_id' => $row->id,
                    'pedido_produccion_id' => $row->pedido_produccion_id,
                    'numero_pedido' => $row->numero_pedido,
                    'talla_raw' => $row->talla,
                    'talla_color_id' => $row->talla_color_id,
                    'es_hash_tecnico' => $esHashTecnico,
                    'hash_valor' => $esHashTecnico ? $hash : null,
                    'candidatos_por_hash' => $candidatosPorHash->toArray(),
                    'candidatos_por_pedido_talla' => $candidatosPorPedidoTalla->toArray(),
                    'candidato_pedido_epp_local_id' => ($esHashTecnico && Schema::hasTable('pedido_epp'))
                        ? DB::table('pedido_epp as pe')
                            ->leftJoin('epps as e', 'pe.epp_id', '=', 'e.id')
                            ->where('pe.pedido_produccion_id', $row->pedido_produccion_id)
                            ->where('pe.local_id', $hash)
                            ->select('pe.id', 'pe.local_id', 'pe.epp_id', 'e.nombre_completo')
                            ->orderByDesc('pe.id')
                            ->limit(3)
                            ->get()
                            ->toArray()
                        : [],
                ]);
            }
        }

        return [
            'source' => 'bodega',
            'id' => 'bodega-' . (string) $row->id,
            'contenido' => $row->contenido,
            'talla' => ($esHashTecnico || strtoupper((string) $itemArea) === 'EPP') ? null : $row->talla,
            'talla_color_id' => $row->talla_color_id,
            'item_nombre' => $itemNombre,
            'area' => $itemArea,
            'usuario_id' => $row->usuario_id,
            'usuario_nombre' => $row->usuario_nombre,
            'usuario_rol' => $row->usuario_rol,
            'ip_address' => $row->ip_address,
            'estado' => null,
            'created_at' => optional($row->created_at)->toISOString(),
            'updated_at' => optional($row->updated_at)->toISOString(),
        ];
    }
}
