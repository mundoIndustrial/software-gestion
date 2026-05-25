<?php

namespace App\Models;

use App\Infrastructure\Services\Despacho\DespachoRowHashService;
use Illuminate\Database\Eloquent\Model;

class DespachoAjusteDetalle extends Model
{
    protected $table = 'despacho_ajustes_detalles';

    protected $fillable = [
        'pedido_produccion_id',
        'row_hash',
        'tipo_item',
        'item_id',
        'talla_id',
        'talla_color_id',
        'genero',
        'revision',
        'cantidad_base',
        'cantidad_ajustada',
        'diferencia',
        'estado',
        'motivo',
        'creado_por',
        'aplicado_por',
        'aplicado_en',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->row_hash = DespachoRowHashService::generar(
                (int) $model->pedido_produccion_id,
                (string) $model->tipo_item,
                (int) $model->item_id,
                (int) $model->talla_id,
                $model->talla_color_id !== null ? (int) $model->talla_color_id : null,
                $model->genero !== null ? (string) $model->genero : null,
            );
        });
    }
}
