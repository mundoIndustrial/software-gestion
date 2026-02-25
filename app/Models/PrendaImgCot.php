<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaImgCot extends Model
{
    protected $table = 'prenda_img_cot';

    protected $fillable = [
        'prenda_item_id',
        'ruta',
    ];

    public function prendaItem()
    {
        return $this->belongsTo(PrendaItemCot::class, 'prenda_item_id');
    }
}
