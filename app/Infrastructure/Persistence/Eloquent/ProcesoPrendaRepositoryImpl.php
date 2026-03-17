<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\ProcesoPrenda;

class ProcesoPrendaRepositoryImpl implements ProcesoPrendaRepository
{
    public function findLatestByPrendaAndProceso(int $prendaId, string $proceso): ?ProcesoPrenda
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();
    }

    public function findLatestByProceso(int $numeroPedido, int $prendaId, string $proceso): ?ProcesoPrenda
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->where('numero_pedido', $numeroPedido)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->whereNull('deleted_at')
            ->latest('fecha_inicio')
            ->first();
    }

    public function findLatestByProcesoAndNumeroRecibo(int $numeroPedido, int $prendaId, string $proceso, int $numeroRecibo): ?ProcesoPrenda
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->where('numero_pedido', $numeroPedido)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->where('numero_recibo', $numeroRecibo)
            ->whereNull('deleted_at')
            ->latest('fecha_inicio')
            ->first();
    }

    public function findLatestNotProcesoByNumeroRecibo(int $numeroPedido, int $prendaId, string $procesoExcluido, int $numeroRecibo): ?ProcesoPrenda
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->where('numero_pedido', $numeroPedido)
            ->where('numero_recibo', $numeroRecibo)
            ->whereRaw('LOWER(TRIM(proceso)) != ?', [strtolower(trim($procesoExcluido))])
            ->whereNull('deleted_at')
            ->latest('fecha_inicio')
            ->first();
    }

    public function create(array $attributes): ProcesoPrenda
    {
        return ProcesoPrenda::create($attributes);
    }

    public function update(ProcesoPrenda $proceso, array $attributes): void
    {
        $proceso->update($attributes);
    }

    public function forceDelete(ProcesoPrenda $proceso): void
    {
        $proceso->forceDelete();
    }
}
