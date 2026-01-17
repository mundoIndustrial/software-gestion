<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EppCategoria extends Model
{
    protected $table = 'epp_categorias';
    
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];
    
    protected $casts = [
        'activo' => 'boolean',
    ];

    public function epps()
    {
        return $this->hasMany(Epp::class, 'categoria_id');
    }
}
