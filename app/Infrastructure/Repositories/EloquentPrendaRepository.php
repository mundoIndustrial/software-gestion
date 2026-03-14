<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Pedidos\Repositories\PrendaRepositoryInterface;
use App\Models\PrendaCot;
use App\Models\PrendaPedido;
use App\Models\TipoManga;
use Illuminate\Support\Facades\DB;

/**
 * Infrastructure Repository: EloquentPrendaRepository
 * 
 * Implementación concreta usando Eloquent ORM
 * Pertenece a la capa de Infrastructure
 */
class EloquentPrendaRepository implements PrendaRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?object
    {
        return PrendaCot::find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?object
    {
        $relationsPorDefecto = [
            'colores_telas.fotos',
            'variantes.telas_multiples.imagenes',
            'fotos',
            'procesos.imagenes'
        ];
        
        $relations = array_merge($relationsPorDefecto, $relations);
        
        return PrendaCot::with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerTiposManga(): array
    {
        try {
            return TipoManga::select('id', 'nombre', 'descripcion')
                ->orderBy('nombre')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo tipos de manga', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(object $prenda): object
    {
        if ($prenda instanceof PrendaCot) {
            $prenda->save();
            return $prenda;
        }
        
        throw new \InvalidArgumentException('Tipo de prenda no soportado');
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        try {
            return PrendaCot::where('id', $id)->update($data) > 0;
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error actualizando prenda', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        try {
            return PrendaCot::destroy($id) > 0;
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error eliminando prenda', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria): array
    {
        try {
            $query = PrendaCot::query();
            
            foreach ($criteria as $campo => $valor) {
                if (is_array($valor)) {
                    $query->whereIn($campo, $valor);
                } else {
                    $query->where($campo, $valor);
                }
            }
            
            return $query->get()->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error en búsqueda', [
                'criteria' => $criteria,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerPorCotizacion(int $cotizacionId): array
    {
        try {
            return PrendaCot::where('cotizacion_id', $cotizacionId)
                ->with([
                    'colores_telas.fotos',
                    'variantes.telas_multiples',
                    'fotos'
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo prendas de cotización', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerVariantes(int $prendaId): array
    {
        try {
            return DB::table('variantes_prendas_cot')
                ->where('prenda_cot_id', $prendaId)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo variantes', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerTelas(int $prendaId): array
    {
        try {
            return DB::table('prenda_tela_cot')
                ->where('prenda_cot_id', $prendaId)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo telas', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerImagenes(int $prendaId): array
    {
        try {
            return DB::table('prenda_fotos_cot')
                ->where('prenda_cot_id', $prendaId)
                ->orderBy('orden')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo imágenes', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerProcesos(int $prendaId): array
    {
        try {
            return DB::table('procesos_prenda_cot')
                ->where('prenda_cot_id', $prendaId)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo procesos', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function obtenerTallasPedido(int $prendaId): array
    {
        try {
            return DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaId)
                ->select('genero', 'talla', 'cantidad')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo tallas de pedido', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function obtenerVariantesPedido(int $prendaId): array
    {
        try {
            return DB::table('prenda_pedido_variantes')
                ->leftJoin('tipos_manga', 'prenda_pedido_variantes.tipo_manga_id', '=', 'tipos_manga.id')
                ->leftJoin('tipos_broche_boton', 'prenda_pedido_variantes.tipo_broche_boton_id', '=', 'tipos_broche_boton.id')
                ->where('prenda_pedido_variantes.prenda_pedido_id', $prendaId)
                ->select(
                    'prenda_pedido_variantes.*',
                    'tipos_manga.nombre as nombre_manga',
                    'tipos_broche_boton.nombre as nombre_broche'
                )
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo variantes de pedido', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function obtenerColoresTelasPedido(int $prendaId): array
    {
        try {
            return DB::table('prenda_pedido_colores_telas')
                ->leftJoin('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                ->leftJoin('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prendaId)
                ->select(
                    'prenda_pedido_colores_telas.id',
                    'colores_prenda.nombre as color',
                    'colores_prenda.codigo as codigo_color',
                    'telas_prenda.nombre as tela',
                    'prenda_pedido_colores_telas.referencia as referencia_tela'
                )
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentPrendaRepository] Error obteniendo colores y telas de pedido', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
