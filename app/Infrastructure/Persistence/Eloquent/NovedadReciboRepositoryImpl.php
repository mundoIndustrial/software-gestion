<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\NovedadReciboRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NovedadReciboRepositoryImpl implements NovedadReciboRepository
{
    public function crear(array $data): void
    {
        DB::table('prendas_pedido_novedades_recibo')->insert($data);
    }

    public function obtenerPorId(int $id): ?object
    {
        return DB::table('prendas_pedido_novedades_recibo')->find((int) $id);
    }

    public function obtenerPorPrenda(int $prendaPedidoId): Collection
    {
        return DB::table('prendas_pedido_novedades_recibo')
            ->where('prenda_pedido_id', (int) $prendaPedidoId)
            ->orderBy('creado_en', 'desc')
            ->get();
    }

    public function actualizar(int $id, array $data): void
    {
        DB::table('prendas_pedido_novedades_recibo')
            ->where('id', (int) $id)
            ->update($data);
    }

    public function eliminar(int $id): void
    {
        DB::table('prendas_pedido_novedades_recibo')->delete((int) $id);
    }

}
