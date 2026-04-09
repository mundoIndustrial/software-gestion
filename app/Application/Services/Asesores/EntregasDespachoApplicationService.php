<?php

namespace App\Application\Services\Asesores;

use App\Models\BodegaDetalleTalla;
use App\Models\PedidoProduccion;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntregasDespachoApplicationService
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

    public function obtenerResumen(array $pedidoIds, ?Authenticatable $usuario): array
    {
        if (!$usuario) {
            throw new AuthenticationException('No autenticado');
        }

        $ids = array_values(array_unique(array_map('intval', $pedidoIds)));
        if (empty($ids)) {
            return [];
        }

        $permitidos = $ids;
        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('asesor')) {
            $permitidos = PedidoProduccion::query()
                ->whereIn('id', $ids)
                ->where('asesor_id', $usuario->id)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        if (empty($permitidos)) {
            return [];
        }

        $conteos = BodegaDetalleTalla::query()
            ->selectRaw('pedido_produccion_id, COUNT(*) as pendientes')
            ->whereIn('pedido_produccion_id', $permitidos)
            ->where('estado_bodega', 'Entregado')
            ->whereNull('fecha_entrega_despacho')
            ->whereNull('deleted_at')
            ->groupBy('pedido_produccion_id')
            ->pluck('pendientes', 'pedido_produccion_id');

        $map = [];
        foreach ($permitidos as $pedidoId) {
            $map[(int) $pedidoId] = [
                'pendientes_despacho' => (int) ($conteos[(int) $pedidoId] ?? 0),
            ];
        }

        return $map;
    }

    public function obtenerPendientesPorPedido(int $pedidoId): array
    {
        return BodegaDetalleTalla::query()
            ->select([
                'id',
                'prenda_nombre',
                'talla',
                'genero',
                'cantidad',
                'area',
                'estado_bodega',
                'fecha_entrega_bodega',
                'fecha_entrega',
                'fecha_entrega_despacho',
            ])
            ->where('pedido_produccion_id', $pedidoId)
            ->where('estado_bodega', 'Entregado')
            ->whereNull('deleted_at')
            ->orderByRaw('CASE WHEN fecha_entrega_despacho IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByRaw('COALESCE(fecha_entrega_bodega, fecha_entrega, created_at) ASC')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'prenda_nombre' => (string) ($row->prenda_nombre ?? '-'),
                    'talla' => (string) ($row->talla ?? '-'),
                    'genero' => (string) ($row->genero ?? '-'),
                    'cantidad' => (int) ($row->cantidad ?? 0),
                    'area' => (string) ($row->area ?? '-'),
                    'estado_bodega' => (string) ($row->estado_bodega ?? ''),
                    'fecha_entrega' => optional($row->fecha_entrega)?->format('Y-m-d'),
                    'fecha_entrega_bodega' => optional($row->fecha_entrega_bodega)?->format('Y-m-d H:i:s'),
                    'fecha_entrega_despacho' => optional($row->fecha_entrega_despacho)?->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->all();
    }

    public function marcarEnDespacho(int $pedidoId, int $detalleId): array
    {
        $detalle = BodegaDetalleTalla::query()
            ->where('id', $detalleId)
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('deleted_at')
            ->first();

        if (!$detalle) {
            throw new NotFoundHttpException('Detalle de entrega no encontrado');
        }

        if ((string) $detalle->estado_bodega !== 'Entregado') {
            throw new AccessDeniedHttpException('Solo se pueden marcar detalles en estado Entregado');
        }

        if ($detalle->fecha_entrega_despacho) {
            return [
                'already_marked' => true,
                'fecha_entrega_despacho' => optional($detalle->fecha_entrega_despacho)->format('Y-m-d H:i:s'),
            ];
        }

        $detalle->fecha_entrega_despacho = now();
        $detalle->save();

        return [
            'already_marked' => false,
            'fecha_entrega_despacho' => optional($detalle->fecha_entrega_despacho)->format('Y-m-d H:i:s'),
        ];
    }
}
