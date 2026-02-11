<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Pedidos\Repositories\CotizacionRepositoryInterface;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;

/**
 * Infrastructure Repository: EloquentCotizacionRepository
 * 
 * Implementación concreta usando Eloquent ORM
 * Pertenece a la capa de Infrastructure
 */
class EloquentCotizacionRepository implements CotizacionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?object
    {
        return Cotizacion::find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?object
    {
        $relationsPorDefecto = [
            'tipo_cotizacion',
            'cliente'
        ];
        
        $relations = array_merge($relationsPorDefecto, $relations);
        
        return Cotizacion::with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerTelas(int $cotizacionId, int $prendaId): array
    {
        try {
            return DB::table('prenda_tela_cot as ptc')
                ->join('telas as t', 'ptc.tela_id', '=', 't.id')
                ->join('colores as c', 'ptc.color_id', '=', 'c.id')
                ->where('ptc.cotizacion_id', $cotizacionId)
                ->where('ptc.prenda_cot_id', $prendaId)
                ->select([
                    'ptc.id',
                    't.nombre as tela_nombre',
                    'c.nombre as color_nombre',
                    'ptc.referencia',
                    'ptc.descripcion'
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentCotizacionRepository] Error obteniendo telas de cotización', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerVariaciones(int $cotizacionId, int $prendaId): array
    {
        try {
            return DB::table('variantes_prendas_cot as vpc')
                ->leftJoin('prenda_tela_cot as ptc', function($join) {
                    $join->on('vpc.id', '=', 'ptc.variante_prenda_cot_id')
                         ->where('ptc.cotizacion_id', '=', DB::raw($cotizacionId));
                })
                ->where('vpc.cotizacion_id', $cotizacionId)
                ->where('vpc.prenda_cot_id', $prendaId)
                ->select([
                    'vpc.id',
                    'vpc.tipo_manga_id',
                    'vpc.manga_obs',
                    'vpc.tiene_bolsillos',
                    'vpc.bolsillos_obs',
                    'vpc.tipo_broche_boton_id',
                    'vpc.broche_boton_obs',
                    'ptc.tela_id',
                    'ptc.color_id',
                    'ptc.referencia',
                    'ptc.descripcion'
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[EloquentCotizacionRepository] Error obteniendo variaciones', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerUbicaciones(int $cotizacionId, int $prendaId): array
    {
        try {
            // Para Reflectivo/Logo, las ubicaciones podrían estar en una tabla específica
            // Por ahora retornamos array vacío como placeholder
            return [];
        } catch (\Exception $e) {
            \Log::error('[EloquentCotizacionRepository] Error obteniendo ubicaciones', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerDescripcion(int $cotizacionId, int $prendaId): string
    {
        try {
            $prenda = DB::table('prendas_cot')
                ->where('cotizacion_id', $cotizacionId)
                ->where('id', $prendaId)
                ->value('descripcion');
                
            return $prenda ?? '';
        } catch (\Exception $e) {
            \Log::error('[EloquentCotizacionRepository] Error obteniendo descripción', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function esTipo(int $cotizacionId, string $tipo): bool
    {
        try {
            $tipoCotizacion = DB::table('cotizaciones as c')
                ->join('tipos_cotizacion as tc', 'c.tipo_cotizacion_id', '=', 'tc.id')
                ->where('c.id', $cotizacionId)
                ->where('tc.nombre', $tipo)
                ->exists();
                
            return $tipoCotizacion;
        } catch (\Exception $e) {
            \Log::error('[EloquentCotizacionRepository] Error verificando tipo de cotización', [
                'cotizacion_id' => $cotizacionId,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtenerTipo(int $cotizacionId): ?object
    {
        try {
            return DB::table('cotizaciones as c')
                ->join('tipos_cotizacion as tc', 'c.tipo_cotizacion_id', '=', 'tc.id')
                ->where('c.id', $cotizacionId)
                ->select('tc.*')
                ->first();
        } catch (\Exception $e) {
            \Log::error('[EloquentCotizacionRepository] Error obteniendo tipo de cotización', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
