<?php

namespace App\Infrastructure\Persistence\Eloquent\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Models\DisenoLogoPedido;
use Illuminate\Support\Collection;

final class DisenoLogoPedidoRepository implements DisenoLogoPedidoRepositoryInterface
{
    public function listarPorProceso(int $procesoPrendaDetalleId): Collection
    {
        return DisenoLogoPedido::query()
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
            ->orderBy('id', 'asc')
            ->get(['id', 'url', 'observacio_diseño']);
    }

    public function contarPorProceso(int $procesoPrendaDetalleId): int
    {
        return DisenoLogoPedido::query()
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
            ->count();
    }

    public function crear(int $procesoPrendaDetalleId, string $url, ?string $observacion = null): array
    {
        $row = DisenoLogoPedido::create([
            'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
            'url' => $url,
            'observacio_diseño' => $observacion,
        ]);

        return [
            'id' => $row->id,
            'url' => $row->url,
            'observacio_diseño' => $row->{'observacio_diseño'} ?? null,
        ];
    }

    public function findById(int $id): ?object
    {
        return DisenoLogoPedido::query()->find($id);
    }

    public function eliminarPorId(int $id): void
    {
        DisenoLogoPedido::query()->where('id', $id)->delete();
    }
}
