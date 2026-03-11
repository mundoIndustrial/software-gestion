<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodegaDetalleVisto extends Model
{
    protected $table = 'bodega_detalles_visto';
    
    protected $fillable = [
        'bodega_detalle_id',
        'user_id',
    ];

    public function detalleEpp()
    {
        return $this->belongsTo(BodegaDetalleTalla::class, 'bodega_detalle_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
