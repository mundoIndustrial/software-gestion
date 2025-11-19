<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComponentePrenda extends Model
{
    protected $table = 'componentes_prenda';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    /**
     * Relación con costos
     */
    public function costos(): HasMany
    {
        return $this->hasMany(CostoPrenda::class);
    }
}
