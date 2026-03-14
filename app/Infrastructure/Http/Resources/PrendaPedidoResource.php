<?php

namespace App\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PrendaPedidoResource
 *
 * Serializa el modelo PrendaPedido (con sus relaciones cargadas) a array JSON.
 * Garantiza que cada proceso incluya siempre su ID y relaciones anidadas.
 *
 * Uso:
 *   PrendaPedidoResource::make($prenda)->resolve()
 */
class PrendaPedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $array = $this->resource->toArray();

        if ($this->resource->relationLoaded('procesos') && $this->resource->procesos?->isNotEmpty()) {
            $array['procesos'] = $this->resource->procesos->map(fn($proceso) => [
                'id'             => $proceso->id,
                'tipo_proceso_id'=> $proceso->tipo_proceso_id,
                'tipo_proceso'   => $proceso->tipoProceso?->nombre,
                'slug'           => $proceso->tipoProceso?->slug,
                'ubicaciones'    => $proceso->ubicaciones ? json_decode($proceso->ubicaciones, true) : [],
                'observaciones'  => $proceso->observaciones,
                'estado'         => $proceso->estado,
                'imagenes'       => $proceso->imagenes?->map(fn($img) => [
                    'id'           => $img->id,
                    'ruta_original'=> $img->ruta_original,
                    'ruta_webp'    => $img->ruta_webp,
                    'orden'        => $img->orden,
                    'es_principal' => $img->es_principal,
                ])->toArray() ?? [],
                'tallas'         => $proceso->tallas?->map(fn($t) => [
                    'genero'   => $t->genero,
                    'talla'    => $t->talla,
                    'cantidad' => $t->cantidad,
                ])->toArray() ?? [],
            ])->toArray();
        }

        return $array;
    }
}
