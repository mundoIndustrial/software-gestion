<?php

namespace App\Repositories;

use App\Domain\Procesos\Entities\TipoProceso;
use App\Domain\Procesos\Repositories\TipoProcesoRepository;
use App\Models\TipoProceso as TipoProcesoModel;

/**
 * Eloquent Implementation: TipoProcesoRepository
 */
class EloquentTipoProcesoRepository implements TipoProcesoRepository
{
    public function obtenerPorId(int $id): ?TipoProceso
    {
        $model = TipoProcesoModel::find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function obtenerPorSlug(string $slug): ?TipoProceso
    {
        $model = TipoProcesoModel::where('slug', $slug)->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function obtenerTodos(): array
    {
        return TipoProcesoModel::orderBy('nombre')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    public function obtenerActivos(): array
    {
        return TipoProcesoModel::activos()
            ->orderBy('nombre')
            ->get()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    public function guardar(TipoProceso $tipoProceso): TipoProceso
    {
        $model = TipoProcesoModel::create([
            'nombre' => $tipoProceso->getNombre(),
            'slug' => $tipoProceso->getSlug(),
            'descripcion' => $tipoProceso->getDescripcion(),
            'color' => $tipoProceso->getColor(),
            'icono' => $tipoProceso->getIcono(),
            'activo' => $tipoProceso->isActivo(),
        ]);

        return $this->mapToDomain($model);
    }

    public function actualizar(TipoProceso $tipoProceso): TipoProceso
    {
        $model = TipoProcesoModel::findOrFail($tipoProceso->getId());
        
        $model->update([
            'nombre' => $tipoProceso->getNombre(),
            'slug' => $tipoProceso->getSlug(),
            'descripcion' => $tipoProceso->getDescripcion(),
            'color' => $tipoProceso->getColor(),
            'icono' => $tipoProceso->getIcono(),
            'activo' => $tipoProceso->isActivo(),
        ]);

        return $this->mapToDomain($model);
    }

    public function eliminar(int $id): bool
    {
        return TipoProcesoModel::destroy($id) > 0;
    }

    /**
     * Mapear Eloquent Model a Domain Entity
     */
    private function mapToDomain(TipoProcesoModel $model): TipoProceso
    {
        return new TipoProceso(
            id: $model->id,
            nombre: $model->nombre,
            slug: $model->slug,
            descripcion: $model->descripcion,
            color: $model->color,
            icono: $model->icono,
            activo: $model->activo
        );
    }
}
