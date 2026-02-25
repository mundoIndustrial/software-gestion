<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaValorUnitario extends Model
{
    protected $table = 'prenda_valor_unitario';

    protected $fillable = [
        'prenda_item_id',
        'valor_unitario',
    ];

    public function prendaItem()
    {
        return $this->belongsTo(PrendaItemCot::class, 'prenda_item_id');
    }
}
