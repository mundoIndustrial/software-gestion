<?php

namespace App\Infrastructure\ProcesoSeguimiento\Persistence\Eloquent;

use App\Domain\ProcesoSeguimiento\Repositories\ProcesoPrendaSeguimientoRepository;
use App\Models\ProcesoPrenda;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of ProcesoPrendaSeguimientoRepository.
 *
 * Toda interacción con la tabla `procesos_prenda` pasa por aquí.
 * Los Use Cases solo ven la interfaz del dominio.
 */
class EloquentProcesoPrendaSeguimientoRepository implements ProcesoPrendaSeguimientoRepository
{
    public function encontrarActivoPorArea(int $numeroPedido, int $prendaId, string $area): ?ProcesoPrenda
    {
        return ProcesoPrenda::where([
            ['numero_pedido',    '=', $numeroPedido],
            ['prenda_pedido_id', '=', $prendaId],
            ['proceso',         '=', $area],
            ['estado_proceso',  '!=', 'Completado'],
        ])->first();
    }

    public function encontrarMasReciente(int $prendaId, int $numeroPedido): ?ProcesoPrenda
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->where('numero_pedido', $numeroPedido)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function guardar(ProcesoPrenda $proceso): ProcesoPrenda
    {
        $proceso->save();
        return $proceso;
    }

    public function eliminar(int $procesoId): void
    {
        ProcesoPrenda::findOrFail($procesoId)->forceDelete();
    }

    public function obtenerPorPrenda(int $prendaId): Collection
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->with(['prenda', 'pedido'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
