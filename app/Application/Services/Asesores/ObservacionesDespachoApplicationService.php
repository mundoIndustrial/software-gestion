<?php

namespace App\Application\Services\Asesores;

use App\Models\BodegaNota;
use App\Models\PedidoObservacionesDespacho;
use App\Models\PedidoProduccion;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
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
        return [
            'source' => 'bodega',
            'id' => 'bodega-' . (string) $row->id,
            'contenido' => $row->contenido,
            'talla' => $row->talla,
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
