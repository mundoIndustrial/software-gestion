<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\ProcesoPedidoWriteRepository;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\DB;

class ProcesoPedidoWriteRepositoryImpl implements ProcesoPedidoWriteRepository
{
    public function obtenerProcesoLegacyPorPedidoYNombre(int $numeroPedido, string $nombreProceso): ?array
    {
        $proceso = ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('proceso', $nombreProceso)
            ->first(['id', 'proceso']);

        if (!$proceso) {
            return null;
        }

        return [
            'id' => (int) $proceso->id,
            'proceso' => (string) $proceso->proceso,
        ];
    }

    public function actualizarProcesoLegacy(int $procesoId, string $fechaInicio, ?string $encargado, string $estadoProceso): void
    {
        ProcesoPrenda::query()
            ->whereKey($procesoId)
            ->update([
                'fecha_inicio' => $fechaInicio,
                'encargado' => $encargado,
                'estado_proceso' => $estadoProceso,
                'updated_at' => now(),
            ]);
    }

    public function crearProcesoLegacy(int $numeroPedido, string $nombreProceso, string $fechaInicio, ?string $encargado, string $estadoProceso): int
    {
        return (int) ProcesoPrenda::query()->insertGetId([
            'numero_pedido' => $numeroPedido,
            'proceso' => $nombreProceso,
            'fecha_inicio' => $fechaInicio,
            'encargado' => $encargado,
            'estado_proceso' => $estadoProceso,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function existeProcesoNuevoEnPedido(int $procesoId, int $numeroPedido): bool
    {
        return PedidosProcesosPrendaDetalle::query()
            ->whereKey($procesoId)
            ->whereHas('prenda', function ($query) use ($numeroPedido) {
                $query->where('pedido_produccion_id', $numeroPedido);
            })
            ->exists();
    }

    public function eliminarProcesoNuevoConDependencias(int $procesoId): void
    {
        $proceso = PedidosProcesosPrendaDetalle::query()
            ->with(['imagenes', 'tallas'])
            ->find($procesoId);

        if (!$proceso) {
            return;
        }

        DB::transaction(function () use ($proceso): void {
            foreach ($proceso->imagenes as $imagen) {
                $imagen->forceDelete();
            }

            foreach ($proceso->tallas as $talla) {
                $talla->delete();
            }

            $proceso->forceDelete();
        });
    }

    public function obtenerNombreProcesoLegacy(int $procesoId, int $numeroPedido): ?string
    {
        return ProcesoPrenda::query()
            ->where('id', $procesoId)
            ->where('numero_pedido', $numeroPedido)
            ->value('proceso');
    }

    public function contarProcesosLegacyDistintos(int $numeroPedido): int
    {
        return ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->whereNull('deleted_at')
            ->distinct('proceso')
            ->count('proceso');
    }

    public function eliminarProcesoLegacyPorNombre(int $numeroPedido, string $nombreProceso): void
    {
        DB::transaction(function () use ($numeroPedido, $nombreProceso): void {
            ProcesoPrenda::query()
                ->where('numero_pedido', $numeroPedido)
                ->where('proceso', $nombreProceso)
                ->delete();

            DB::table('procesos_historial')
                ->where('numero_pedido', $numeroPedido)
                ->where('proceso', $nombreProceso)
                ->delete();
        });
    }
}
