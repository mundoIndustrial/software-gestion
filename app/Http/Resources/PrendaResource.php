<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource: PrendaResource
 * 
 * Transforma Prenda domain model a JSON serializable.
 */
class PrendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nombre' => $this->getNombrePrenda(),
            'cantidad' => [
                'total' => $this->getCantidadTotal(),
                'entregada' => $this->getCantidadEntregada(),
                'pendiente' => $this->getCantidadPendiente(),
                'porcentaje_entrega' => $this->getPorcentajeEntrega(),
            ],
            'tallas' => $this->getCantidadTalla(),
            'descripcion' => $this->getDescripcion(),
            'atributos' => [
                'color_id' => $this->getColorId(),
                'tela_id' => $this->getTelaId(),
                'tipo_manga_id' => $this->getTipoMangaId(),
                'tipo_broche_id' => $this->getTipoBrocheId(),
                'tiene_bolsillos' => $this->tieneBolsillos(),
                'tiene_reflectivo' => $this->tieneReflectivo(),
            ],
        ];
    }
}
