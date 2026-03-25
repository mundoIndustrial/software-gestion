<?php

namespace App\Infrastructure\Repositories\Cotizacion;

use App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CotizacionDetalleRepository implements CotizacionDetalleRepositoryInterface
{
    public function obtenerCotizacionConEpp(int $cotizacionId): array
    {
        $eppCot = DB::table('epp_cotizacion')
            ->where('cotizacion_id', $cotizacionId)
            ->first();
        
        $items = DB::table('epp_items_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->orderBy('id')
            ->get();
        
        $valores = DB::table('epp_valor_unitario')
            ->where('cotizacion_id', $cotizacionId)
            ->keyBy('epp_item_id')
            ->get();
        
        $imagenes = DB::table('epp_img_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->get()
            ->groupBy('epp_item_id');

        $itemsUi = $items->map(function ($it) use ($valores, $imagenes) {
            $id = $it->id;
            $vUnitario = $valores[$id] ?? null;
            $imgs = $imagenes[$id] ?? collect([]);
            
            return [
                'id' => $id,
                'nombre' => $it->nombre ?? '',
                'cantidad' => $it->cantidad ?? '',
                'valor_unitario' => $vUnitario ? ($vUnitario->valor ?? '') : '',
                'imagenes' => $imgs->map(fn($img) => [
                    'url' => $img->ruta ?? '',
                    'nombre' => $img->nombre ?? ''
                ])->toArray()
            ];
        })->values()->all();

        return [
            'eppCot' => $eppCot,
            'items' => $itemsUi
        ];
    }

    public function obtenerCotizacionConPrendas(int $cotizacionId): array
    {
        $prendas = DB::table('prenda_items_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->orderBy('id')
            ->get();
        
        $valoresPrendas = DB::table('prenda_valor_unitario')
            ->where('cotizacion_id', $cotizacionId)
            ->keyBy('prenda_item_id')
            ->get();
        
        $imagenesPrendas = DB::table('prenda_img_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->get()
            ->groupBy('prenda_item_id');

        $prendasUi = $prendas->map(function ($prenda) use ($valoresPrendas, $imagenesPrendas) {
            $id = $prenda->id;
            $vUnitario = $valoresPrendas[$id] ?? null;
            $imgs = $imagenesPrendas[$id] ?? collect([]);
            
            return [
                'id' => $id,
                'nombre' => $prenda->nombre ?? '',
                'cantidad' => $prenda->cantidad ?? '',
                'valor_unitario' => $vUnitario ? ($vUnitario->valor ?? '') : '',
                'imagenes' => $imgs->map(fn($img) => [
                    'url' => $img->ruta ?? '',
                    'nombre' => $img->nombre ?? ''
                ])->toArray()
            ];
        })->values()->all();

        return [
            'prendas' => $prendasUi
        ];
    }
}
