<?php

namespace App\Domain\Epp\Repositories;

use App\Domain\Epp\Aggregates\EppAggregate;
use App\Domain\Epp\Aggregates\EppImagenValue;
use App\Models\Epp as EppModel;
use Illuminate\Support\Collection;

/**
 * Implementación de Repositorio EPP
 * Convierte entre modelos Eloquent y agregados de dominio
 */
class EppRepository implements EppRepositoryInterface
{
    /**
     * Obtener un EPP por ID
     */
    public function obtenerPorId(int $id): ?EppAggregate
    {
        $modelo = EppModel::with('imagenes')
            ->find($id);

        if (!$modelo) {
            return null;
        }

        return $this->mapearModeloAAgregado($modelo);
    }

    /**
     * Obtener un EPP por código
     */
    public function obtenerPorCodigo(string $codigo): ?EppAggregate
    {
        $modelo = EppModel::with('imagenes')
            ->where('codigo', $codigo)
            ->first();

        if (!$modelo) {
            return null;
        }

        return $this->mapearModeloAAgregado($modelo);
    }

    /**
     * Obtener todos los EPP activos
     */
    public function obtenerActivos(): Collection
    {
        return EppModel::where('activo', true)
            ->with('imagenes', 'categoria')
            ->orderBy('categoria_id')
            ->orderBy('nombre')
            ->get()
            ->map(fn($modelo) => $this->mapearModeloAAgregado($modelo));
    }

    /**
     * Obtener EPP por categoría
     */
    public function obtenerPorCategoria(string $categoria): Collection
    {
        return EppModel::where('activo', true)
            ->whereHas('categoria', fn($q) => $q->where('codigo', $categoria))
            ->with('imagenes', 'categoria')
            ->orderBy('nombre')
            ->get()
            ->map(fn($modelo) => $this->mapearModeloAAgregado($modelo));
    }

    /**
     * Buscar EPP por término
     */
    public function buscar(string $termino): Collection
    {
        return EppModel::where('activo', true)
            ->where(function ($query) use ($termino) {
                $query->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('codigo', 'like', "%{$termino}%");
            })
            ->with('imagenes', 'categoria')
            ->orderBy('nombre')
            ->get()
            ->map(fn($modelo) => $this->mapearModeloAAgregado($modelo));
    }

    /**
     * Guardar un EPP
     */
    public function guardar(EppAggregate $epp): void
    {
        $modelo = EppModel::updateOrCreate(
            ['id' => $epp->id()],
            [
                'codigo' => (string)$epp->codigo(),
                'nombre' => $epp->nombre(),
                'categoria' => (string)$epp->categoria(),
                'descripcion' => $epp->descripcion(),
                'activo' => $epp->estaActivo(),
            ]
        );

        // Sincronizar imágenes
        $this->sincronizarImagenes($modelo, $epp->imagenes());
    }

    /**
     * Eliminar un EPP (soft delete)
     */
    public function eliminar(EppAggregate $epp): void
    {
        EppModel::find($epp->id())?->delete();
    }

    /**
     * Obtener todas las categorías disponibles
     */
    public function obtenerCategorias(): Collection
    {
        return \App\Models\EppCategoria::where('activo', true)
            ->orderBy('nombre')
            ->pluck('codigo')
            ->sort();
    }

    /**
     * Mapear modelo Eloquent a agregado de dominio
     */
    private function mapearModeloAAgregado(EppModel $modelo): EppAggregate
    {
        // Obtener código de categoría
        $codigoCategoria = $modelo->categoria?->codigo ?? 'OTRA';

        $agregado = EppAggregate::reconstruir(
            $modelo->id,
            $modelo->codigo,
            $modelo->nombre,
            $codigoCategoria,
            $modelo->descripcion,
            $modelo->activo,
            $modelo->created_at,
            $modelo->updated_at,
            $modelo->deleted_at
        );

        // Agregar imágenes
        try {
            foreach ($modelo->imagenes as $imagen) {
                $imagenValue = new EppImagenValue(
                    $imagen->id,
                    $imagen->archivo,
                    $imagen->principal,
                    $imagen->orden
                );
                $agregado->agregarImagen($imagenValue);
            }
        } catch (\Exception $e) {
            // Log del error pero no detener el mapeo
            \Illuminate\Support\Facades\Log::warning('  Error mapeando imágenes EPP', [
                'epp_id' => $modelo->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $agregado;
    }

    /**
     * Sincronizar imágenes del agregado con el modelo
     *
     * @param EppModel $modelo
     * @param array<EppImagenValue> $imagenes
     */
    private function sincronizarImagenes(EppModel $modelo, array $imagenes): void
    {
        // Obtener IDs de las imágenes existentes
        $imagenesExistentes = $modelo->imagenes->pluck('id')->toArray();

        // Obtener IDs de las imágenes del agregado
        $imagenesAgregado = array_map(fn($img) => $img->id(), $imagenes);

        // Eliminar imágenes que no están en el agregado
        $paraEliminar = array_diff($imagenesExistentes, $imagenesAgregado);
        if (!empty($paraEliminar)) {
            $modelo->imagenes()->whereIn('id', $paraEliminar)->delete();
        }

        // Actualizar imágenes existentes e insertar nuevas
        foreach ($imagenes as $imagen) {
            $modelo->imagenes()->updateOrCreate(
                ['id' => $imagen->id()],
                [
                    'archivo' => $imagen->archivo(),
                    'principal' => $imagen->esPrincipal(),
                    'orden' => $imagen->orden(),
                ]
            );
        }
    }
}
