<?php

namespace App\Http\Resources;

use App\Helpers\EstadoHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_cotizacion' => $this->numero_cotizacion,
            'usuario_id' => $this->user_id,
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'tipo_cotizacion' => $this->tipo_cotizacion,
            'tipo_cotizacion_id' => $this->tipo_cotizacion_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_envio' => $this->fecha_envio,
            
            // Estado con transformación
            'estado' => [
                'valor' => $this->estado,
                'label' => EstadoHelper::labelCotizacion($this->estado),
                'color' => EstadoHelper::colorCotizacion($this->estado),
                'icono' => EstadoHelper::iconoCotizacion($this->estado),
            ],
            
            'es_borrador' => $this->es_borrador,
            'productos' => $this->productos,
            'especificaciones' => $this->especificaciones,
            'imagenes' => $this->imagenes,
            'tecnicas' => $this->tecnicas,
            'observaciones_tecnicas' => $this->observaciones_tecnicas,
            'ubicaciones' => $this->ubicaciones,
            'observaciones_generales' => $this->observaciones_generales,
            
            'creado_en' => $this->created_at,
            'actualizado_en' => $this->updated_at,
            'eliminado_en' => $this->deleted_at,
        ];
    }
}
