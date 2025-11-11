<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioTela extends Model
{
    protected $table = 'inventario_telas';
    
    protected $fillable = [
        'categoria',
        'nombre_tela',
        'stock',
        'metraje_sugerido',
    ];

    public $timestamps = false;
    
    protected $dates = ['fecha_registro'];
}
