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
        $modelo = EppModel::find($id);

        if (!$modelo) {
            return null;
        }

        // Ignorar tabla epp_imagenes (no existe)
        // Las imágenes se obtienen de pedido_epp_imagenes en el contexto de pedidos
        
        \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] Cargando EPP sin tabla epp_imagenes', [
            'epp_id' => $id,
            'nombre' => $modelo->nombre_completo,
        ]);

        return $this->mapearModeloAAgregado($modelo);
    }


    /**
     * Obtener todos los EPP activos
     */
    public function obtenerActivos(): Collection
    {
        // Caché por 1 hora
        return \Illuminate\Support\Facades\Cache::remember('epps:activos', 3600, function() {
            $epps = EppModel::where('activo', true)
                ->orderBy('categoria_id')
                ->orderBy('nombre_completo')
                ->get();

            // Ignorar tabla epp_imagenes (no existe)
            \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] Obteniendo EPPs activos sin epp_imagenes', [
                'total' => $epps->count(),
            ]);

            return $epps->map(function($modelo) {
                return $this->mapearModeloAAgregado($modelo);
            });
        });
    }


    /**
     * Buscar EPP por término
     */
    public function buscar(string $termino): Collection
    {
        //  DESACTIVADO CACHÉ TEMPORALMENTE PARA DEBUG
        $epps = EppModel::where('activo', true)
            ->where(function ($query) use ($termino) {
                $query->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('marca', 'like', "%{$termino}%");
            })
            ->orderBy('nombre_completo')
            ->limit(50)  // ⚡ Limitar a 50 resultados
            ->get();

        // Ignorar tabla epp_imagenes (no existe)
        \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] Buscando EPPs sin caché', [
            'termino' => $termino,
            'total' => $epps->count(),
            'sql' => EppModel::where('activo', true)->where(function ($query) use ($termino) {
                $query->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('marca', 'like', "%{$termino}%");
            })->toSql(),
        ]);

        return $epps->map(function($modelo) {
            return $this->mapearModeloAAgregado($modelo);
        });
    }

    /**
     * Obtener un EPP por código
     */
    public function obtenerPorCodigo(string $codigo): ?EppAggregate
    {
        //  NOTA: El campo 'codigo' NO existe en la tabla epps después de la migración
        // Esta función devuelve null ya que no hay datos de código disponibles
        \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] obtenerPorCodigo IGNORADA - campo codigo no existe', [
            'codigo' => $codigo,
        ]);
        
        return null;
    }

    /**
     * Obtener EPP por categoría
     */
    public function obtenerPorCategoria(string $categoria): Collection
    {
        //  NOTA: El campo 'categoria_id' NO existe en la tabla epps después de la migración
        // Devolvemos una colección vacía
        \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] obtenerPorCategoria IGNORADA - campo categoria_id no existe', [
            'categoria' => $categoria,
        ]);
        
        return collect([]);
    }

    public function guardar(EppAggregate $epp): void
    {
        $modelo = EppModel::updateOrCreate(
            ['id' => $epp->id()],
            [
                'nombre_completo' => $epp->nombre(),
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
        // Usar nombre_completo (único campo disponible)
        $nombre = $modelo->nombre_completo ?? '';

        $agregado = EppAggregate::reconstruir(
            $modelo->id,
            $nombre,
            $modelo->marca,
            $modelo->tipo,
            $modelo->talla,
            $modelo->color,
            $modelo->descripcion,
            $modelo->activo,
            $modelo->created_at,
            $modelo->updated_at
        );

        \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] Mapeando EPP desde tabla', [
            'epp_id' => $modelo->id,
            'nombre' => $nombre,
        ]);

        return $agregado;
    }

    /**
     * Sincronizar imágenes del agregado con el modelo (DESACTIVADA)
     * IGNORADA: tabla epp_imagenes no existe
     *
     * @param EppModel $modelo
     * @param array<EppImagenValue> $imagenes
     */
    private function sincronizarImagenes(EppModel $modelo, array $imagenes): void
    {
        // Esta función no hace nada, tabla epp_imagenes no existe
        \Illuminate\Support\Facades\Log::debug(' [EPP-REPO] sincronizarImagenes IGNORADA - tabla epp_imagenes no existe', [
            'epp_id' => $modelo->id,
        ]);
    }
}
