<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\ProcesoPrenda;

class ProcesoPrendaRepositoryImpl implements ProcesoPrendaRepository
{
    public function findByNumeroPedidoProcesoEncargado(int $numeroPedido, string $proceso, string $encargado): ?ProcesoPrenda
    {
        return ProcesoPrenda::query()
            ->where('numero_pedido', (int) $numeroPedido)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->whereRaw('LOWER(TRIM(encargado)) = ?', [strtolower(trim($encargado))])
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();
    }

    public function findLatestByPrendaAndProceso(int $prendaId, string $proceso, ?int $prendaBodegaId = null): ?ProcesoPrenda
    {
        $query = ProcesoPrenda::query()
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->whereNull('deleted_at');

        if ($prendaBodegaId !== null) {
            $query->where('prenda_bodega_id', $prendaBodegaId);
        } else {
            $query->where('prenda_pedido_id', $prendaId);
        }

        return $query->latest('created_at')->first();
    }

    public function findLatestByProceso(int $numeroPedido, int $prendaId, string $proceso, ?int $prendaBodegaId = null): ?ProcesoPrenda
    {
        $query = ProcesoPrenda::query()
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->whereNull('deleted_at');

        if ($prendaBodegaId !== null) {
            $query->where('prenda_bodega_id', $prendaBodegaId);
        } else {
            $query->where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $numeroPedido);
        }

        return $query->latest('fecha_inicio')->first();
    }

    public function findLatestByProcesoAndNumeroRecibo(int $numeroPedido, int $prendaId, string $proceso, int $numeroRecibo, ?int $prendaBodegaId = null): ?ProcesoPrenda
    {
        $query = ProcesoPrenda::query()
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [strtolower(trim($proceso))])
            ->where('numero_recibo', $numeroRecibo)
            ->whereNull('deleted_at');

        if ($prendaBodegaId !== null) {
            $query->where('prenda_bodega_id', $prendaBodegaId);
        } else {
            $query->where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $numeroPedido);
        }

        return $query->latest('fecha_inicio')->first();
    }

    public function findLatestNotProcesoByNumeroRecibo(int $numeroPedido, int $prendaId, string $procesoExcluido, int $numeroRecibo, ?int $prendaBodegaId = null): ?ProcesoPrenda
    {
        $query = ProcesoPrenda::query()
            ->where('numero_recibo', $numeroRecibo)
            ->whereRaw('LOWER(TRIM(proceso)) != ?', [strtolower(trim($procesoExcluido))])
            ->whereNull('deleted_at');

        if ($prendaBodegaId !== null) {
            $query->where('prenda_bodega_id', $prendaBodegaId);
        } else {
            $query->where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $numeroPedido);
        }

        return $query->latest('fecha_inicio')->first();
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

    /**
     * Obtiene todos los procesos de un tipo específico
     * @return \Illuminate\Database\Eloquent\Collection<int, ProcesoPrenda>
     */
    public function findByProceso(string $proceso): \Illuminate\Database\Eloquent\Collection
    {
        $norm = strtolower(trim($proceso));

        // Normalizar para soportar variantes comunes como "Control de Calidad" vs "Control Calidad"
        if (in_array($norm, ['control de calidad', 'control calidad'], true)) {
            return ProcesoPrenda::query()
                ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control de calidad', 'control calidad'])
                ->whereNull('deleted_at')
                ->get();
        }

        return ProcesoPrenda::query()
            ->whereRaw('LOWER(TRIM(proceso)) = ?', [$norm])
            ->whereNull('deleted_at')
            ->get();
    }
}
