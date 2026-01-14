<?php

namespace App\Repositories;

use App\Domain\Procesos\Entities\ProcesoPrendaDetalle;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Models\ProcesoPrendaDetalle as ProcesoPrendaDetalleModel;

/**
 * Eloquent Implementation: ProcesoPrendaDetalleRepository
 */
class EloquentProcesoPrendaDetalleRepository implements ProcesoPrendaDetalleRepository
{
    public function obtenerPorId(int $id): ?ProcesoPrendaDetalle
    {
        $model = ProcesoPrendaDetalleModel::find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function obtenerPorPrenda(int $prendaId): array
    {
        return ProcesoPrendaDetalleModel::where('prenda_pedido_id', $prendaId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    public function obtenerPorPedido(int $numeroPedido): array
    {
        return ProcesoPrendaDetalleModel::whereHas('prendaPedido', function ($q) use ($numeroPedido) {
            $q->where('numero_pedido', $numeroPedido);
        })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    public function obtenerPorPrendaYTipo(int $prendaId, int $tipoProcesoId): ?ProcesoPrendaDetalle
    {
        $model = ProcesoPrendaDetalleModel::where([
            'prenda_pedido_id' => $prendaId,
            'tipo_proceso_id' => $tipoProcesoId,
        ])->first();

        return $model ? $this->mapToDomain($model) : null;
    }

    public function guardar(ProcesoPrendaDetalle $proceso): ProcesoPrendaDetalle
    {
        $model = ProcesoPrendaDetalleModel::create([
            'prenda_pedido_id' => $proceso->getPrendaPedidoId(),
            'tipo_proceso_id' => $proceso->getTipoProcesoId(),
            'ubicaciones' => $proceso->getUbicaciones(),
            'observaciones' => $proceso->getObservaciones(),
            'tallas_dama' => $proceso->getTallasDama(),
            'tallas_caballero' => $proceso->getTallasCalabrero(),
            'estado' => $proceso->getEstado(),
            'notas_rechazo' => $proceso->getNotasRechazo(),
            'fecha_aprobacion' => $proceso->getFechaAprobacion(),
            'aprobado_por' => $proceso->getAprobadoPor(),
            'datos_adicionales' => $proceso->getDatosAdicionales(),
        ]);

        $proceso->setId($model->id);
        return $this->mapToDomain($model);
    }

    public function actualizar(ProcesoPrendaDetalle $proceso): ProcesoPrendaDetalle
    {
        $model = ProcesoPrendaDetalleModel::findOrFail($proceso->getId());

        $model->update([
            'ubicaciones' => $proceso->getUbicaciones(),
            'observaciones' => $proceso->getObservaciones(),
            'tallas_dama' => $proceso->getTallasDama(),
            'tallas_caballero' => $proceso->getTallasCalabrero(),
            'estado' => $proceso->getEstado(),
            'notas_rechazo' => $proceso->getNotasRechazo(),
            'fecha_aprobacion' => $proceso->getFechaAprobacion(),
            'aprobado_por' => $proceso->getAprobadoPor(),
            'datos_adicionales' => $proceso->getDatosAdicionales(),
        ]);

        return $this->mapToDomain($model);
    }

    public function eliminar(int $id): bool
    {
        return ProcesoPrendaDetalleModel::destroy($id) > 0;
    }

    public function obtenerPendientes(): array
    {
        return ProcesoPrendaDetalleModel::pendientes()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    public function obtenerAprobados(): array
    {
        return ProcesoPrendaDetalleModel::aprobados()
            ->orderBy('fecha_aprobacion', 'desc')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    public function obtenerCompletados(): array
    {
        return ProcesoPrendaDetalleModel::where('estado', 'COMPLETADO')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    /**
     * Mapear Eloquent Model a Domain Entity
     */
    private function mapToDomain(ProcesoPrendaDetalleModel $model): ProcesoPrendaDetalle
    {
        return new ProcesoPrendaDetalle(
            id: $model->id,
            prendaPedidoId: $model->prenda_pedido_id,
            tipoProcesoId: $model->tipo_proceso_id,
            ubicaciones: $model->ubicaciones ?? [],
            observaciones: $model->observaciones,
            tallasDama: $model->tallas_dama,
            tallasCalabrero: $model->tallas_caballero,
            estado: $model->estado,
            notasRechazo: $model->notas_rechazo,
            fechaAprobacion: $model->fecha_aprobacion,
            aprobadoPor: $model->aprobado_por,
            datosAdicionales: $model->datos_adicionales
        );
    }
}
