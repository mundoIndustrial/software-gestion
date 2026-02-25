<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaItemCot extends Model
{
    protected $table = 'prenda_items_cot';

    protected $fillable = [
        'cotizacion_id',
        'descripcion',
        'cantidad',
        'observaciones',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function imagenes()
    {
        return $this->hasMany(PrendaImgCot::class, 'prenda_item_id');
    }

    public function valorUnitario()
    {
        return $this->hasOne(PrendaValorUnitario::class, 'prenda_item_id');
    }
}
